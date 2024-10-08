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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

define('__PLGBASE__', dirname(dirname(__DIR__)));
require_once (__PLGBASE__.'/3rparty/Diagral-eOne-API-PHP/class/Diagral/Diagral_eOne.class.php');
require_once (__PLGBASE__.'/3rparty/DiagDebug/DiagDebug.class.php');
include(__PLGBASE__.'/3rparty/HTTPFul/httpful.phar');

class Diagral_eOne extends eqLogic {
    /*     * *************************Attributs****************************** */

    public static $_widgetPossibility = array(
		'custom' => true,
		'custom::layout' => false,
		'parameters' => array(),
	);

    /*     * ***********************Methode static*************************** */


    /**
     * Sync Dispatcher
     */
    public static function synchronize($type='all') {
        $callResults = array();
        $message = array();
        // Lancement connexion Diagral
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'), config::byKey('plugin_version', 'Diagral_eOne'));
        $MyAlarm->verbose = boolval(config::byKey('verbose', 'Diagral_eOne'));
        $accountInfos = $MyAlarm->login();
        log::add('Diagral_eOne', 'debug', 'Synchronize::Systems::Login ' . var_export($accountInfos, true));
        $Diagral_systems = $MyAlarm->getSystems();
        log::add('Diagral_eOne', 'debug', 'Synchronize::Systems::GetSystems ' . var_export($Diagral_systems, true));
        // Execution des creations/maj d'équipements
        switch($type) {
            case 'systems':
                $systemsResult = Diagral_eOne::syncSystems($Diagral_systems);
                array_push($callResults, $systemsResult);
                break;
            case 'all':
                $systemsResult = Diagral_eOne::syncSystems($Diagral_systems);
                foreach (eqLogic::byTypeAndSearchConfiguration('Diagral_eOne', 'systemid') as $system) {
                    if ($system->getConfiguration('type') == 'centrale') {
                        if (! empty($system->getConfiguration('mastercode',''))) {
                            $MyAlarm->setSystemId(intval($system->getConfiguration('systemid')));
                            $MyAlarm->getConfiguration();
                            $MyAlarm->connect($system->getConfiguration('mastercode'));
                            $MyAlarm->refreshDeviceMultizone();
                            $imageDetectorResult = Diagral_eOne::syncImageDetectors($MyAlarm, $system);
                            $cameraResult = Diagral_eOne::syncCameras($MyAlarm, $system);
                            $automationsResults = Diagral_eOne::syncAutomations($MyAlarm, $system);
                            $modulesResults = Diagral_eOne::syncModules($MyAlarm, $system);
                        } else {
                            if (is_object($system)) {
                                log::add('Diagral_eOne', 'warning', 'Synchronize::Centrale Bypass de la centrale ' . $system->getName() . ' car le masterCode est vide.');
                            } else {
                                log::add('Diagral_eOne', 'warning', 'Synchronize::Centrale Bypass d\'une ou plusieurs centrale(s) car le masterCode est vide.');
                            }
                        }
                    }
                }
                
                array_push($callResults, $systemsResult, $imageDetectorResult, $cameraResult, $automationsResults, $modulesResults);
                break;
        }
        // TODO : Voir pourquoi le logout ne marche plus
        //$MyAlarm->logout();
        foreach ($callResults as $callResult) {
            if ($callResult['nbCreated'] > 0) array_push($message, 'Ajout de ' . $callResult['nbCreated'] . ' ' . $callResult['type']);
            if ($callResult['nbUpdated'] > 0) array_push($message, 'Mise à jour de ' . $callResult['nbUpdated'] . ' '  . $callResult['type']);
        }
        event::add('jeedom::alert', array(
            'level' => 'warning',
            'page' => 'Diagral_eOne',
            'message' => implode('<br/>', $message),
        ));
    }

    /**
     * Sync Systems
     * @return array
     */
    public static function syncSystems($Diagral_systems) {
        log::add('Diagral_eOne', 'debug', 'Synchronize::Systems::GetSystems ' . var_export($Diagral_systems, true));
        $nbCreated = 0;
        foreach ($Diagral_systems as $key => $value) {
            $Alarm = Diagral_eOne::byLogicalId($value['id'], 'Diagral_eOne');
            if (!is_object($Alarm)) {
                log::add('Diagral_eOne', 'info', "Synchronize::Systems Alarme trouvée ".$value['name']."(".$value['id']."):");
                $eqLogic = new Diagral_eOne();
                $eqLogic->setName($value['name']);
                $eqLogic->setIsEnable(0);
                $eqLogic->setIsVisible(1);
                $eqLogic->setLogicalId($value['id']);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('systemid', $key);
                $eqLogic->setConfiguration('type', 'centrale');
                $eqLogic->setConfiguration('templateDiagral', 1);
                $nbCreated++;
            } else {
                log::add('Diagral_eOne', 'info', "Synchronize::Systems Alarme ".$Alarm->getName()." mise à jour.");
                $eqLogic = $Alarm;
                $eqLogic->setName($Alarm->getName());
                $eqLogic->setIsEnable($Alarm->getIsEnable());
                $eqLogic->setIsVisible($Alarm->getIsVisible());
                $eqLogic->setLogicalId($value['id']);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('systemid', $key);
                $eqLogic->setConfiguration('type', 'centrale');
                if ($eqLogic->getConfiguration('templateDiagral') == "") {
                    $eqLogic->setConfiguration('templateDiagral', 1);
                }
            }
            $eqLogic->save();
        }
        return array(
            "type" => "système(s)",
            "nbCreated" => $nbCreated,
            "nbUpdated" => count($Diagral_systems) - $nbCreated
        );
    }

    /**
     * Synchronize Diagral Image detectors
     * @return array to Ajax
     */
    public static function syncImageDetectors($MyAlarm, $system) {
        $imageDetectors = $MyAlarm->getImageDetectors();
        $nbCreated = 0;
        foreach ($imageDetectors as $imageDetector) {
            $detector = Diagral_eOne::byLogicalId($imageDetector['radioId'], 'Diagral_eOne');
            if (!is_object($detector)) {
                log::add('Diagral_eOne', 'info', "Synchronize::ImageDetector Detecteur à image trouvée (". $imageDetector['customLabel'] . ")");
                $eqLogic = new Diagral_eOne();
                $eqLogic->setName($imageDetector['customLabel']);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(0);
                $eqLogic->setLogicalId($imageDetector['radioId']);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('type', 'imagedetector');
                $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                $eqLogic->setConfiguration('index', $imageDetector['index']);
                $eqLogic->setConfiguration('carirOnDemand', $imageDetector['carirOnDemand']);
                $eqLogic->setConfiguration('affectedToZone', $imageDetector['affectedToZone']);
                $eqLogic->setConfiguration('autoDlVideo', '0');
                $nbCreated++;
            } else {
                log::add('Diagral_eOne', 'info', "Synchronize::ImageDetectors Detecteur à image (".$detector->getName().") mis à jour.");
                $eqLogic = $detector;
                $eqLogic->setName($detector->getName());
                $eqLogic->setIsEnable($detector->getIsEnable());
                $eqLogic->setIsVisible($detector->getIsVisible());
                $eqLogic->setLogicalId($imageDetector['radioId']);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('type', 'imagedetector');
                $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                $eqLogic->setConfiguration('index', $imageDetector['index']);
                $eqLogic->setConfiguration('carirOnDemand', $imageDetector['carirOnDemand']);
                $eqLogic->setConfiguration('affectedToZone', $imageDetector['affectedToZone']);
            }
            $eqLogic->save();
        }
        return array(
            "type" => "détecteur(s) à image",
            "nbCreated" => $nbCreated,
            "nbUpdated" => count($imageDetectors) - $nbCreated
        );
    }


    public static function syncCameras($MyAlarm, $system) {
        $cameras = $MyAlarm->getCameras();
        $nbCreated = 0;
        foreach ($cameras as $camera) {
            $cam = Diagral_eOne::byLogicalId($camera['macAddress'], 'Diagral_eOne');
            if (!is_object($cam)) {
                log::add('Diagral_eOne', 'info', "Synchronize::Cameras Camera trouvée (". $camera['name'] . ")");
                $eqLogic = new Diagral_eOne();
                $eqLogic->setName($camera['name']);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(0);
                $eqLogic->setLogicalId($camera['macAddress']);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('type', 'camera');
                $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                $eqLogic->setConfiguration('index', $camera['index']);
                $eqLogic->setConfiguration('ip', $camera['ip']);
                $eqLogic->setConfiguration('autoDlVideo', '0');
                $nbCreated++;
            } else {
                log::add('Diagral_eOne', 'info', "Synchronize::Camera Camera (".$cam->getName().") mis à jour.");
                $eqLogic = $cam;
                $eqLogic->setName($cam->getName());
                $eqLogic->setIsEnable($cam->getIsEnable());
                $eqLogic->setIsVisible($cam->getIsVisible());
                $eqLogic->setLogicalId($camera['macAddress']);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('type', 'camera');
                $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                $eqLogic->setConfiguration('index', $camera['index']);
                $eqLogic->setConfiguration('ip', $camera['ip']);
            }
            $eqLogic->save();
        }
        return array(
            "type" => "camera(s)",
            "nbCreated" => $nbCreated,
            "nbUpdated" => count($cameras) - $nbCreated
        );
    }

    public static function syncAutomations($MyAlarm, $system) {
        $automationList = $MyAlarm->getAutomations();
        $KNXautomationList = $MyAlarm->getKNXAutomations();
        $allAutomations = array_merge($automationList,$KNXautomationList);
        log::add('Diagral_eOne', 'debug', 'Automation List Content : ' . var_export($allAutomations, TRUE));
        $nbCreated = 0;
        foreach ($allAutomations as $automation) {
            $automationObject = Diagral_eOne::byLogicalId(strtolower($automation['type']['type'].'-'.$automation['type']['application'].'_'.$automation['index']), 'Diagral_eOne');
            log::add('Diagral_eOne', 'debug', 'Automation Object found : ' . var_export($automationObject, TRUE));
            if (!is_object($automationObject)) {
                log::add('Diagral_eOne', 'info', "Synchronize::Automation Equipement Automation trouvé (". $automation['name'] . ")");
                $eqLogic = new Diagral_eOne();
                $eqLogic->setName($automation['name']);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(0);
                $eqLogic->setLogicalId(strtolower($automation['type']['type'].'-'.$automation['type']['application'].'_'.$automation['index']));
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('type', strtolower($automation['type']['type'].'-'.$automation['type']['application']));
                $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                $eqLogic->setConfiguration('index', $automation['index']);
                $nbCreated++;
            } else {
                log::add('Diagral_eOne', 'info', "Synchronize::Automation Equipement Automation (".$automationObject->getName().") mis à jour.");
                $eqLogic = $automationObject;
                $eqLogic->setName($automationObject->getName());
                $eqLogic->setIsEnable($automationObject->getIsEnable());
                $eqLogic->setIsVisible($automationObject->getIsVisible());
                $eqLogic->setLogicalId(strtolower($automation['type']['type'].'-'.$automation['type']['application'].'_'.$automation['index']));
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('type', strtolower($automation['type']['type'].'-'.$automation['type']['application']));
                $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                $eqLogic->setConfiguration('index', $automation['index']);
            }
            $eqLogic->save();
        }
        return array(
            "type" => "Automation",
            "nbCreated" => $nbCreated,
            "nbUpdated" => count($automationList) - $nbCreated
        );
    }

    public static function syncModules($MyAlarm, $system) {
        $nbCreated = 0;
        $moduleListSum = 0;
        foreach (array('commands', 'transmitters', 'sensors', 'alarms') as $moduleType) {
            $moduleList = $MyAlarm->getModules($moduleType);
            $moduleListSum = $moduleListSum + count($moduleList);
            log::add('Diagral_eOne', 'debug', var_export($moduleList, TRUE));
            foreach ($moduleList as $module) {
                $moduleObject = Diagral_eOne::byLogicalId($module['radioId'], 'Diagral_eOne');
                if (!is_object($moduleObject)) {
                    log::add('Diagral_eOne', 'info', "Synchronize::Modules Module trouvé (". $module['customLabel'] . ")");
                    $eqLogic = new Diagral_eOne();
                    if (empty($module['customLabel'])) {
                        $eqLogic->setName('Module '.$module['radioId']);
                    } else {
                        $eqLogic->setName($module['customLabel']);
                    }
                    $eqLogic->setIsEnable(1);
                    $eqLogic->setIsVisible(0);
                    $eqLogic->setLogicalId($module['radioId']);
                    $eqLogic->setEqType_name('Diagral_eOne');
                    $eqLogic->setCategory('security', 1);
                    $eqLogic->setConfiguration('type', 'module');
                    $eqLogic->setConfiguration('subtype', $moduleType);
                    $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                    $eqLogic->setConfiguration('index', $module['index']);
                    $nbCreated++;
                } else {
                    log::add('Diagral_eOne', 'info', "Synchronize::Modules Module (".$moduleObject->getName().") mis à jour.");
                    $eqLogic = $moduleObject;
                    if ($eqLogic->getConfiguration('type','') == 'module') {
                        $eqLogic->setName($moduleObject->getName());
                        $eqLogic->setIsEnable($moduleObject->getIsEnable());
                        $eqLogic->setIsVisible($moduleObject->getIsVisible());
                        $eqLogic->setLogicalId($module['radioId']);
                        $eqLogic->setEqType_name('Diagral_eOne');
                        $eqLogic->setCategory('security', 1);
                        $eqLogic->setConfiguration('type', 'module');
                        $eqLogic->setConfiguration('subtype', $moduleType);
                        $eqLogic->setConfiguration('centrale', $system->getLogicalId());
                        $eqLogic->setConfiguration('index', $module['index']);
                    }
                }
                $eqLogic->save();
            }
        }
        return array(
            "type" => "Modules",
            "nbCreated" => $nbCreated,
            "nbUpdated" => $moduleListSum - $nbCreated
        );
    }

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */



    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {

    }

    public function postInsert() {

    }

    public function preSave() {

    }

    public function postSave() {
        // Creation des commandes
        $this->createCmd();
    }

    public function preUpdate() {

    }

    public function postUpdate() {

    }

    public function preRemove() {
        /*
        Supprimer les équipements dépendant de l'équipement à supprimer
        */
        log::add('Diagral_eOne', 'info', ' ┌──── Suppression de l\'équipement ' . $this->getName());
        $eqLogics = eqLogic::byTypeAndSearchConfiguration(  'Diagral_eOne',   'centrale');
        foreach ($eqLogics as $eqLogic) {
            if (!empty($eqLogic->getConfiguration('centrale')) && strcmp($eqLogic->getConfiguration('centrale'), $this->getLogicalId()) == 0) {
                log::add('Diagral_eOne', 'debug', '│  Suppression de l\'équipement ' . $eqLogic->getName() . ' car il est dépendant de l\'équipement en cours de suppression (' . $this->getName() . ')');
                $eqLogic->remove();
            }
        }
    }

    public function postRemove() {

    }

    public function toHtml($_version = 'dashboard') {
        // Si on desactive l'option du template du plugin, alors on repasse en template par defaut (Jeedom)
        if ($this->getConfiguration('templateDiagral') != 1 || $this->getConfiguration('type') != 'centrale')
        {
            return parent::toHtml($_version);
        }

        $replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		$replace['#text_color#'] = $this->getConfiguration('text_color');
        $replace['#version#'] = $_version;
        $this->emptyCacheWidget(); //vide le cache. Pratique pour le développement

        // Traitement des commandes infos
        foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_display#'] = (is_object($cmd) && $cmd->getIsVisible()) ? '#' . $cmd->getLogicalId() . '_display#' : "none";
            $replace['#' . $cmd->getLogicalId() . '_name_display#'] = ($cmd->getDisplay('icon') != '') ? $cmd->getDisplay('icon') : $cmd->getName();
            $replace['#' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
            $replace['#' . $cmd->getLogicalId() . '_unite#'] = $cmd->getUnite();
            $replace['#' . $cmd->getLogicalId() . '_hide_name#'] = '';
            $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
            $replace['#' . $cmd->getLogicalId() . '_version#'] = $_version;
            $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#' . $cmd->getLogicalId() . '_uid#'] = 'cmd' . $cmd->getId() . eqLogic::UIDDELIMITER . mt_rand() . eqLogic::UIDDELIMITER;
            $replace['#' . $cmd->getLogicalId() . '_collectDate#'] = $cmd->getCollectDate();
            $replace['#' . $cmd->getLogicalId() . '_valueDate#'] = $cmd->getValueDate();
            $replace['#' . $cmd->getLogicalId() . '_alertLevel#'] = $cmd->getCache('alertLevel', 'none');
            if ($cmd->getIsHistorized() == 1) {
                $replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
            }
            if ($cmd->getDisplay('showNameOn' . $_version, 1) == 0) {
                $replace['#' . $cmd->getLogicalId() . '_hide_name#'] = 'hidden';
            }
        }

        // Traitement des commandes actions
        foreach ($this->getCmd('action') as $cmd) {
            $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
            if ($cmd->getSubType() == 'select') {
                $listValue = "<option value>" . $cmd->getName() . "</option>";
                $listValueArray = explode(';', $cmd->getConfiguration('listValue'));
                foreach ($listValueArray as $value) {
                    list($id, $name) = explode('|', $value);
                    $listValue = $listValue . "<option value=" . $id . ">" . $name . "</option>";
                }
                $replace['#' . $cmd->getLogicalId() . '_listValue#'] = $listValue;
            }
        }

        $replace['#systemID#'] = $this->getConfiguration('systemid');

        // On defini le template a appliquer par rapport à la version Jeedom utilisée
        if (version_compare(jeedom::version(), '4.4.0') >= 0) {
            $template = 'eqLogic';
        } elseif (version_compare(jeedom::version(), '4.0.0') >= 0) {
            $template = 'eqLogic4-0';
        } else {
            $template = 'eqLogic3';
        }
        $replace['#template#'] = $template;

        return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, $template, 'Diagral_eOne')));
    }

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */





    /* ------------------------------ Creations des commandes Jeedom ------------------------------ */


    /**
     * Creation des commandes pour l'equipement
     */
    private function createCmd() {
        // Definition et chargement du fichier de configuration globale qui inclus notament les commandes
        $filename = __PLGBASE__.'/core/config/cmdConfig/'. $this->getConfiguration('type') .'.config.json';
        try {
            $config = $this->loadConfigFile($filename, 'commands');
        } catch(Exception $e){
            if (preg_match('/Impossible de trouver le fichier de configuration .*/', $e)) {
                $message = 'Le ou l\'un des modules sauvegardés n\'est pas supporté (consulter les logs DEBUG pour plus de détails sur le module concerné). Veuillez vous référer à la documentation https://mguyard.github.io/Jeedom-Documentations/fr_FR/Diagral_eOne/documentation#Equipements%20support%C3%A9s';
                log::add('Diagral_eOne', 'warning', $message);
                throw new Exception($message);
            }
        }
        // Attribue les valeurs par defaut d'un device
        $eqLogicConf = $config['eqLogic'];
        log::add('Diagral_eOne', 'debug', 'createCmd::EqTemplate ' . var_export($eqLogicConf, true));
        utils::a2o($this, $eqLogicConf);
        // Boucle de génération des commandes
        foreach ($config['commands'] as $key => $command) {
            $newCmd = false;
            $cmd = $this->getCmd(null, $command['logicalId']);
            // Si la commande n'existe pas deja
            if (!is_object($cmd)) {
                $newCmd = true;
                $cmd = new Diagral_eOneCmd();
                $cmd->setName(__($command['name'], __FILE__));
            }
            // Le parametre JSON masterCodeNeed n'existe pas ou est à false ou bien que le MasterCode est rempli
            if (! isset($command['masterCodeNeed']) || $command['masterCodeNeed'] === false || ! empty($this->getConfiguration('mastercode'))) {
                $cmd->setOrder($key);
                $cmd->setEqLogic_id($this->getId());
                // Si un parametre function est fournit a la commande
                if( isset($command['configuration']['function'])) {
                    list($fieldType, $fieldFunction)= explode("::", $command['configuration']['function']);
                    log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::' . $command['logicalId'] . ' ' . $fieldType . ' with function ' . $fieldFunction);
                    if (is_callable(array(get_class($this), $fieldFunction))) {
                        log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::' . $command['logicalId'] . 'VerifyFunctionCallable ' . $fieldFunction . ' TRUE');
                        $contentField = call_user_func(array(get_class($this), $fieldFunction));
                        $parsedContent = "";
                        switch ($fieldType) {
                            case 'listValue':
                                $parsedContent = $this->generateSelect($contentField);
                                break;
                        }
                        log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::GetReturnFunction ' . $parsedContent);
                        $command['configuration'][$fieldType] = $parsedContent;
                        unset($command['configuration']['function']);
                        log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::NewCommand ' . var_export($command, true));
                    } else {
                        log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::VerifyFunctionCallable ' . $fieldFunction . ' FALSE');
                    }
                }
                utils::a2o($cmd, $command);
                $cmd->save();
                if ($newCmd === true) {
                    log::add('Diagral_eOne', 'info', 'postSave::createCmd '.$command['logicalId'].' ('.$command['name'].')');
                } else {
                    log::add('Diagral_eOne', 'info', 'postSave::updateCmd '.$command['logicalId'].' ('.$command['name'].') with order ' . $key);
                }
                if (isset($command['homebridge'])) {
                    $this->applyHomeBridgeConf($command['logicalId'], $command['homebridge']);
                }
            } else {
                log::add('Diagral_eOne', 'info', 'postSave::bypassCmd '.$command['logicalId'].' ('.$command['name'].')');
            }
        }
        // Sauvegarde l'EqLogic
        $this->save(true);
    }

    /**
     * Applique les configurations necessaire pour HomeBridge
     * @param string $logicalId nom de la commande sur laquelle appliquer Homebridge Alarm
     * @param array $homebridgeConf contient la configuration Homebridge a appliquer
     * @return void
     */
    private function applyHomeBridgeConf($logicalId, $homebridgeConf) {
        $cmd = $this->getCmd(null, $logicalId);
        // Verifie si la commande existe
        if (!is_object($cmd)) {
            throw new Exception('La commande sur laquelle HomeBridge doit être activé (' . $logicalId . ') n\'existe pas.');
        }
        $homebridge_mode = $homebridgeConf['homebridge_mode'];
        $mode_status = $homebridgeConf['mode_status'];
        // Verifie que les parametres necessaires sont bien présents.
        if (! empty($homebridge_mode) && ! empty($mode_status)) {
            $this->setConfiguration($homebridge_mode,$cmd->getId()."|".$mode_status);
            log::add('Diagral_eOne', 'debug', 'HomeBridge::applyCommand ' . $homebridge_mode . ' / ' . $mode_status . ' / ' . $cmd->getId());
            log::add('Diagral_eOne', 'debug', 'HomeBridge::EqConfiguration ' . var_export($this->getConfiguration(), true));
        } else {
            log::add('Diagral_eOne', 'debug', 'HomeBridge::alert Unable to activate Homebridge on Cmd ' . $logicalId . ' because configuration in JSON isn\'t complete.');
        }
    }




    /* ------------------------------ Generate des JSON ------------------------------ */


    /**
     * Charge la configuration globale du plugin.
     * @param string $filename          Nom et Chemin du fichier a lire
     * @param string $required_path     Specifie le parametre a charger dans le fichier pour valider sa presence
     * @return array                    tableau de tout les parametres
     */
    private function loadConfigFile($filename, $required_path) {
        log::add('Diagral_eOne', 'debug', 'loadConfigFile::'.$filename);
        if ( file_exists($filename) === false ) {
            log::add('Diagral_eOne', 'error', 'Impossible de trouver le fichier de configuration \'' . $filename . '\'');
            throw new Exception('Impossible de trouver le fichier de configuration \'' . $filename . '\'');
        }
        $content = file_get_contents($filename);
        if (!is_json($content)) {
            log::add('Diagral_eOne', 'error', 'Le fichier de configuration \'' . $filename . '\' est corrompu');
            throw new Exception('Le fichier de configuration \'' . $filename . '\' est corrompu');
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data[$required_path])) {
            log::add('Diagral_eOne', 'error', 'Le fichier de configuration \'' . $filename . '\' est invalide');
            throw new Exception('Le fichier de configuration \'' . $filename . '\' est invalide');
        }
        log::add('Diagral_eOne', 'debug', 'Contenu du fichier \'' . $filename . '\' ' . var_export($data, true));
        return $data;
    }

    /**
     * Ecrit des fichiers JSON de configuration
     * @param string $filename  Nom et Chemin du fichier a ecrire
     * @param string $content   Contenu du fichier
     */
    private function writeConfigFile($filename, $content) {
        log::add('Diagral_eOne', 'debug', 'writeConfigFile::'.$filename);
        if (file_put_contents($filename, $content, LOCK_EX)) {
            log::add('Diagral_eOne', 'info', 'writeConfigFile::Success '. $filename);
        } else {
            log::add('Diagral_eOne', 'error', 'writeConfigFile::Failed ' . $filename);
        }
    }

    /**
     * Generation d'un JSON par equipement actif dans Jeedom. Lancé via la CRON
     */
    public function generateJsonAllDevices() {
        foreach (eqLogic::byType('Diagral_eOne', true) as $eqLogic) {
            if ($eqLogic->getConfiguration('type') == 'centrale') {
                $cmdGroup = $eqLogic->getCmd(null, 'force_groups_refresh_json');
                $cmdGroup->execute();
                $cmdScenario = $eqLogic->getCmd(null, 'force_scenarios_refresh_json');
                $cmdScenario->execute();
                $eqLogic->save();
            }
        }
    }

    /**
     * Generation des groupes Diagral dans un fichier JSON pour un object specifique $this
     */
    public function generateGroupJson($MyAlarm) {
        log::add('Diagral_eOne', 'debug', 'generateGroupJson::Start');
        $filename = __PLGBASE__.'/data/groups_' . $this->getConfiguration('systemid') . '.json';
        // Recuperation de l'ensemble des groupes
        $groups = $MyAlarm->getAllGroups();
        $groupsJSON = array();
        // Mise en tableau des groupes ainsi que les ID
        foreach ($groups as $groupId => $groupName) {
            array_push($groupsJSON, array("groupID" => ++$groupId, "groupName" => $groupName));
        }
        log::add('Diagral_eOne', 'debug', 'generateGroupJson::Result ' . var_export(json_encode($groupsJSON), true));
        $this->writeConfigFile($filename, json_encode(array('lastModified' => date("Y-m-d H:i:s"), 'groups' => $groupsJSON),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        // Refresh des commandes pour s'assurer que les updates de groups sont pris en compte
        $this->createCmd();
    }

    /**
     * Generation des scénarios dans un fichier JSON pour un objet specifique $this
     */
    public function generateScenariosJson($MyAlarm) {
        log::add('Diagral_eOne', 'debug', 'generateScenariosJson::Start');
        $filename = __PLGBASE__.'/data/scenarios_' . $this->getConfiguration('systemid') . '.json';
        // Recuperation de l'ensemble des scenarios
        $scenarios = $MyAlarm->getScenarios();
        log::add('Diagral_eOne', 'debug', 'generateScenariosJson::ListScenarios' . var_export($scenarios, true));
        log::add('Diagral_eOne', 'debug', 'generateScenariosJson::' . $this->getConfiguration('systemid') . '::Success');
        $this->writeConfigFile($filename, json_encode(array('lastModified' => date("Y-m-d H:i:s"), 'scenarios' => $scenarios),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_FORCE_OBJECT));
        // Refresh des commandes pour s'assurer que les updates de scenarios sont pris en compte
        $this->createCmd();
    }


    /* ------------------------------ Generation des ListValue ------------------------------ */


    /**
     * Genere une ListValue formaté a partir d'un tableau
     * @return string Liste formatée en select pour les actions
     * @return $listSelect     String de liste select ListValue
     */
    private function generateSelect($selectArray) {
        log::add('Diagral_eOne', 'debug', 'generateSelect::Start');
        $listSelect = "";
        log::add('Diagral_eOne', 'debug', 'generateSelect::Return ' . var_export($selectArray, true));
        foreach ($selectArray as $key => $value) {
            // Recupere la position de la clé dans le tableau
            $position = array_search($key, array_keys($selectArray));
            if ($position > 0) {
                // Si la position est superieur à 0 (donc au moins la seconde position) alors on ajoute un ; comme séparateur avant le contenu
                $listSelect .= ';';
            }
            $listSelect .= $key . '|' . $value;
        }
        return $listSelect;
    }

    /**
     * Genere un Liste des groupes de Zone possible
     * @return array Tableau contenant l'ensemble des zones Diagral de l'alarme
     */
    public function generateGroupsList($MyAlarm = NULL) {
        log::add('Diagral_eOne', 'debug', 'generateGroupsList::Start');
        $filename = __PLGBASE__.'/data/groups_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            if (is_null($MyAlarm) || ! is_object($MyAlarm)) {
                $MyAlarm = $this->setDiagralEnv();
            }
            $this->generateGroupJson($MyAlarm);
        }
        // Recuperation de l'ensemble des groups avec leur nom et leur ID
        $config = $this->loadConfigFile($filename, 'groups');
        $groups = array();
        foreach ($config['groups'] as $group) {
            // Creation d'un tableau ou la clé = idgroup et contenu groupName
            $groups[$group['groupID']] = $group['groupName'];
        }
        log::add('Diagral_eOne', 'debug', 'generateGroupsList::Content ' . var_export($groups, true));
        return $groups;
    }

    /**
     * Genere la liste des combinaisons de Zones possible
     * @return array Tableau contenant un tableau pour chaque combinaison possible.
     */
    private function generateGroupsPossibilities() {
        log::add('Diagral_eOne', 'debug', 'generateGroupsPossibilities::Start');
        $filename = __PLGBASE__.'/data/groups_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $MyAlarm = $this->setDiagralEnv();
            $this->generateGroupJson($MyAlarm);
        }
        // Recuperation de l'ensemble des groups avec leur nom et leur ID
        $config = $this->loadConfigFile($filename, 'groups');
        $groups = array();
        foreach ($config['groups'] as $group) {
            $groups[] = $group['groupName'];
        }
        log::add('Diagral_eOne', 'debug', 'generateGroupsPossibilities::GroupList ' . var_export($groups, true));
        // Generation de l'ensemble des combinaisons possible (un tableau contenant pour chaque combinaison, une autre tableau avec une zone par entrée)
        $allZoneCombination = array(array( ));
        foreach ($groups as $element)  {
            foreach ($allZoneCombination as $combination) {
                array_push($allZoneCombination, array_merge(array($element), $combination));
            }
        }
        // Suppression du premier tableau vide
        array_shift($allZoneCombination);
        // Parcours du tableau afin de me mettre chaque combinaison possible dans une entrée d'un nouveau tableau (en séparant chaque zone par un +)
        foreach ($allZoneCombination as $listCombinaison) {
            log::add('Diagral_eOne', 'debug', 'generateGroupsPossibilities::Possibility ' . var_export($listCombinaison, true));
            $FinalCombination[] = implode(" + ", $listCombinaison);
        }
        // Tri du tableau selon la longueur des valeurs (pour faire un plus bel affichage)
        array_multisort(array_map('strlen', $FinalCombination), $FinalCombination);
        log::add('Diagral_eOne', 'debug', 'generateGroupsPossibilities::GroupedPossibilities ' . var_export($FinalCombination, true));
        return $FinalCombination;
    }

    /**
     * Genere la liste des scénarios disponibles
     * @return array    Liste des scénarios
     */
    private function generateScenariosPossibilities() {
        log::add('Diagral_eOne', 'debug', 'generateScenariosPossibilities::Start');
        $filename = __PLGBASE__.'/data/scenarios_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $MyAlarm = $this->setDiagralEnv();
            $this->generateScenariosJson($MyAlarm);
        }
        // Recuperation de l'ensemble des groups avec leur nom et leur ID
        $config = $this->loadConfigFile($filename, 'scenarios');
        $scenarios = array();
        foreach ($config['scenarios'] as $scenarioName => $scenario) {
            $scenarios[] = $scenarioName;
        }
        log::add('Diagral_eOne', 'debug', 'generateScenariosPossibilities::Possibilities ' . var_export($scenarios, true));
        return $scenarios;
    }



    /* ------------------------------ Verifications du plugin ------------------------------ */



    /**
     * Verification des configurations du plugins
     */
    public function checkConfig() {
        // Checking Username
        log::add('Diagral_eOne', 'debug', 'checkConfig::login::Start');
        if ( ! empty(config::byKey('login', 'Diagral_eOne'))) {
            if(!filter_var(config::byKey('login', 'Diagral_eOne'), FILTER_VALIDATE_EMAIL)){
                throw new Exception(__('L\'adresse email utilisé en identifiant à un format invalide.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::login OK with value ' . config::byKey('login', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::login Default Value');
        }
        // Checking Password
        log::add('Diagral_eOne', 'debug', 'checkConfig::password::Start');
        if ( empty(config::byKey('password', 'Diagral_eOne'))) {
            throw new Exception(__('Le mot de passe doit être rempli.', __FILE__));
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::password OK with value ************');
        }
        // Checking Retry
        log::add('Diagral_eOne', 'debug', 'checkConfig::retry::Start');
        if ( ! empty(config::byKey('retry', 'Diagral_eOne'))) {
            if(!filter_var(config::byKey('retry', 'Diagral_eOne'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 10)))){
                throw new Exception(__('Le nombre de tentative en cas d\'echec est invalide. Elle doit contenir un nombre (entier) compris entre 1 et 10.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::retry OK with value ' . config::byKey('retry', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::retry Default Value');
        }
        // Checking waitRetry
        log::add('Diagral_eOne', 'debug', 'checkConfig::waitRetry::Start');
        if ( ! empty(config::byKey('waitRetry', 'Diagral_eOne'))) {
            if(!filter_var(config::byKey('waitRetry', 'Diagral_eOne'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 5)))){
                throw new Exception(__('Le nombre de minutes entre les tentatives est invalide. Elle doit contenir un nombre (entier) de secondes superieur à 5.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::waitRetry OK with value ' . config::byKey('waitRetry', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::waitRetry Default Value');
        }
        //Checking polling interval
        log::add('Diagral_eOne', 'debug', 'checkConfig::polling_interval::Start');
        if ( ! empty(config::byKey('polling_interval', 'Diagral_eOne'))) {
            if(!filter_var(config::byKey('polling_interval', 'Diagral_eOne'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 10)))){
                throw new Exception(__('La frequence de mise à jour (polling) est invalide. Elle doit contenir un nombre (entier) de minutes superieur ou égale à 10.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::polling_interval OK with value ' . config::byKey('polling_interval', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::polling_interval Default Value');
        }
        //Checking Video Retention
        log::add('Diagral_eOne', 'debug', 'checkConfig::video_retention::Start');
        if ( ! empty(config::byKey('video_retention', 'Diagral_eOne'))) {
            if(!filter_var(config::byKey('video_retention', 'Diagral_eOne'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))){
                throw new Exception(__('Le nombre de video a conserver est invalide. Elle doit contenir un nombre (entier) superieur a 1.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::video_retention OK with value ' . config::byKey('video_retention', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::video_retention Default Value (100)');
            config::save('video_retention', '100', 'Diagral_eOne');
        }
        // Checking InstallBaseEmailAddr
        log::add('Diagral_eOne', 'debug', 'checkConfig::InstallBaseEmailAddr::Start');
        if ( ! empty(config::byKey('InstallBaseEmailAddr', 'Diagral_eOne'))) {
            if(!filter_var(config::byKey('InstallBaseEmailAddr', 'Diagral_eOne'), FILTER_VALIDATE_EMAIL)){
                throw new Exception(__('L\'adresse email utilisé pour les communication à un format invalide.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::InstallBaseEmailAddr OK with value ' . config::byKey('InstallBaseEmailAddr', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::InstallBaseEmailAddr Default Value');
        }
    }


    /* ------------------------------ Lancement d'actions sur le Cloud Diagral ------------------------------ */



    /**
     * Genere l'environnement Diagral inclus le login, la recuperation de la configuration ainsi que l'entrée dans le systeme
     * @return object   $MyAlarm
     */
    public function setDiagralEnv() {
        log::add('Diagral_eOne', 'debug', 'setDiagralEnv::' . $this->getConfiguration('systemid') . '::Start Diagral Environnement');
        if ( ! empty($this->getConfiguration('mastercode'))) {
            $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'), config::byKey('plugin_version', 'Diagral_eOne'));
            $MyAlarm->verbose = config::byKey('verbose', 'Diagral_eOne');
            if ( ! empty(config::byKey('retry', 'Diagral_eOne'))) {
                $MyAlarm->doRequestAttempts = config::byKey('retry', 'Diagral_eOne');
            }
            if ( ! empty(config::byKey('waitRetry', 'Diagral_eOne'))) {
                $MyAlarm->waitBetweenAttempts = config::byKey('waitRetry', 'Diagral_eOne');
            }
            $MyAlarm->login();
            $MyAlarm->getSystems();
            $MyAlarm->setSystemId(intval($this->getConfiguration('systemid')));
            $MyAlarm->getConfiguration();
            $MyAlarm->connect($this->getConfiguration('mastercode'));
            // Recupere le nombre de mises à jour disponibles
            $nbUpdates = $MyAlarm->getFirmwareUpdates();
            log::add('Diagral_eOne', 'debug', 'setDiagralEnv::UpdateAvailable : '.$nbUpdates);
            log::add('Diagral_eOne', 'debug', 'setDiagralEnv::getVersions : '.var_export($MyAlarm->versions, true));
            $this->checkAndUpdateCmd('updates_available', $nbUpdates);
            return $MyAlarm;
        } else {
            throw new Exception("MasterCode of device ".$this->getName()." cannot be empty. Please configure it in your device.");
        }
    }

    /**
     * Recuperation automatique (cron) du statut de l'ensemble des équipements actifs
     */
    public static function pull() {
        $changed = false;
        log::add('Diagral_eOne', 'debug', 'pull::Starting Request');
        // Place un temps d'attente pour éviter que tout les utilisateurs ne se connecte en même temps chez Diagral
        $delay = random_int(0,10);
        if ($delay > 0) {
            log::add(__CLASS__, 'debug', __('CRON - Ajout d\'un délai d\'attente aléatoire de', __FILE__) . ': ' . strval($delay) . ' ' . __('secondes', __FILE__));
            sleep($delay); 
        }
        // Recuperer la liste des centrale
        log::add('Diagral_eOne', 'debug', 'pull::ListofCentrale ' . var_export(eqLogic::byTypeAndSearchConfiguration('Diagral_eOne', 'systemid'), true));
        foreach (eqLogic::byTypeAndSearchConfiguration('Diagral_eOne', 'systemid') as $centrale) {
            log::add('Diagral_eOne', 'debug', 'pull::Centrale ' . $centrale->getName());
            if($centrale->getIsEnable() && strlen($centrale->getConfiguration('systemid')) != 0) {
                //Se connecter sur la centrale
                $MyAlarm = $centrale->setDiagralEnv();
                // Récupérer la liste de toutes les alertes
                $allAlerts = $centrale->getAlerts($MyAlarm, TRUE);
                // Rafraichi la centrale
                $centrale->deviceRefresh($MyAlarm, $allAlerts['centralStatus']);
                foreach (eqLogic::byTypeAndSearchConfiguration('Diagral_eOne', 'centrale') as $centralChild) {
                    log::add('Diagral_eOne', 'debug', 'pull::CentraleChild ' . $centralChild->getName());
                    // Si le device enfant a bien la centrale en parent
                    if ($centralChild->getConfiguration('centrale') == $centrale->getLogicalId()) {
                        switch($centralChild->getConfiguration('type')) {
                            case 'module':
                                foreach ($allAlerts[$centralChild->getConfiguration('subtype').'Status'] as $moduleAlert) {
                                    if ($moduleAlert['index'] == $centralChild->getConfiguration('index')) {
                                        $centralChild->deviceRefresh($MyAlarm, $moduleAlert);
                                    }
                                }
                                break;
                            default:
                                $centralChild->deviceRefresh($MyAlarm);
                        }
                    }
                }
                $MyAlarm->logout();
            } else {
                log::add('Diagral_eOne', 'debug', 'pull::Centrale ' . $centrale->getName() . ' is not enable (enabled:'.$centrale->getIsEnable().') or don\'t have systemid configuration (systemID: '.$centrale->getConfiguration('systemid').').');

            }
        }
        // Purge Videos Retention
        Diagral_eOne::purgeVideos();
        // Send data informations for installation follow
        Diagral_eOne::installTracking();
    }


    public function deviceRefresh($MyAlarm, $alert = "") {
        $changed = false;
        log::add('Diagral_eOne', 'info', 'deviceRefresh::Start Refresh de l\'équipement '.$this->getName());
        switch ($this->getConfiguration('type', '')) {
            case 'centrale':
                $alarmStatus = $this->getDiagralStatus($MyAlarm);
                $changed = $this->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                $changed = $this->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                $changed = $this->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                // Stockage de la batterie
                if ($alert['mainPowerSupplyAlert'] === TRUE || $alert['secondaryPowerSupplyAlert'] === TRUE) {
                    $changed = $this->checkAndUpdateCmd('battery', TRUE) || $changed;
                    $this->batteryStatus(20);
                } else {
                    $changed = $this->checkAndUpdateCmd('battery', FALSE) || $changed;
                    $this->batteryStatus(100);
                }
                // Stockage de l'autoProtection
                if ($alert['autoprotectionMechanicalAlert'] === TRUE || $alert['autoprotectionWiredAlert'] === TRUE) {
                    $this->checkAndUpdateCmd('autoprotectionAlert', TRUE);
                } else {
                    $this->checkAndUpdateCmd('autoprotectionAlert', FALSE);
                }
                // Stockage de defaultMediaAlert
                $this->checkAndUpdateCmd('MediaAlert', $alert['defaultMediaAlert']);
                break;
            case 'module':
                // Stockage de la batterie
                if ($this->getConfiguration('subtype', '') == 'transmitters') {
                    if ($alert['mainPowerSupplyAlert'] === TRUE || $alert['secondaryPowerSupplyAlert'] === TRUE) {
                        $this->checkAndUpdateCmd('battery', TRUE);
                        $this->batteryStatus(20);
                    } else {
                        $this->checkAndUpdateCmd('battery', FALSE);
                        $this->batteryStatus(100);
                    }
                } else {
                    $batteryAlert = $alert['powerSupplyAlert'];
                    $this->checkAndUpdateCmd('battery', $alert['powerSupplyAlert']);
                    if ($alert['powerSupplyAlert'] === TRUE) {
                        $this->batteryStatus(20);
                    } else {
                        $this->batteryStatus(100);
                    }
                }
                
                // Stockage de l'autoProtection
                $this->checkAndUpdateCmd('autoprotectionMechanicalAlert', $alert['autoprotectionMechanicalAlert']);
                // Stockage de radioAlert
                $this->checkAndUpdateCmd('radioAlert', $alert['radioAlert']);
                break;
            case 'knx-shutter':
                // Recuperation de la centrale
                $centrale = eqLogic::byLogicalId($this->getConfiguration('centrale'), 'Diagral_eOne');
                // Recuperation de la position
                $position = intval($this->automationKNXgetValue($MyAlarm, $centrale));
                // Stockage de la position
                $changed = $this->checkAndUpdateCmd('positionState', $position) || $changed;
                break;
            case 'knx-light':
                // Recuperation de la centrale
                $centrale = eqLogic::byLogicalId($this->getConfiguration('centrale'), 'Diagral_eOne');
                // Recuperation de la position
                $value = $this->automationKNXgetValue($MyAlarm, $centrale);
                switch($value) {
                    case 'ON':
                        $status = 1;
                        break;
                    case 'OFF':
                        $status = 0;
                        break;
                }
                // Stockage de la position
                $changed = $this->checkAndUpdateCmd('status', $status) || $changed;
                break;
        }
        return $changed;
    }


    /**
     * Recupere le statut de l'alarme
     * @return array (state => etat binaire de l'alarme / mode => mode actuel de l'alarme / groups => groupes actif)
     */
    public function getDiagralStatus($MyAlarm) {
        // Stock l'actuelle valeur du status pour pouvoir le comparer avec le nouveau statut plus tard
        $lastStatus = $this->getCmd(null, 'mode')->execCmd();
        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm->getAlarmStatus();
        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::' . $this->getConfiguration('systemid') . '::Result ' . var_export($MyAlarm->systemState, true) );
        if ( strcmp($MyAlarm->systemState, 'off') !== 0) {
            $filename = __PLGBASE__.'/data/groups_' . $this->getConfiguration('systemid') . '.json';
            // Si le fichier JSON des groupes n'existe pas, on le genère.
            if ( file_exists($filename) === false ) {
                $this->generateGroupJson($MyAlarm);
            }
            // Recuperation de l'ensemble des groups avec leur nom et leur ID
            $config = $this->loadConfigFile($filename, 'groups');
            // Recupération des groupes actif de l'alarme et affichage de l'état de l'alarme
            foreach ($MyAlarm->groups as $key => $groupID) {
                log::add('Diagral_eOne', 'debug', 'getDiagralStatus::searchingGroupID ' . $groupID);
                $groupArrayKey = array_search($groupID, array_column($config['groups'], 'groupID'));
                log::add('Diagral_eOne', 'debug', 'getDiagralStatus::groupIDsFindInArrayWithKey ' . var_export($groupArrayKey, true));
                $MyAlarm->groups[$key] = $config['groups'][$groupArrayKey]['groupName'];
            }
            $groups = implode(' + ', $MyAlarm->groups);
            log::add('Diagral_eOne', 'debug', 'getDiagralStatus::GroupsEnable ' . var_export($MyAlarm->groups, true));
            // Si une alarme est active
            if ($this->isAlarmActive()) {
                // On compare la zone declencheur de l'alarme et les zone actives
                $trigger = $this->getAlarmTrigger();
                if (! empty($trigger['zone'])) {
                    $zoneMatch = array_search(strtolower($trigger['zone']), array_map('strtolower', $MyAlarm->groups));
                    // Si la zone declencheur de l'alarme est toujours active, alors on conserve le status de l'alarme sur "alarm"
                    if ($zoneMatch !== false) {
                        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::AlarmTrigger Alarme Trigger zone (' . $trigger['zone'] . ') is enable. Conserve alarm status');
                        $MyAlarm->systemState = "alarm";
                    }
                } else {
                    log::add('Diagral_eOne', 'debug', 'getDiagralStatus::AlarmTrigger Alarm is enable but no trigger detected. It seens to be a false positive. Reset alarm to 0');
                    $this->checkAndUpdateCmd('alarm', 0);
                }
            }
            $alarmState = 1;
        } else {
            $groups = "";
            // Si une alarme est active et que le dernier statut de l'alarme est off, on repasse supprime l'alarme active
            if ($this->isAlarmActive()) {
                $this->checkAndUpdateCmd('alarm', 0);
                log::add('Diagral_eOne', 'debug', 'getDiagralStatus::Alarm set to 0 due to complete desactivation following an alarm alert');
            }
            $alarmState = 0;
        }
        return array(
            "status" => $alarmState,
            "mode" => $MyAlarm->systemState,
            "groups" => $groups
        );
    }

    /**
     * Fonction de desactivaton totale de l'alarme
     */
    public function setCompleteDesactivation($MyAlarm) {
        log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm->completeDesactivation();
        log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::' . $this->getConfiguration('systemid') . '::Success');
        // Si une alarme est en cours
        if ($this->isAlarmActive()) {
            $this->checkAndUpdateCmd('alarm', 0);
            log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::Alarm set to 0 due to complete desactivation following an alarm alert');
        }
    }

    /**
     * Fonction d'activation complete de l'alarme
     */
    public function setCompleteActivation($MyAlarm) {
        log::add('Diagral_eOne', 'debug', 'setCompleteActivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm->completeActivation();
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $this->getConfiguration('systemid') . '::Success');
    }

    /**
     * Fonction d'activation du mode presence
     * @param int $systemId ID de l'alarme sur le compte Diagral
     */
    public function setPresenceActivation($MyAlarm) {
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm->presenceActivation();
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $this->getConfiguration('systemid') . '::Success');
    }


    /**
     * Activation partielle de l'alarme
     * @param int $cmdValue         ID du listValue recu en parametre de l'execution de la commande
     * @param array $listValue      listValue configuré sur la commande
     */
    public function setPartialActivation($MyAlarm, $cmdValue, $listValue) {
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::cmdValue ' . $cmdValue);
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::ListValue ' . var_export($listValue, true));
        $filename = __PLGBASE__.'/data/groups_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $this->generateGroupJson($MyAlarm);
        }
        // Recuperation de l'ensemble des groups avec leur nom et leur ID
        $config = $this->loadConfigFile($filename, 'groups');
        // Recuperation de l'ensemble de la listValue dans une tableau
        $listValue = explode(';', $listValue);
        // Découpage de chaque element du listValue pour ne garder que le nom des groupes
        foreach ($listValue as $key => $value) {
            $listValue[$key] = substr($value, strpos($value, '|') + 1 );
        }
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::ListValue::AfterManipulation ' . var_export($listValue, true));
        // Découpage dans un tableau des groupes a partir de l'ID recu en paramètre ($cmdValue)
        $groups = explode(' + ',$listValue[$cmdValue]);
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::cmdGroupsNameReceive ' . var_export($groups, true) );
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::listGroupJSON ' . var_export($config['groups'], true) );
        // Pour chacun des groupes recu en parametre, on recherche l'ID de groupe Diagral
        foreach ($groups as $key => $group) {
            log::add('Diagral_eOne', 'debug', 'setPartialActivation::searchingGroupName ' . $group);
            $groupArrayKey = array_search($group, array_column($config['groups'], 'groupName'));
            log::add('Diagral_eOne', 'debug', 'setPartialActivation::groupNameFindInArrayWithKey ' . var_export($groupArrayKey, true));
            $groups[$key] = $config['groups'][$groupArrayKey]['groupID'];
        }
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::groupToEnableWithGroupID ' . var_export($groups, true));
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        // Execution de la demande de mise en activation partielle avec les ID Diagral
        $MyAlarm->partialActivation($groups);
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::' . $this->getConfiguration('systemid') . '::Success');
    }

    /**
     * Desactivation partielle de l'alarme (une seule zone à la fois)
     * @param int $cmdValue  GroupID de la zone (ID tels que connu par Diagral)
     * @return boolean du statut de l'action
     */
    public function setPartialDesactivation($MyAlarm, $cmdValue) {
        log::add('Diagral_eOne', 'debug', 'setPartialDesactivation::cmdValue ' . $cmdValue);
        try {
            $MyAlarm->partialDesactivation($cmdValue);
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'setPartialDesactivation::' . $this->getConfiguration('systemid') . '::Failed for groupID ' . $cmdValue . '. Reason : ' . $e->getMessage());
            return FALSE;
        }
        log::add('Diagral_eOne', 'debug', 'setPartialDesactivation::' . $this->getConfiguration('systemid') . '::Success for groupID ' . $cmdValue);
        // Si une alarme est en cours
        if ($this->isAlarmActive()) {
            $trigger = $this->getAlarmTrigger();
            // Recuperation de la liste des zones existante
            $listAlarmZone = $this->generateGroupsList($MyAlarm);
            $zoneNameDisabled = $listAlarmZone[$cmdValue];
            // Comparaison de la zone d'alarme et de la zone qui vient d'être désactivé
            $result = strpos(strtolower($trigger['zone']), strtolower($zoneNameDisabled));
            // La Zone d'alarme correspond à la zone desactivé
            if ($result !== false) {
                $this->checkAndUpdateCmd('alarm', 0);
                log::add('Diagral_eOne', 'debug', 'setPartialDesactivation::Alarm set to 0. Alarm was triggered by "' . $trigger['zone'] . '" and this zone is now disable (' . $zoneNameDisabled . ')');
            }
        }
        return TRUE;
    }

    /**
     * Lancement de scenarios
     * @param int $cmdValue         ID du listValue recu en parametre de l'execution de la commande
     * @param array $listValue      listValue configuré sur la commande
     */
    public function setScenario($MyAlarm, $cmdValue, $listValue) {
        log::add('Diagral_eOne', 'debug', 'setScenario::cmdValue ' . $cmdValue);
        log::add('Diagral_eOne', 'debug', 'setScenario::ListValue ' . var_export($listValue, true));
        $filename = __PLGBASE__.'/data/scenarios_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des scenarios n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $this->generateScenariosJson($MyAlarm);
        }
        // Recuperation de l'ensemble des scenarios
        $config = $this->loadConfigFile($filename, 'scenarios');
        // Recuperation de l'ensemble de la listValue dans une tableau
        $listValue = explode(';', $listValue);
        // Découpage de chaque element du listValue pour ne garder que le nom des scenarios
        foreach ($listValue as $key => $value) {
            $listValue[$key] = substr($value, strpos($value, '|') + 1 );
        }
        log::add('Diagral_eOne', 'debug', 'setScenario::ListValue::AfterManipulation ' . var_export($listValue, true));
        $scenarioID = $config['scenarios'][$listValue[$cmdValue]][0]['id'];
        log::add('Diagral_eOne', 'debug', 'setScenario::ScenarioToEnableWithScenarioID ' . $scenarioID);
        // Execution de la demande de mise en activation partielle avec les ID Diagral
        $MyAlarm->launchScenario($scenarioID);
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setScenario::' . $this->getConfiguration('systemid') . '::Success ' . $listValue[$cmdValue]);
    }


    /**
     * Fonction de récuperation des Events
     */
    public function getEvents($id, $startDate = "2010-01-01 00:00:00", $endDate = null) {
        $eqLogic = eqLogic::byId($id);
        log::add('Diagral_eOne', 'debug', 'getEvents::' . $eqLogic->getConfiguration('systemid') . '::Starting Request');
        try {
            $MyAlarm = $eqLogic->setDiagralEnv();
            $MyAlarm->setEventsRetry(100);
            $events = $MyAlarm->getEvents($startDate,$endDate);
            $MyAlarm->logout();
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'getEvents::' . $eqLogic->getConfiguration('systemid') . '::Failed. Reason : ' . $e->getMessage());
        }
        log::add('Diagral_eOne', 'debug', 'getEvents::' . $eqLogic->getConfiguration('systemid') . '::Success');
        return $events;
    }


    /* ------------------------------ Produit Tiers integré Diagral e-One - Detecteur Image ------------------------------ */


    /**
     * Fonction pour se connecter sur la centrale a partir d'un detecteur/cameras/etc..
     * @return object   Object Diagral
     */
    public function getCentrale() {
        // $this = Detector
        $centrale = eqLogic::byLogicalId($this->getConfiguration('centrale'), 'Diagral_eOne');
        $MyAlarm = $centrale->setDiagralEnv();
        $MyAlarm->verbose = True;
        $MyAlarm->setSystemId($centrale->getConfiguration('systemid'));
        $MyAlarm->connect($centrale->getConfiguration('mastercode'));
        return $MyAlarm;
    }



    /**
     * Recupère la liste des videos disponibles effectuées par un detecteur a image / Camera
     * et les telecharges selon le paramètre $download et la configuration du detecteur/camera
     * @return array    Liste des videos disponibles
     */
    public function listVideos($download = False) {
        $MyAlarm = $this->getCentrale();
        switch ($this->getConfiguration('type')) {
            case 'imagedetector':
                log::add('Diagral_eOne', 'debug', 'listVideos::ImageDetectors ' . var_export($MyAlarm->getImageDetectors(), true));
                break;
            case 'camera':
                log::add('Diagral_eOne', 'debug', 'listVideos::Cameras ' . var_export($MyAlarm->getCameras(), true));
                break;
        }
        $videoList = $MyAlarm->getVideos($this->getConfiguration('type'), strval($this->getConfiguration('index')));
        // Tri le tableau en descendant (date la plus recente en haut)
        usort($videoList, function ($item1, $item2) {
            return $item2['timestamp'] <=> $item1['timestamp'];
        });
        log::add('Diagral_eOne', 'debug', 'listVideos::VideoList ' . var_export($videoList, true));
        // Si le mode AutoDownload des videos est actif et que le telechargement est autorisé en parametre de la fonction, alors on sauvegarde
        if ($this->getConfiguration('autoDlVideo', '0') == '1' && $download === True) {
            foreach ($videoList as $key => $video) {
                $date = new \DateTime('now', new \DateTimeZone(config::byKey('timezone')));
                $date->setTimestamp($video['timestamp']);
                // Créé l'arborescence de stockage si elle n'existe pas
                if (!is_dir(__PLGBASE__.'/data/videos/'.$this->getConfiguration('type').'/'.strval($this->getLogicalId()))) {
                    mkdir(__PLGBASE__.'/data/videos/'.$this->getConfiguration('type').'/'.strval($this->getLogicalId()), 0766, True);
                }
                // Si le fichier n'existe pas, on le sauvegarde. Dans le cas contraire on le bypass.
                $storePath = __PLGBASE__.'/data/videos/'.$this->getConfiguration('type').'/'.strval($this->getLogicalId()).'/'.$video['timestamp'].'.mp4';
                if (!file_exists($storePath)) {
                    try {
                        $videoFile = $MyAlarm->downloadVideo($this->getConfiguration('type'), strval($this->getConfiguration('index')), $video['id']);
                    } catch (Exception $e) {
                        log::add('Diagral_eOne', 'error', 'listVideos::Download&Store Failed - Reason  : ' . $e->getMessage());
                        continue;
                    }
                    // Store Videos
                    $videoStorage = fopen($storePath, "wb");
                    fwrite($videoStorage, $videoFile);
                    fclose($videoStorage);
                    log::add('Diagral_eOne', 'info', 'listVideos::Download&Store Sauvegarde de la video ' . $video['timestamp'] . '.mp4 du ' . $date->format('Y-m-d H:i:s'));
                    // Si c'est l'element 0 du tableau (le plus recent), on stocke le chemin dans la commande lastVideoPath
                    if ($key == '0') {
                        $this->checkAndUpdateCmd('lastVideoPath', $storePath, $date->format('Y-m-d H:i:s'));
                    }
                } else {
                    // Le fichier existe deja, on le retelecharge pas.
                    log::add('Diagral_eOne', 'info', 'listVideos::Download&Store Bypass de la video ' . $video['timestamp'] . '.mp4 du ' . $date->format('Y-m-d H:i:s'). ' car elle est déjà sauvegardée');
                }
            }
        }
        //$MyAlarm->logout(); // FAIT PARFOIS BUGGER LA RECUPERATION DES VIDEOS
        return $videoList;
    }



    /**
     * Gere la retention des Videos stockés
     */
    private static function purgeVideos() {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__PLGBASE__.'/data/videos/'));
        $files = array();
        foreach ($rii as $file) {
            // Skip hidden files and directories.
            if ($file->getFilename()[0] === '.') {
                continue;
            }
            if ($file->isDir()){
                continue;
            }
            $files[] = array(
                'filefull' => $file->getPathname(),
                'filename' => $file->getFilename(),
                'ctime' => $file->getCTime(),
                'mtime' => $file->getMTime(),
                'size' => $file->getSize()
            );
        }
        // Trie les fichiers du plus ancien au plus récent
        usort($files, function ($item1, $item2) {
            return $item1['ctime'] <=> $item2['ctime'];
        });
        log::add('Diagral_eOne', 'debug', 'purgeVideos::ListAllVideos - ' . var_export($files, true));
        $countArray = count($files);
        log::add('Diagral_eOne', 'debug', 'purgeVideos::CountVideos - Il y a actuellement ' . $countArray . ' vidéos sur '. config::byKey('video_retention', 'Diagral_eOne') .' stockables (retention configuré dans le plugin)');
        // Si il y a plus de videos stocké que la retention autorisée
        if ($countArray > intval(config::byKey('video_retention', 'Diagral_eOne', '100'))) {
            $nbToDelete = $countArray - intval(config::byKey('video_retention', 'Diagral_eOne', '100'));
            for ($entry = 0; $entry <= $nbToDelete - 1; $entry++) {
                unlink($files[$entry]['filefull']);
                log::add('Diagral_eOne', 'info', 'purgeVideos::Delete Suppression du fichier ' . $files[$entry]['filefull']);
            }
        }
    }


    /**
     * Lance une video a la demande
     * @return void
     */
    public function onDemandRecord($MyAlarm) {
        log::add('Diagral_eOne', 'info', 'onDemandRecord::Launching... ');
        try {
            $MyAlarm->launchManualVideo($this->getLogicalId());
            log::add('Diagral_eOne', 'info', 'onDemandRecord::Launch Lancement effectué avec succès.');
            $this->listVideos(true);
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'onDemandRecord::Launch Lancement avec erreur : ' . $e->getMessage());
        }
    }


    /* ------------------------------ Produit Tiers integré Diagral e-One - Automations ------------------------------ */

    /**
     * Execute une commande ADYX (allumer/eteindre lampe, ouvrir/fermer volet, etc...)
     * @param object $MyAlarm
     * @param object $centrale
     * @param string $command
     */
    public function automationSendCmd($MyAlarm, $centrale, $command) {
        try{
            log::add('Diagral_eOne', 'debug', 'automationSendCmd::' . $centrale->getConfiguration('systemid') . '::Starting Request');
            $MyAlarm->automationSendCmd($this->getConfiguration('index'),$command);
            log::add('Diagral_eOne', 'debug', 'automationSendCmd::' . $centrale->getConfiguration('systemid') . '::Success');
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'automationSendCmd - '.  $e->getMessage());
        }
    }

    /**
     * Execute une commande KNX (allumer/eteindre lampe, ouvrir/fermer volet, etc...)
     * @param object $MyAlarm
     * @param object $centrale
     * @param string $command
     * @param int    $position
     */
    public function automationKNXSendCmd($MyAlarm, $centrale, $command, $position = 0) {
        try{
            log::add('Diagral_eOne', 'debug', 'automationKNXSendCmd::' . $centrale->getConfiguration('systemid') . '::Starting Request');
            $MyAlarm->automationKNXSendCmd($this->getConfiguration('index'),$command, $position);
            log::add('Diagral_eOne', 'debug', 'automationKNXSendCmd::' . $centrale->getConfiguration('systemid') . '::Success');
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'automationKNXSendCmd - '.  $e->getMessage());
        }
    }


    /**
     * Retourne la valeur d'un module KNX (position pour volet, etat d'une lampe, etc..)
     * @param object $MyAlarm
     * @param object $centrale
     * @return int   $value 
     */
    public function automationKNXgetValue($MyAlarm, $centrale) {
        try{
            log::add('Diagral_eOne', 'debug', 'automationKNXgetValue::' . $centrale->getConfiguration('systemid') . '::Starting Request');
            $value = $MyAlarm->getKNXAutomationStatus($this->getConfiguration('index'));
            log::add('Diagral_eOne', 'debug', 'automationKNXgetValue::' . $centrale->getConfiguration('systemid') . '::Success');
            return $value;
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'automationKNXgetValue - '.  $e->getMessage());
        }
    }

    /* ------------------------------ Sécurités du plugins ------------------------------ */




    /**
     * Fonction de verification du mode SecureDisarm
     * @return boolean True si SecureDisarm est activé sinon False
     */
    public function secureDisarm() {
        $userIsAdmin = isConnect('admin');
        $secureDisarm = $this->getConfiguration('secureDisarm') ?: 0;
        // Si l'utilisateur est administrateur, alors ne pas bloquer le désarmement même si SecureDisarm est actif
        if ($secureDisarm) {
            // La fonctionnalitée SecureDisarm est activée.
            if ($userIsAdmin) {
                log::add('Diagral_eOne', 'debug', 'L\'utilisateur est administrateur. La fonctionnalité SecureDisarm (Statut actuel : '. var_export($secureDisarm, true) .') est outre-passée.');
                return FALSE;
            } else {
                log::add('Diagral_eOne', 'error', 'La fonctionnalitée SecureDisarm est active (' . var_export($secureDisarm, true) . '). La désactivation de l\'alarme au travers de Jeedom est désactivée.');
                return TRUE;
            }
        } else {
            // La fonctionnalitée SecureDisarm est désactivée.
            return FALSE;
        }
    }



    /* ------------------------------ Gestion des importations de Mail ou SMS ------------------------------ */



    /**
     * Importation d'un message (Mail, SMS, etc..) dans la plugin pour ajouter des informations et/ou prendre des actions
     * @param string $message
     * @param array $options
     */
    public function importMessage($message, $options) {
        log::add('Diagral_eOne', 'debug', 'importMessage::Message ' . $message);
        log::add('Diagral_eOne', 'debug', 'importMessage::Options ' . var_export($options, true));
        $matches = NULL;
        $refreshNeed = FALSE;
        // Si la source est bien indiqué
        if (isset($options['source'])) {
            switch ($options['source']) {
                // Reception par email
                case 'email':
                    // Converti les caractères HTML et unicode en UTF8 + retire les balises HTML
                    $message = trim(strip_tags(html_entity_decode($message, ENT_QUOTES)));
                    log::add('Diagral_eOne', 'debug', 'importMessage::MessageAfterManipulation "' . $message. '"');
                    if(isset($options['subject'])) {
                        switch ($options['subject']) {
                            // Le sujet correspond à un Arret/Marche
                            case ( preg_match( '/Arrêt\/Marche/', $options['subject'] ) ? true : false ):
                                $regex = '/Votre système d\\\'alarme Diagral sur le site «(.*)», vous signale : (.*) par ([^(\\(|\\.)]+)\\s?([^\\.]+)?/m';
                                preg_match($regex, $message, $matches);
                                log::add('Diagral_eOne', 'debug', 'importMessage::MessageAfterManipulationRegex ' . var_export($matches, true));
                                $formatedMsg = $matches[0];
                                // Actuellement le AlarmName semble mal envoyé dans les mails
                                $alarmName = trim($matches[1]);
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::alarmName ' . $alarmName);
                                $mailContent = trim($matches[2]);
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::mailContent ' . $mailContent);
                                $alarmMethod = trim($matches[3]);
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::alarmMethod ' . $alarmMethod);
                                $alarmUser = trim($matches[4], " ()"); # Remove space and ()
                                // Verifie si alarmMethod correspond à un badge
                                if (preg_match( '/Badge\\s(\\d)/', $alarmMethod, $badge)) {
                                    log::add('Diagral_eOne', 'debug', 'importMessage::BadgeAlias Détection d\'un badge');
                                    // On recupere l'alias du badge correspondant
                                    $badgeAlias = $this->getConfiguration('badge' . $badge[1] . '-alias');
                                    log::add('Diagral_eOne', 'debug', 'importMessage::BadgeAlias Badge' . $badge[1] . ' détecté. Son alias est : ' . $badgeAlias);
                                    // Si l'alias de badge est non vide alors on le met dans alarmUser
                                    if (! empty($badgeAlias)) {
                                        log::add('Diagral_eOne', 'debug', 'importMessage::BadgeAlias Ajout de l\'alias du badge'. $badge[1] .' dans le champs "IMPORT - Dernier utilisateur" ('. $badgeAlias .')');
                                        $alarmUser = $badgeAlias;
                                    } else {
                                        log::add('Diagral_eOne', 'debug', 'importMessage::BadgeAlias Aucun alias défini pour le badge'. $badge[1] .'. Le champs "IMPORT - Dernier utilisateur" reste vide.');
                                    }
                                }
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::alarmUser ' . $alarmUser);
                                // Analyse le contenu du message pour définir si c'est une mise en marche ou mise à l'arrêt
                                switch ($mailContent) {
                                    case ( preg_match( '/mise à l\\\'arrêt/', $mailContent ) ? true : false ):
                                        log::add('Diagral_eOne', 'info', 'importMessage::Message::mailContent Mise à l\'arrêt detectée');
                                        $refreshNeed = TRUE;
                                        break;
                                    case ( preg_match( '/mise en marche/', $mailContent ) ? true : false ):
                                        log::add('Diagral_eOne', 'info', 'importMessage::Message::mailContent Mise en marche detectée');
                                        $refreshNeed = TRUE;
                                        break;
                                    default:
                                        log::add('Diagral_eOne', 'warning', 'importMessage::Message::mailContent "'. $mailContent. '" ne correspond à aucun type de message connu');
                                        break;
                                }
                                break;
                            // Le sujet correspond à une Alarme
                            case ( preg_match( '/Alarme/', $options['subject'] ) ? true : false ):
                                // Besoin de plus de type de contenu pour gerer cette partie.
                                $regex = '/Votre système d\\\'alarme Diagral sur le site «(.*)», vous signale : \\S+\\s?(.*) déclenchée par :?l?[e|a]?(.*)./m';
                                preg_match($regex, $message, $matches);
                                log::add('Diagral_eOne', 'info', 'importMessage::MessageAfterManipulationRegex NOT YET PARSED' . var_export($matches, true));
                                $formatedMsg = $matches[0];
                                // Actuellement le AlarmName semble mal envoyé dans les mails
                                $alarmName = trim($matches[1]);
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::alarmName ' . $alarmName);
                                $mailContent = trim($matches[2]); # Correspond au type d'alarme
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::mailContent ' . $mailContent);
                                $alarmMethod = trim($matches[3]); # Correspond au declencheur de l'alarme
                                log::add('Diagral_eOne', 'debug', 'importMessage::Message::alarmMethod ' . $alarmMethod);
                                $alarmUser = ""; # Contenu non necessaire pour la gestion des alarmes
                                $this->checkAndUpdateCmd('mode', "alarm");
                                $this->checkAndUpdateCmd('alarm', 1);
                                log::add('Diagral_eOne', 'debug', 'Update::Mode Changement du statut en mode ALARM suite à la reception d\'un email de déclenchement d\'alarme');
                                // Objectif avec commande info qui specifie l'alarme en cours => Voir comment faire pour la 'unset' apres quelques minutes --> Voir si a la prochaine desactivation, si alarme = 1 alors passé à 0
                                break;
                            // Le sujet est invalide ou inconnu
                            default:
                                log::add('Diagral_eOne', 'warning', 'importMessage::subject "' . $options['subject']. '" non valide ou inconnu. Message : '.$message);
                                break;
                        }
                    }
                    break;
                // Reception par SMS -- a integrer
                case 'sms':
                    log::add('Diagral_eOne', 'warning', 'importMessage::SMS "' . $message. '" non intégré.');
                    break;
                // Source invalide
                default:
                    log::add('Diagral_eOne', 'warning', 'importMessage::source "' . $options['source']. '" non valide.');
                    break;
            }
        } else {
            log::add('Diagral_eOne', 'warning', 'Impossible de parser le contenu sans connaitre la source (mail, sms, etc...)');
        }

        // Retourne les paramètres collectés
        return array(
            "originalMsg" => $formatedMsg,
            "alarmName" => $alarmName,
            "content" => $mailContent,
            "method" => $alarmMethod,
            "user" => $alarmUser,
            "refresh" => $refreshNeed
        );
    }


    /**
     * Get information about alarm trigger
     * @return array with detector and zone who trigger alarm
     */
    public function getAlarmTrigger() {
        $cmdTrigger = $this->getCmd(null, 'imported_last_method')->execCmd();
        if (preg_match( '/.*\\s\\((.*)\\)\\s.*\\((.*)\\)/', $cmdTrigger, $trigger)) {
            $triggerDetector = $trigger[1];
            log::add('Diagral_eOne', 'debug', 'getAlarmTrigger::Detector ' . $triggerDetector);
            $triggerZone = $trigger[2];
            log::add('Diagral_eOne', 'debug', 'getAlarmTrigger::Zone ' . $triggerZone);
        }
        // Retourne les paramètres collectés
        return array(
            "detector" => $triggerDetector,
            "zone" => $triggerZone
        );
    }


    /**
     * Return if alarm is in progress (0 : No Alarm / 1 : Alarm)
     * @return boolean
     */
    public function isAlarmActive() {
        $cmdAlarm = $this->getCmd(null, 'alarm')->execCmd();
        log::add('Diagral_eOne', 'debug', 'isAlarmActive::Status ' . $cmdAlarm);
        return $cmdAlarm;
    }


    /* ------------------------------ Alerte Batterie, AutoProtection, GSM, etc... ------------------------------ */

    public function getAlerts($MyAlarm, $all = FALSE) {
        try{
            $allSysAlerts = $MyAlarm->getSystemAlerts();
            log::add('Diagral_eOne', 'debug', 'List of System alerts : '.var_export($allSysAlerts, TRUE));
            if ($all) {
                return $allSysAlerts;
            } else {
                switch ($this->getConfiguration('type')) {
                    case 'centrale':
                        return $allSysAlerts['centralStatus'];
                        break;
                    case 'module':
                        foreach ($allSysAlerts[$this->getConfiguration('subtype').'Status'] as $module) {
                            if ($module['index'] == $this->getConfiguration('index')) {
                                log::add('Diagral_eOne', 'debug', 'List of System alerts for '.$this->getName().': '.var_export($module, TRUE));
                                return $module;
                            }
                        }
                        break;
                }
            } 
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'getAlerts - '.  $e->getMessage());
        }
    }



    /* ------------------------------ Tracking d'installation ------------------------------ */


    /**
     * Send information to install database to follow number of installations and communication list
     * @param bool $delete (1 if delete action need. Default : 0)
     */
    public static function installTracking($delete=0) {
        log::add('Diagral_eOne', 'debug', 'isEnable::Status ' . config::byKey('InstallBaseStatus', 'Diagral_eOne'));
        log::add('Diagral_eOne', 'debug', 'isAnonymous::Status ' . config::byKey('InstallBaseAnonymousOnly', 'Diagral_eOne'));
        log::add('Diagral_eOne', 'debug', 'emailAddr::Status ' . config::byKey('InstallBaseEmailAddr', 'Diagral_eOne'));
        log::add('Diagral_eOne', 'debug', 'jeedomKey::Status ' . jeedom::getHardwareKey());
        log::add('Diagral_eOne', 'debug', 'marketLogin::Status ' . config::byKey('market::username'));
        // Configuration Request
        $baseURL = "https://jeedom-e061.restdb.io/rest/diagral-eone-installation-base";
        $apiKey = "5e8459ecf96f9f072a0b0bd7";
        // Si c'est une demande de suppression de données
        if ($delete) {
            // Lance la suppression des données de tracking de cette installation
            Diagral_eOne::deleteInstallBase($baseURL,$apiKey);
        } else { // Sinon
            // Si l'envoi d'information est actif
            if (config::byKey('InstallBaseStatus', 'Diagral_eOne')) {
                // Lance la creation ou la mise à jour des données
                Diagral_eOne::createUpdateInstallBase($baseURL,$apiKey);
            } else {
                log::add('Diagral_eOne', 'debug', 'installTracking La mise à jour des données de suivi d\'installation est actuellement désactivée.');
            }
        }
    }

    /**
     * get database UID for this Jeedom Installation
     * @param string $getURL url to request data
     * @param string $apiKey apikey for request
     * @return array with uid and success of request
     */
    public static function getUIDDataInstallBase($url, $apiKey, $waitingTime=0) {
        log::add('Diagral_eOne', 'debug', 'installTracking Récupération de l\'UID de tracking...');
        $urlArgs = $url . '?q={"productKey":"' . jeedom::getHardwareKey() . '"}';
        $success = FALSE;
        $uid = "";
        // Ajout d'une temporisation pour eviter les requetes dans les même secondes
        if ($waitingTime > 0) {
            sleep ( $waitingTime );
        }
        // Execution de la requete de récupération de l'UID
        $requestUID = \Httpful\Request::get($urlArgs)
            ->expectsJson()
            ->timeoutIn(30)
            ->addHeaders(array(
                'x-apikey' => $apiKey,
                'content-type' => 'application/json',
            ))
            ->send();

        // Affichage des messages si le code de retour n'est pas 200
        if (strpos($requestUID->code, '2') === 0) {
            log::add('Diagral_eOne', 'debug', 'installTracking Données reçu (HTTP '. $requestUID->code .')');
            // Recuperation de l'UID
            $uid = $requestUID->body[0]->_id;
            $success = TRUE;
            log::add('Diagral_eOne', 'debug', 'installTracking UID:' . $uid);
        } else {
            log::add('Diagral_eOne', 'debug', 'installTracking Erreur '. $requestUID->code .' avec le serveur de suivi des installations (' . $requestUID->body->message . ') : ' . var_export($requestUID->body, True));
        }

        // Retourne les paramètres collectés
        return array(
            "uid" => $uid,
            "success" => $success
        );
    }

    /**
     * get Plugin branch (stable or beta)
     * @return string $pluginbranch
     */
    public static function getPluginBranch() {
        $update = update::byLogicalId('Diagral_eOne');
        $pluginbranch = $update->getConfiguration('version');
        return $pluginbranch;
    }

    /**
     * Generate data to send to Install Tracking
     * @return array $data data to send
     */
    public static function generateDataInstallBase() {
        log::add('Diagral_eOne', 'debug', 'installTracking Génération des données avant envoi de suivi d\'installation...');
        // Defini les données anonymisées
        $data = array(
            'productKey' => jeedom::getHardwareKey(),
            'hardware' => jeedom::getHardwareName(),
            'id' => '',
            'email' => '',
            'pluginVersion' => config::byKey('plugin_version', 'Diagral_eOne'),
            'pluginBranch' => Diagral_eOne::getPluginBranch(),
            'jeedomVersion' => jeedom::version()
        );
        // Verifie si on peut envoyer les informations non anonyme
        if (! config::byKey('InstallBaseAnonymousOnly', 'Diagral_eOne')) {
            $data['id'] = config::byKey('market::username');
            $data['email'] = config::byKey('InstallBaseEmailAddr', 'Diagral_eOne');
        }
        log::add('Diagral_eOne', 'debug', 'installTracking CONTENT : ' . var_export($data, true));
        return $data;
    }

    /**
     * Create or update data
     * @param string $url
     * @param string $apiKey
     */
    public static function createUpdateInstallBase($url,$apiKey) {
        log::add('Diagral_eOne', 'debug', 'installTracking Lancement de la mise à jour.');
        // Attente aleatoire entre 2 et 10 secondes pour répartir les requètes
        $waitingTime = rand (2, 10);
        // Récuperation de l'UID d'installation
        $uidResponse = Diagral_eOne::getUIDDataInstallBase($url, $apiKey, $waitingTime);
        // Verifie que la requete d'UID est un succes (qu'un UID existe ou non). Se base uniquement sur le statut de la requete (2xx)
        if ($uidResponse['success']) {
            // Genere les data a envoyer
            $data = Diagral_eOne::generateDataInstallBase();
            $waitingTime = rand (2, 10);
            // Si aucun UID existe (aucune entrée existante en base)
            if ( empty($uidResponse['uid']) && $uidResponse['success'] ) {
                log::add('Diagral_eOne', 'info', 'installTracking Aucune entrée existante. Creation d\'une nouvelle.');
                Diagral_eOne::sendDataInstallBase($url,$apiKey,'POST',$data,$waitingTime);
            } else { // Une entrée existe deja
                $url = $url . '/' . $uidResponse['uid'];
                log::add('Diagral_eOne', 'debug', 'installTracking Mise à jour de l\'entrée.');
                Diagral_eOne::sendDataInstallBase($url,$apiKey,'PUT',$data,$waitingTime);
            }
        }
    }

    /**
     * Delete install base
     * @param string $url
     * @param string $apiKey
     */
    public static function deleteInstallBase($url,$apiKey) {
        log::add('Diagral_eOne', 'debug', 'Suppression de votre installation dans la base de Tracking en cours...');
        // Récuperation de l'UID d'installation
        $uidResponse = Diagral_eOne::getUIDDataInstallBase($url, $apiKey);
        // Si l'entrée existe bien
        if ( ! empty($uidResponse['uid']) && $uidResponse['success']) {
            $url = $url . '/' . $uidResponse['uid'];
            // Je supprime l'entrée
            $returnCode = Diagral_eOne::sendDataInstallBase($url,$apiKey,'DELETE');
            if (strpos($returnCode, '2') === 0) {
                log::add('Diagral_eOne', 'info', 'installTracking Suppression de votre installation dans la base de Tracking effectuée.');
                // Je désactive le tracking
                config::save('InstallBaseStatus', 0, 'Diagral_eOne');
                log::add('Diagral_eOne', 'info', 'installTracking Désactivation du tracking.');
            } else {
                log::add('Diagral_eOne', 'error', 'installTracking Erreur de suppression des données de tracking.');
            }
        } else {
            log::add('Diagral_eOne', 'error', 'installTracking Erreur de suppression des données de tracking. L\'UID n\'a pas était trouvé.');
        }
    }

    /**
     * Call API to Database
     * @param string $url
     * @param string $apiKey
     * @param string $method
     * @param array $data
     * @return string HTTP return code
     */
    public static function sendDataInstallBase($url,$apiKey,$method,$data=array(),$waitingTime=0) {
        log::add('Diagral_eOne', 'debug', 'installTracking Transmission des données...');
        // Ajout d'une temporisation pour eviter les requetes dans les même secondes
        if ($waitingTime > 0) {
            sleep ( $waitingTime );
        }
        // Execution des envois d'informations
        $request = \Httpful\Request::post($url)
            ->expectsJson()
            ->timeoutIn(30)
            ->addHeaders(array(
                'x-apikey' => $apiKey,
                'content-type' => 'application/json',
                'X-HTTP-Method-Override' => $method,
                'cache-control' => 'no-cache',
            ))
            ->body(json_encode($data))
            ->send();
        // Affichage des messages si le code de retour n'est pas 200
        if (strpos($request->code, '2') === 0) {
            log::add('Diagral_eOne', 'debug', 'installTracking Données envoyées (HTTP '. $request->code .')');
        } else {
            log::add('Diagral_eOne', 'debug', 'installTracking Erreur '. $request->code .' avec le serveur de suivi des installations (' . $request->body->message . ') : ' . var_export($request->body, True));
        }
        return $request->code;
    }

    /* ------------------------------ Gestion des icones par type d'équipements ------------------------------ */

    /**
     * Renvoi le chemin de l'icone
     */
    public static function getPathDeviceIcon($eqLogic) {
        switch ($eqLogic->getConfiguration('type')) {
            case (preg_match('/.*-portal/', $eqLogic->getConfiguration('type')) ? true : false) :
                $type = 'portal';
                break;
            case (preg_match('/.*-shutter/', $eqLogic->getConfiguration('type')) ? true : false) :
                $type = 'rollershutter';
                break;
            case (preg_match('/.*-garage_door/', $eqLogic->getConfiguration('type')) ? true : false) :
                $type = 'garagedoor';
                break;
            case (preg_match('/.*-light/', $eqLogic->getConfiguration('type')) ? true : false) :
                $type = 'light';
                break;
            default:
                $type = $eqLogic->getConfiguration('type');
        }
        if (file_exists(__DIR__ . '/../devices_image/' . $type . '_icon.png')) {
            return 'plugins/Diagral_eOne/core/devices_image/' . $type . '_icon.png';
        }
        $plugin = plugin::byId('Diagral_eOne');
        return $plugin->getPathImgIcon();
    }


    /* ------------------------------ Génératon de l'archive de DiagDebug ------------------------------ */

    public function generateDiagDebug() {
        try {
            $diag = new DiagDebug('Diagral_eOne');
            $diag->addPluginLogs();
            $diag->addJeedomLogs(array('plugin', 'jeedom', 'http.error'));
            $diag->addCmd('nslookup appv3.tt-monitor.com');
            $diag->addCmd('ip addr', NULL, TRUE);
            $diag->addCmd('ip route', NULL, TRUE);
            $diag->addCmd('ls -lR #PLUGBASE#', NULL, TRUE);
            $diag->addPluginConf();
            $diag->addAllPluginEqlogic();
            $diag->addFile('#PLUGBASE#');
            return $diag->download();
        } catch (Exception $e) {
            log::add('Diagral_eOne', 'error', 'generateDiagDebug - '.  $e->getMessage());
        }
    }


    /*     * **********************Getteur Setteur*************************** */
}

class Diagral_eOneCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        $changed = false;
        $eqLogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this

        // On se connecte à Diagral
        if ($eqLogic->getConfiguration('type') != 'centrale') {
            $centrale = eqLogic::byLogicalId($eqLogic->getConfiguration('centrale'), 'Diagral_eOne');
            $MyAlarm = $eqLogic->getCentrale();
        } else {
            $MyAlarm = $eqLogic->setDiagralEnv();
        }

        switch ($this->getLogicalId()) {
            case 'refresh':
                // Recuperation des alertes
                $alert = $eqLogic->getAlerts($MyAlarm);
                $changed = $eqLogic->deviceRefresh($MyAlarm, $alert);
                break;
            case 'total_disarm':
                if ( ! $eqLogic->secureDisarm()) { // SecureDisarm n'est pas activée
                    $eqLogic->setCompleteDesactivation($MyAlarm);
                    $alarmStatus = $eqLogic->getDiagralStatus($MyAlarm);
                    $changed = $eqLogic->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                    $changed = $eqLogic->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                    $changed = $eqLogic->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                }
                break;
            case 'disarm_partial':
                if ( ! $eqLogic->secureDisarm()) { // SecureDisarm n'est pas activée
                    $status = $eqLogic->setPartialDesactivation($MyAlarm, $_options['select']);
                    if($status) {
                        $alarmStatus = $eqLogic->getDiagralStatus($MyAlarm);
                        $changed = $eqLogic->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                        $changed = $eqLogic->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                        $changed = $eqLogic->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                    }
                }
                break;
            case 'total_arm':
                $eqLogic->setCompleteActivation($MyAlarm);
                $alarmStatus = $eqLogic->getDiagralStatus($MyAlarm);
                $changed = $eqLogic->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                break;
            case 'arm_presence':
                $eqLogic->setPresenceActivation($MyAlarm);
                $alarmStatus = $eqLogic->getDiagralStatus($MyAlarm);
                $changed = $eqLogic->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                break;
            case 'arm_partial':
                $eqLogic->setPartialActivation($MyAlarm, $_options['select'], $this->getConfiguration('listValue'));
                $alarmStatus = $eqLogic->getDiagralStatus($MyAlarm);
                $changed = $eqLogic->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                $changed = $eqLogic->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                break;
            case 'launch_scenario':
                $eqLogic->setScenario($MyAlarm, $_options['select'], $this->getConfiguration('listValue'));
                break;
            case 'import_message':
                $explodeOptions = explode('|', $_options['title']); // Explose les options pour recuperer le sujet dans la premiere partie separé par "|"
                if (count($explodeOptions) > 1) { // Si on a plus d'une entrée dans le table, j'ai donc un sujet
                    $options = arg2array($explodeOptions[1]);
                    $options['subject'] = $explodeOptions[0];
                } else { // Sinon je n'ai que les options
                    $options = arg2array($explodeOptions[0]);
                }
                $contents = $eqLogic->importMessage($_options['message'], $options);
                $eqLogic->checkAndUpdateCmd('imported_last_message', $contents['originalMsg']);
                $eqLogic->checkAndUpdateCmd('imported_last_action', $contents['content']);
                $eqLogic->checkAndUpdateCmd('imported_last_method', $contents['method']);
                $eqLogic->checkAndUpdateCmd('imported_last_user', $contents['user']);
                if($contents['refresh']) {
                    $alarmStatus = $eqLogic->getDiagralStatus($MyAlarm);
                    $changed = $eqLogic->checkAndUpdateCmd('status', $alarmStatus['status']) || $changed;
                    $changed = $eqLogic->checkAndUpdateCmd('mode', $alarmStatus['mode']) || $changed;
                    $changed = $eqLogic->checkAndUpdateCmd('groups_enable', $alarmStatus['groups']) || $changed;
                }
                break;
            case 'force_groups_refresh_json':
                $eqLogic->generateGroupJson($MyAlarm);
                break;
            case 'force_scenarios_refresh_json':
                $eqLogic->generateScenariosJson($MyAlarm);
                break;
            case 'onDemandRecord':
                $eqLogic->onDemandRecord($MyAlarm);
                break;
            case 'portalOpenComplete':
            case 'garageMove':
                $eqLogic->automationSendCmd($MyAlarm, $centrale, "UP");
                break;
            case 'portalOpenPartial':
                $eqLogic->automationSendCmd($MyAlarm, $centrale, "DOWN");
                break;
            case 'adyxShutterOpen':
                $eqLogic->automationSendCmd($MyAlarm, $centrale, "UP");
                break;
            case 'adyxShutterClose':
                $eqLogic->automationSendCmd($MyAlarm, $centrale, "DOWN");
                break;
            case 'adyxShutterStop':
                $eqLogic->automationSendCmd($MyAlarm, $centrale, "STOP");
                break;
            case 'KNXshutterOpen':
                $eqLogic->automationKNXSendCmd($MyAlarm, $centrale, "UP");
                sleep(20);
                $eqLogic->deviceRefresh($MyAlarm);
                break;
            case 'KNXshutterClose':
                $eqLogic->automationKNXSendCmd($MyAlarm, $centrale, "DOWN");
                sleep(20);
                $eqLogic->deviceRefresh($MyAlarm);
                break;
            case 'KNXshutterStop':
                $eqLogic->automationKNXSendCmd($MyAlarm, $centrale, "STOP");
                sleep(20);
                $eqLogic->deviceRefresh($MyAlarm);
                break;
            case 'KNXshutterPosition':
                $eqLogic->automationKNXSendCmd($MyAlarm, $centrale, "POSITION", $_options['slider']);
                sleep(20);
                $eqLogic->deviceRefresh($MyAlarm);
                break;
            case 'KNXlightOn':
                $eqLogic->automationKNXSendCmd($MyAlarm, $centrale, "ON");
                sleep(20);
                $eqLogic->deviceRefresh($MyAlarm);
                break;
            case 'KNXlightOff':
                $eqLogic->automationKNXSendCmd($MyAlarm, $centrale, "OFF");
                sleep(20);
                $eqLogic->deviceRefresh($MyAlarm);
                break;
            default:
                log::add('Diagral_eOne', 'warning', 'Commande inconnue : ' . $this->getLogicalId());
        }
        $MyAlarm->logout();
        if ($changed) {
            $eqLogic->refreshWidget();
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
