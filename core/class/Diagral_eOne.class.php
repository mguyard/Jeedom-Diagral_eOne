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
        $config = $this->loadConfigFile();

        foreach ($config['commands'] as $command) {
            $cmd = $this->getCmd(null, $command['logicalId']);
            if (!is_object($cmd)) {
                log::add('Diagral_eOne', 'info', 'postSave::createCmd '.$command['logicalId'].' ('.$command['name'].')');
                $cmd = new Diagral_eOneCmd();
                $cmd->setName(__($command['name'], __FILE__));
            } else {
                log::add('Diagral_eOne', 'debug', 'postSave::updateCmd '.$command['logicalId'].' ('.$command['name'].')');
            }
            $cmd->setOrder($i++);
            //$cmd->setLogicalId($command['logicalId']);
            $cmd->setEqLogic_id($this->getId());
            //$cmd->setType($command['type']);
            //$cmd->setSubType($command['subtype']);
            utils::a2o($cmd, $command);
            $cmd->save();
            ## TODO : Voir pour generer automatiquement la listValue pour Activation Partielle a partir de la liste des zone avec une boucle pour creer toutes les combinaisons possibles.
        }
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
     * Charge la configuration globale du plugin.
     * @return array tableau de tout les parametres
     */
    private function loadConfigFile() {
        $filename = __ROOT__.'/core/config/config.json';
        log::add('Diagral_eOne', 'debug', 'loadConfigFile::'.$filename);
        if ( file_exists($filename) === false ) {
            throw new Exception('Impossible de trouver le fichier de configuration');
        }
        $content = file_get_contents($filename);
        if (!is_json($content)) {
            throw new Exception('Le fichier de configuration \'' . $filename . '\' est corrompu');
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['commands'])) {
            throw new Exception('Le fichier de configuration \'' . $filename . '\' est invalide');
        }

        return $data;
    }

    /**
     * Genere la liste des combinaisons de Zones possible
     * @return array Tableau contenant un tableau pour chaque combinaison possible.
     */
    private function generateZonePossibility() {
        // Recuperation de l'ensemble des groups avec leur nom et leur ID
        $groups = $MyAlarm->getAllGroups();
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
            $FinalCombination[] = implode(" + ", $combinaison);
        }
        // Tri du tableau selon la longueur des valeurs (pour faire un plus bel affichage)
        array_multisort(array_map('strlen', $FinalCombination), $FinalCombination);
        return $FinalCombination;
    }

    /**
     * Recupere le statut de l'alarme
     * @param int $systemId ID de l'alarme sur le compte Diagral
     * @return string statut de l'état de l'alarme
     */
    public function getDiagralStatus($systemId) {
        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::' . $systemId . '::Starting Request');
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
        $MyAlarm->verbose = config::byKey('verbose', 'Diagral_eOne');
        $MyAlarm->login();
        $MyAlarm->getSystems();
        $MyAlarm->setSystemId(intval($systemId));
        $MyAlarm->getConfiguration();
        $MyAlarm->connect($this->getConfiguration('mastercode'));
        // Si nous n'avons pas d'information sur l'état de l'alarme (session existante), on demande les informations
        if(empty($MyAlarm->systemState)) {
            $MyAlarm->getAlarmStatus();
        }
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'getDiagralStatus::' . $systemId . '::Result::' . var_export($MyAlarm->systemState, true) );
        return $MyAlarm->systemState;
    }

    /**
     * Fonction de desactivaton totale de l'alarme
     * @param int $systemId ID de l'alarme sur le compte Diagral
     */
    public function setCompleteDesactivation($systemId) {
        log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::' . $systemId . '::Starting Request');
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
        $MyAlarm->verbose = config::byKey('verbose', 'Diagral_eOne');
        $MyAlarm->login();
        $MyAlarm->getSystems();
        $MyAlarm->setSystemId(intval($systemId));
        $MyAlarm->getConfiguration();
        $MyAlarm->connect($this->getConfiguration('mastercode'));
        $MyAlarm->completeDesactivation();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setCompleteDesactivation::' . $systemId . '::Success');
    }

    /**
     * Fonction d'activation complete de l'alarme
     * @param int $systemId ID de l'alarme sur le compte Diagral
     */
    public function setCompleteActivation($systemId) {
        log::add('Diagral_eOne', 'debug', 'setCompleteActivation::' . $systemId . '::Starting Request');
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
        $MyAlarm->verbose = config::byKey('verbose', 'Diagral_eOne');
        $MyAlarm->login();
        $MyAlarm->getSystems();
        $MyAlarm->setSystemId(intval($systemId));
        $MyAlarm->getConfiguration();
        $MyAlarm->connect($this->getConfiguration('mastercode'));
        $MyAlarm->completeActivation();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $systemId . '::Success');
    }

    /**
     * Fonction d'activation du mode presence
     * @param int $systemId ID de l'alarme sur le compte Diagral
     */
    public function setPresenceActivation($systemId) {
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $systemId . '::Starting Request');
        $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
        $MyAlarm->verbose = config::byKey('verbose', 'Diagral_eOne');
        $MyAlarm->login();
        $MyAlarm->getSystems();
        $MyAlarm->setSystemId(intval($systemId));
        $MyAlarm->getConfiguration();
        $MyAlarm->connect($this->getConfiguration('mastercode'));
        $MyAlarm->presenceActivation();
        $MyAlarm->logout();
        log::add('Diagral_eOne', 'debug', 'setPresenceActivation::' . $systemId . '::Success');
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
        $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
        switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
            case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave.
                $status = $eqlogic->getDiagralStatus($eqlogic->getConfiguration('systemid')); 	//On lance la fonction getDiagralStatus() pour récupérer le statut de l'alarme et on la stocke dans la variable $status
                $eqlogic->checkAndUpdateCmd('status', $status); // on met à jour la commande avec le LogicalId "status"  de l'eqlogic
                break;
            case 'total_disarm':
                $eqlogic->setCompleteDesactivation($eqlogic->getConfiguration('systemid'));
                ## TODO : Voir si on peut pas remplacer ces deux commandes par un appel de la commande refresh
                $status = $eqlogic->getDiagralStatus($eqlogic->getConfiguration('systemid'));
                $eqlogic->checkAndUpdateCmd('status', $status);
                break;
            case 'arm_presence':
                $eqlogic->setPresenceActivation($eqlogic->getConfiguration('systemid'));
                ## TODO : Voir si on peut pas remplacer ces deux commandes par un appel de la commande refresh
                $status = $eqlogic->getDiagralStatus($eqlogic->getConfiguration('systemid'));
                $eqlogic->checkAndUpdateCmd('status', $status);
                break;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
