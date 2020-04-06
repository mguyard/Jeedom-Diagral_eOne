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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function Diagral_eOne_install() {
    Diagral_eOne_Cron_Pull('create');
    Diagral_eOne_Cron_JSON('create');
    message::add('Diagral_eOne', 'Merci pour l\'installation de ce plugin, consultez les notes de version (https://community.jeedom.com/t/plugin-diagral-eone/10909) avant utilisation svp');
}

function Diagral_eOne_update() {
    log::add('Diagral_eOne', 'info', 'Mise à jour des commandes du Plugin Diagral_eOne');
    foreach (eqLogic::byType('Diagral_eOne') as $eqLogic) {
        $eqLogic->save();
        log::add('Diagral_eOne', 'info', 'Mise à jour effectuée pour l\'équipement '. $eqLogic->getHumanName());
    }
    Diagral_eOne_Cron_Pull('update');
    Diagral_eOne_Cron_JSON('update');
    config::save('InstallBaseStatus', 1, 'Diagral_eOne'); // Active le mode de communication par defaut -- A retirer plus tard car reactivera ceux qui ont désactivé entre 2 updates
    message::add('Diagral_eOne', 'La mise à jour a (re)activer la communication avec le developpeur. Voir la documentation pour des compléments d\'informations'); // A retirer plus tard avec la ligne de code du dessus
    message::add('Diagral_eOne', 'Merci pour la mise à jour de ce plugin, consultez les notes de version (https://community.jeedom.com/t/plugin-diagral-eone/10909) avant utilisation svp');
}


function Diagral_eOne_remove() {
    Diagral_eOne_Cron_Pull('remove');
    Diagral_eOne_Cron_JSON('remove');
    log::add('Diagral_eOne', 'warn', 'Suppression du Plugin Diagral_eOne');
}

function Diagral_eOne_Cron_Pull($action) {
    $cron = cron::byClassAndFunction('Diagral_eOne', 'pull');
    switch ($action) {
        case 'create':
            if ( ! is_object($cron)) {
                $cron = new cron();
                $cron->setClass('Diagral_eOne');
                $cron->setFunction('pull');
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setTimeout(2);
                $cron->setSchedule('*/10 * * * *');
                $cron->save();
            }
            break;
        case 'remove':
            if (is_object($cron)) {
                $cron->remove(true);
            }
            break;
        case 'update':
            if ( ! is_object($cron)) {
                Diagral_eOne_Cron_Pull('create');
            }
            break;
    }
}

function Diagral_eOne_Cron_JSON($action) {
    $cron = cron::byClassAndFunction('Diagral_eOne', 'generateJsonAllDevices');
    switch ($action) {
        case 'create':
            $random_minutes = random_int(0, 59);
            $random_hours = random_int(0, 23);
            if ( ! is_object($cron)) {
                $cron = new cron();
                $cron->setClass('Diagral_eOne');
                $cron->setFunction('generateJsonAllDevices');
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setTimeout(5);
                $cron->setSchedule($random_minutes . ' ' . $random_hours . ' * * 7');
                $cron->save();
            }
            break;
        case 'remove':
            if (is_object($cron)) {
                $cron->remove(true);
            }
            break;
        case 'update':
            if ( ! is_object($cron)) {
                Diagral_eOne_Cron_JSON('create');
            }
            break;
    }
}

?>
