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
		    Diagral_eOne::synchronize('all');
		    ajax::success();
      } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
      }
    }

    // Mise en place des icones d'équipement selon le type
    if (init('action') == 'getIconPath') {
		try {
			$eqLogic = eqLogic::byId(init(eqLogicId), 'Diagral_eOne');
			if (is_object($eqLogic)) {
				$iconPath = Diagral_eOne::getPathDeviceIcon($eqLogic);
				$return = array('iconPath' => $iconPath);
				ajax::success(json_encode($return));
			} else {
			    ajax::success(false);
			}
		} catch (Exception $e) {
			ajax::error(displayExeption($e), $e->getCode());
		}
	}

    // Lancement de la suppression des données de tracking
    if (init('action') == 'delete_remote_datainfo') {
        try {
            Diagral_eOne::installTracking(1);
            ajax::success();
        } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
        }
    }

    // Génératon d'une archive de DiagDebug
    if (init('action') == 'generateDiagDebug') {
        try {
            $diagDebug = Diagral_eOne::generateDiagDebug();
            ajax::success($diagDebug);
        } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
        }
    }

    // Execution PostSave
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

    //Generation du lien vers la centrale pour la page EqLogic des detecteurs à Image
    if (init('action') == 'generateCentralLink') {
        try {
            if(! empty(init('eqID'))) {
                $eqlogic = eqLogic::byId(init('eqID'));
                if(in_array($eqlogic->getConfiguration('type'),array('imagedetector', 'camera', 'adyx-portal'))) {
                    $centrale = eqLogic::byLogicalId($eqlogic->getConfiguration('centrale'), 'Diagral_eOne');
                    if (is_object($centrale)) {
                        $return = array('centraleId' => $centrale->getId());
                        ajax::success(json_encode($return));
                    } else {
                        ajax::success(json_encode(array('centraleId' => '')));
                    }
                } else {
                    ajax::success(json_encode(array('centraleId' => '')));
                }
            } else {
                ajax::success(json_encode(array('centraleId' => '')));
            }
        } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
        }
    }

    //Lancement de la suppression des données de tracking
    if (init('action') == 'notificationVerifyScenario') {
        try {
            $alarmEq = eqLogic::byId(init('eqID'));
            $scenarioID = $alarmEq->getConfiguration('notificationScenarioID');
            $scenario = scenario::byId($scenarioID);
            if (is_object($scenario)) {
                $return = array('scenarioExist' => true, 'scenarioID' => $scenarioID, 'scenarioName' => $scenario->getName());
                ajax::success(json_encode($return));
            } else {
                ajax::success(false);
            }

        } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
        }
    }

    //Création ou mise à jour du scénario de notification
    if (init('action') == 'notificationGenerateUpdateScenario') {
        try {
                $alarmEq = eqLogic::byId(init('eqID'));
                $notificationPlugin = $alarmEq->getConfiguration('notificationPlugin');

                if ($notificationPlugin === "" || $alarmEq->getConfiguration('notificationEqLogic') === "") {
                    ajax::error("Le plugin ou l'equipement de notification n'est pas disponible. Pensez à sauvegarder vos configurations avant la création du scénario");
                }

                //Ouverture du fichier de template
                $scenarioTemplateLocation = '/../config/scenarioTemplate.json';
                if (!file_exists(dirname(__FILE__) . $scenarioTemplateLocation)) {
                    log::add('Diagral_eOne', 'warning', 'Unable to read scenarioTemplate.json');
                }
                $scenarioTemplate = json_decode(file_get_contents(dirname(__FILE__) . $scenarioTemplateLocation), true);
                log::add('Diagral_eOne', 'debug', 'Data : '.var_export($scenarioTemplate, True));
                if (!is_array($scenarioTemplate)) {
                    log::add('Diagral_eOne', 'warning', 'Unable to decode plugin file scenarioTemplate.json');
                }

                // Debug des données reçus
                log::add('Diagral_eOne', 'debug', 'ID de l\'équipement reçu : ' . init('eqID'));

                // Définission des commandes par plugins supportés
                switch ($notificationPlugin) {
                    case 'maillistener':
                        $trigger = "[HTML]";
                        $mailTo = "[Expéditeur]";
                        $subject = "[Sujet]";
                        $htmlContent = $trigger;
                        break;
                    default:
                        log::add('Diagral_eOne', 'warning', 'Plugin ' . $notificationPlugin . ' not supported to create or update scenario');
                        ajax::error('Diagral_eOne', 'warning', 'Plugin ' . $notificationPlugin . ' not supported to create or update scenario');
                        break;
                }

                // Récupération du nom de la commande de notication (recu dans un format type #eqLogic61#)
                preg_match('/#eqLogic([0-9]*)#/', $alarmEq->getConfiguration('notificationEqLogic'), $notificationEqID);
                $notificationEqLogic = eqLogic::byId($notificationEqID[1]);
                log::add('Diagral_eOne', 'debug', 'Commande : ' . $notificationEqLogic->getHumanName());
                // Recuperation de l'équipement alarme
                $alarmEq = eqLogic::byId(init('eqID'));

                // Remplacement des commandes dans le template
                $scenarioTemplate['trigger'] = array("#" . $notificationEqLogic->getHumanName().$trigger . "#");
                $scenarioTemplate['elements'][0]['subElements'][0]['expressions'][0]['expression'] = str_replace("#MAIL-TO#", "#" . $notificationEqLogic->getHumanName().$mailTo . "#", $scenarioTemplate['elements'][0]['subElements'][0]['expressions'][0]['expression']);
                $scenarioTemplate['elements'][0]['subElements'][1]['expressions'][0]['expression'] = "#" . $alarmEq->getHumanName() . "[Importer Message]#";
                $scenarioTemplate['elements'][0]['subElements'][1]['expressions'][0]['options']['title'] = str_replace("#MAIL-SUBJECT#", "#" . $notificationEqLogic->getHumanName().$subject . "#", $scenarioTemplate['elements'][0]['subElements'][1]['expressions'][0]['options']['title']);
                $scenarioTemplate['elements'][0]['subElements'][1]['expressions'][0]['options']['message'] = "#" . $notificationEqLogic->getHumanName().$htmlContent . "#";

                // Creation du scenario (ou mise à jour si existant)
                $scenario = scenario::byId($alarmEq->getConfiguration('notificationScenarioID'));
                if (!is_object($scenario)) {
                    // Déclaration d'un nouveau scenario
                    $scenario = new scenario();
                }
                utils::a2o($scenario, $scenarioTemplate);
                $scenario->setConfiguration('timeDependency', $scenarioTemplate['configuration']['timeDependency']);
                $scenario->setConfiguration('has_return', $scenarioTemplate['configuration']['has_return']);
                $scenario_element_list = array();
                if (isset($scenarioTemplate['elements'])) { // Ajout des elements du scénario
                    foreach ($scenarioTemplate['elements'] as $element) {
                        $scenario_element_list[] = scenarioElement::saveAjaxElement($element);
                    }
                    $scenario->setScenarioElement($scenario_element_list);
                }
                $scenario->save();

                // Retrouve le scénario venant d'être créé pour stocker son ID
                $scenarioCreated = scenario::byObjectNameGroupNameScenarioName('Aucun', 'Aucun', 'Notification Diagral');
                $alarmEq->setConfiguration('notificationScenarioID', $scenarioCreated->getId());
                $alarmEq->save();

                log::add('Diagral_eOne', 'debug', 'Data : '.var_export($scenarioCreated, True));
                ajax::success();

        } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
        }
    }

    // Suppression du scénario de recéption de notification Diagral
    if (init('action') == 'notificationDeleteScenario') {
        try {
            // Recuperation de l'équipement alarme
            $alarmEq = eqLogic::byId(init('EqId'));
            // Recuperation de l'ID du plugin Alarm
            $scenario = scenario::byId($alarmEq->getConfiguration('notificationScenarioID'));
            log::add('Diagral_eOne', 'debug', 'Data : '.var_export($scenario, True));
            if (is_object($scenario)) {
                $scenario->remove();
                $alarmEq->setConfiguration('notificationScenarioID', '');
            } else {
                ajax::error("Scenario invalide");
            }
            ajax::success();
        } catch (Exception $e) {
            ajax::error(displayExeption($e), $e->getCode());
        }
      }


    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
