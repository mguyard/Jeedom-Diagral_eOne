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

define('__ROOT__', dirname(dirname(dirname(__FILE__))));
require_once (__ROOT__.'/3rparty/Diagral-eOne-API-PHP/class/Diagral/Diagral_eOne.class.php');
//use \Mguyard\Diagral\Diagral_eOne;

class Diagral_eOne extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */

    public static function synchronize() {
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
        $MyAlarm->verbose = boolval(config::byKey('verbose', 'Diagral_eOne'));
        $debug_output = $MyAlarm->login();
        log::add('Diagral_eOne', 'debug', 'Synchronize::Login ' . var_export($debug_output, true));
        $Diagral_systems = $MyAlarm->getSystems();
        log::add('Diagral_eOne', 'debug', 'Synchronize::GetSystems ' . var_export($Diagral_systems, true));
        // TODO : Voir pourquoi le logout ne marche plus
        //$MyAlarm->logout();
        foreach ($Diagral_systems as $key => $value) {
            $Alarm = Diagral_eOne::byLogicalId($value[id], 'Diagral_eOne');
            if (!is_object($Alarm)) {
                log::add('Diagral_eOne', 'info', "Synchronize:: Alarme trouvée ".$value[name]."(".$value[id]."):");
                $eqLogic = new Diagral_eOne();
                $eqLogic->setName($value[name]);
                $eqLogic->setIsEnable(0);
                $eqLogic->setIsVisible(1);
                $eqLogic->setLogicalId($value[id]);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('systemid', $key);
            } else {
                log::add('Diagral_eOne', 'info', "Synchronize:: Alarme ".$Alarm->getName()." mise à jour.");
                $eqLogic = $Alarm;
                $eqLogic->setName($Alarm->getName());
                $eqLogic->setIsEnable($Alarm->getIsEnable());
                $eqLogic->setIsVisible($Alarm->getIsVisible());
                $eqLogic->setLogicalId($value[id]);
                $eqLogic->setEqType_name('Diagral_eOne');
                $eqLogic->setCategory('security', 1);
                $eqLogic->setConfiguration('systemid', $key);
            }
        }
        $eqLogic->save();
        if(!is_object($Alarm)) { // NEW
            event::add('jeedom::alert', array(
                'level' => 'warning',
                'page' => 'Diagral_eOne',
                'message' => __('Alarme ajouté avec succès : ' .$value[name], __FILE__),
            ));
        } else { // ALREADY EXIST
            event::add('jeedom::alert', array(
                'level' => 'warning',
                'page' => 'Diagral_eOne',
                'message' => __('Alarme mise à jour : ' .$value[name], __FILE__),
            ));
        }
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

    }

    public function postRemove() {

    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

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






    /**
     * Creation des commandes pour l'equipement
     */
    private function createCmd() {
        // Definition et chargement du fichier de configuration globale qui inclus notament les commandes
        $filename = __ROOT__.'/core/config/config.json';
        $config = $this->loadConfigFile($filename, 'commands');

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
                if( isset($command['configuration']['function'])) {
                    list($fieldType, $fieldFunction)= explode("::", $command['configuration']['function']);
                    log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::' . $command['logicalId'] . ' ' . $fieldType . ' with function ' . $fieldFunction);
                    if (is_callable(array(get_class($this), $fieldFunction))) {
                        log::add('Diagral_eOne', 'debug', 'postSave::UpdateContent::' . $command['logicalId'] . 'VerifyFunctionCallable ' . $fieldFunction . ' TRUE');
                        $contentField = call_user_func(array(get_class($this), $fieldFunction));
                        $parsedContent = "";
                        switch ($fieldType) {
                            case 'listValue':
                                $parsedContent = $this->generatePossibilitiesSelect($contentField);
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
                    log::add('Diagral_eOne', 'info', 'postSave::updateCmd '.$command['logicalId'].' ('.$command['name'].')');
                }
            } else {
                log::add('Diagral_eOne', 'info', 'postSave::bypassCmd '.$command['logicalId'].' ('.$command['name'].')');
            }
        }
    }




    /* ------------------------------ Generate JSON ------------------------------ */


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
            $cmdGroup = $eqLogic->getCmd(null, 'force_groups_refresh_json');
            $cmdGroup->execute();
            $cmdScenario = $eqLogic->getCmd(null, 'force_scenarios_refresh_json');
            $cmdScenario->execute();
            $eqLogic->save();
        }
    }

    /**
     * Generation des groupes Diagral dans un fichier JSON pour un object specifique $this
     */
    public function generateGroupJson() {
        log::add('Diagral_eOne', 'debug', 'generateGroupJson::Start');
        $filename = __ROOT__.'/core/config/groups_' . $this->getConfiguration('systemid') . '.json';
        $MyAlarm = $this->setDiagralEnv();
        // Recuperation de l'ensemble des groupes
        $groups = $MyAlarm->getAllGroups();
        $groupsJSON = array();
        // Mise en tableau des groupes ainsi que les ID
        foreach ($groups as $groupId => $groupName) {
            array_push($groupsJSON, array("groupID" => ++$groupId, "groupName" => $groupName));
        }
        log::add('Diagral_eOne', 'debug', 'generateGroupJson::Result ' . var_export(json_encode($groupsJSON), true));
        $this->writeConfigFile($filename, json_encode(array('lastModified' => date("Y-m-d H:i:s"), 'groups' => $groupsJSON),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }

    public function generateScenariosJson() {
        log::add('Diagral_eOne', 'debug', 'generateScenariosJson::Start');
        $filename = __ROOT__.'/core/config/scenarios_' . $this->getConfiguration('systemid') . '.json';
        $MyAlarm = $this->setDiagralEnv();
        // Recuperation de l'ensemble des scenarios
        $scenarios = $MyAlarm->getScenarios();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'generateScenariosJson::ListScenarios' . var_export($scenarios, true));
        log::add('Diagral_eOne', 'debug', 'generateScenariosJson::' . $this->getConfiguration('systemid') . '::Success');
        $this->writeConfigFile($filename, json_encode(array('lastModified' => date("Y-m-d H:i:s"), 'scenarios' => $scenarios),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }


    /* ------------------------------ Generate ListValue ------------------------------ */


    /**
     * Genere une ListValue formaté a partir d'un tableau
     * @return string Liste formatée en select pour les actions
     * @return $PossibilitiesSelect     String de liste select ListValue
     */
    private function generatePossibilitiesSelect($selectArray) {
        log::add('Diagral_eOne', 'debug', 'generatePossibilitiesSelect::Start');
        $PossibilitiesSelect = "";
        log::add('Diagral_eOne', 'debug', 'generatePossibilitiesSelect::Return ' . var_export($selectArray, true));
        foreach ($selectArray as $key => $Possibility) {
            if ($key > 0) {
                $PossibilitiesSelect .= ';';
            }
            $PossibilitiesSelect .= $key . '|' . $Possibility;
        }
        return $PossibilitiesSelect;
    }

    /**
     * Genere la liste des combinaisons de Zones possible
     * @return array Tableau contenant un tableau pour chaque combinaison possible.
     */
    private function generateGroupsPossibilities() {
        log::add('Diagral_eOne', 'debug', 'generateGroupsPossibilities::Start');
        $filename = __ROOT__.'/core/config/groups_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $this->generateGroupJson();
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
        $filename = __ROOT__.'/core/config/scenarios_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $this->generateScenariosJson();
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



    /* ------------------------------ Lancement d'actions sur le Cloud Diagral ------------------------------ */



    /**
     * Genere l'environnement Diagral inclus le login, la recuperation de la configuration ainsi que l'entrée dans le systeme
     * @return object   $MyAlarm
     */
    private function setDiagralEnv() {
        log::add('Diagral_eOne', 'debug', 'setDiagralEnv::' . $this->getConfiguration('systemid') . '::Start Diagral Environnement');
        if ( ! empty($this->getConfiguration('mastercode'))) {
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
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
        return $MyAlarm;
        } else {
            throw new Exception("MasterCode cannot be empty. Please configure it in your device.");
        }
    }

    /**
     * Recuperation automatique (cron) du statut de l'ensemble des équipements actifs
     */
    public function pull() {
        log::add('Diagral_eOne', 'debug', 'pull::Starting Request');
        foreach (eqLogic::byType('Diagral_eOne') as $eqLogic) {
            if($eqLogic->getIsEnable() && ! empty($eqLogic->getConfiguration('mastercode'))) {
                list($status,$groups) = $eqLogic->getDiagralStatus();
                $eqLogic->checkAndUpdateCmd('status', $status); // on met à jour la commande avec le LogicalId "status"  de l'eqlogic
                $eqLogic->checkAndUpdateCmd('groups_enable', $groups); // On met à jour la commande avec le LogicalId "groups_enable" de l'eqlogic
            }
        }
    }


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
            if(!filter_var(config::byKey('polling_interval', 'Diagral_eOne'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))){
                throw new Exception(__('La frequence de mise à jour (polling) est invalide. Elle doit contenir un nombre (entier) de minutes superieur a 1.', __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'checkConfig::polling_interval OK with value ' . config::byKey('polling_interval', 'Diagral_eOne'));
            }
        } else {
            log::add('Diagral_eOne', 'debug', 'checkConfig::polling_interval Default Value');
        }
    }

    /**
     * Recupere le statut de l'alarme
     * @return string statut de l'état de l'alarme
     */
    public function getDiagralStatus() {
        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm = $this->setDiagralEnv();
        // Si nous n'avons pas d'information sur l'état de l'alarme (session existante), on demande les informations
        if(empty($MyAlarm->systemState)) {
            $MyAlarm->getAlarmStatus();
        }
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::' . $this->getConfiguration('systemid') . '::Result ' . var_export($MyAlarm->systemState, true) );
        if ( strcmp($MyAlarm->systemState, 'off') !== 0) {
            $filename = __ROOT__.'/core/config/groups_' . $this->getConfiguration('systemid') . '.json';
            // Si le fichier JSON des groupes n'existe pas, on le genère.
            if ( file_exists($filename) === false ) {
                $this->generateGroupJson();
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
        } else {
            $groups = "";
        }
        return array($MyAlarm->systemState, $groups);
    }

    /**
     * Fonction de desactivaton totale de l'alarme
     */
    public function setCompleteDesactivation() {
        log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm = $this->setDiagralEnv();
        $MyAlarm->completeDesactivation();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::' . $this->getConfiguration('systemid') . '::Success');
    }

    /**
     * Fonction d'activation complete de l'alarme
     */
    public function setCompleteActivation() {
        log::add('Diagral_eOne', 'debug', 'setCompleteActivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm = $this->setDiagralEnv();
        $MyAlarm->completeActivation();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $this->getConfiguration('systemid') . '::Success');
    }

    /**
     * Fonction d'activation du mode presence
     * @param int $systemId ID de l'alarme sur le compte Diagral
     */
    public function setPresenceActivation() {
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $this->getConfiguration('systemid') . '::Starting Request');
        $MyAlarm = $this->setDiagralEnv();
        $MyAlarm->presenceActivation();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $this->getConfiguration('systemid') . '::Success');
    }

    /**
     * Activation partielle de l'alarme
     * @param int $cmdValue         ID du listValue recu en parametre de l'execution de la commande
     * @param array $listValue      listValue configuré sur la commande
     */
    public function setPartialActivation($cmdValue, $listValue) {
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::cmdValue ' . $cmdValue);
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::ListValue ' . var_export($listValue, true));
        $filename = __ROOT__.'/core/config/groups_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des groupes n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $this->generateGroupJson();
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
        $MyAlarm = $this->setDiagralEnv();
        $MyAlarm->partialActivation($groups);
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setPartialActivation::' . $this->getConfiguration('systemid') . '::Success');
    }

    /**
     * Lancement de scenarios
     * @param int $cmdValue         ID du listValue recu en parametre de l'execution de la commande
     * @param array $listValue      listValue configuré sur la commande
     */
    public function setScenario($cmdValue, $listValue) {
        log::add('Diagral_eOne', 'debug', 'setScenario::cmdValue ' . $cmdValue);
        log::add('Diagral_eOne', 'debug', 'setScenario::ListValue ' . var_export($listValue, true));
        $filename = __ROOT__.'/core/config/scenarios_' . $this->getConfiguration('systemid') . '.json';
        // Si le fichier JSON des scenarios n'existe pas, on le genère.
        if ( file_exists($filename) === false ) {
            $this->generateScenariosJson();
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
        $MyAlarm = $this->setDiagralEnv();
        $MyAlarm->launchScenario($scenarioID);
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setScenario::' . $this->getConfiguration('systemid') . '::Success ' . $listValue[$cmdValue]);
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
        $eqLogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
        switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
            case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave.
                list($status,$groups) = $eqLogic->getDiagralStatus(); 	//On lance la fonction getDiagralStatus() pour récupérer le statut de l'alarme et on la stocke dans la variable $status
                $eqLogic->checkAndUpdateCmd('status', $status); // on met à jour la commande avec le LogicalId "status"  de l'eqlogic
                $eqLogic->checkAndUpdateCmd('groups_enable', $groups); // On met à jour la commande avec le LogicalId "groups_enable" de l'eqlogic
                break;
            case 'total_disarm':
                $eqLogic->setCompleteDesactivation();
                ## TODO : Voir si on peut pas remplacer ces deux commandes par un appel de la commande refresh
                list($status,$groups) = $eqLogic->getDiagralStatus();
                $eqLogic->checkAndUpdateCmd('status', $status);
                $eqLogic->checkAndUpdateCmd('groups_enable', $groups);
                break;
            case 'arm_presence':
                $eqLogic->setPresenceActivation();
                ## TODO : Voir si on peut pas remplacer ces deux commandes par un appel de la commande refresh
                list($status,$groups) = $eqLogic->getDiagralStatus();
                $eqLogic->checkAndUpdateCmd('status', $status);
                $eqLogic->checkAndUpdateCmd('groups_enable', $groups);
                break;
            case 'arm_partial':
                $eqLogic->setPartialActivation($_options['select'], $this->getConfiguration('listValue'));
                ## TODO : Voir si on peut pas remplacer ces deux commandes par un appel de la commande refresh
                list($status,$groups) = $eqLogic->getDiagralStatus();
                $eqLogic->checkAndUpdateCmd('status', $status);
                $eqLogic->checkAndUpdateCmd('groups_enable', $groups);
                break;
            case 'launch_scenario':
                $eqLogic->setScenario($_options['select'], $this->getConfiguration('listValue'));
                break;
            case 'force_groups_refresh_json':
                $eqLogic->generateGroupJson();
                break;
            case 'force_scenarios_refresh_json':
                $eqLogic->generateScenariosJson();
                break;
            default:
                log::add('Diagral_eOne', 'warning', 'Commande inconnue : ' . $this->getLogicalId());
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
