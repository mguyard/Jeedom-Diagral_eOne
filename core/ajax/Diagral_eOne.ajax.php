<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();
    // Lancement de la synchronisation des equipements
    if (init('action') == 'synchronize') {
      try {
		    Diagral_eOne::synchronize();
		    ajax::success();
      } catch (Exception $e) {
        ajax::error(displayExeption($e), $e->getCode());
      }
    }

    //Lancement de la suppression des données de tracking
    if (init('action') == 'delete_remote_datainfo') {
        try {
              Diagral_eOne::installTracking(1);
              ajax::success();
        } catch (Exception $e) {
          ajax::error(displayExeption($e), $e->getCode());
        }
    }


    if (init('action') == 'postSave') {
        //Called after a plugin configuration save
        // Let's first check new configuration values
    	try {
    		Diagral_eOne::checkConfig();
    	} catch (Exception $e) {
    		//Invalid configuration.
            //Let's firt set back the old values
            config::save('email', init('email'), 'Diagral_eOne');
            config::save('password', init('password'), 'Diagral_eOne');
            config::save('retry', init('retry'), 'Diagral_eOne');
            config::save('waitRetry', init('waitRetry'), 'Diagral_eOne');
            config::save('polling_interval', init('polling_interval'), 'Diagral_eOne');
            config::save('InstallBaseStatus', init('InstallBaseStatus'), 'Diagral_eOne');
            config::save('InstallBaseAnonymousOnly', init('InstallBaseAnonymousOnly'), 'Diagral_eOne');
            config::save('InstallBaseEmailAddr', init('InstallBaseEmailAddr'), 'Diagral_eOne');
    		//Let's then the error details
    		ajax::error(displayExeption($e), $e->getCode());
        }

        // Default Value
        if (empty(config::byKey('retry', 'Diagral_eOne'))) {
            config::save('retry', init('default_retry'), 'Diagral_eOne');
        }
        if (empty(config::byKey('waitRetry', 'Diagral_eOne'))) {
            config::save('waitRetry', init('default_waitRetry'), 'Diagral_eOne');
        }
        if (empty(config::byKey('polling_interval', 'Diagral_eOne'))) {
            config::save('polling_interval', init('default_polling_interval'), 'Diagral_eOne');
        }

        //Configuration check OK
        $cron = cron::byClassAndFunction('Diagral_eOne', 'pull');
        if (!is_object($cron)) {
            $cron = new cron();
                $cron->setClass('Diagral_eOne');
                $cron->setFunction('pull');
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setTimeout(2);
                $cron->setSchedule('*/' . intval(config::byKey('polling_interval', 'Diagral_eOne')) . ' * * * *');
                $cron->save();
                log::add('Diagral_eOne', 'info', 'checkConfig::AjaxPull::Re-create cron for pull');
        } else {
            $cron->setSchedule('*/' . intval(config::byKey('polling_interval', 'Diagral_eOne')) . ' * * * *');
            $cron->save();
            log::add('Diagral_eOne', 'info', 'checkConfig::AjaxPull::Update cron for pull');

        }
        ajax::success();
    }


    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
