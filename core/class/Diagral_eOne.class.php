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
          log::add('Diagral_eOne', 'info', "Alarme trouvée ".$value[name]."(".$value[id]."):");
          $eqLogic = new Diagral_eOne();
					$eqLogic->setName($value[name]);
					$eqLogic->setIsEnable(1);
					$eqLogic->setIsVisible(1);
					$eqLogic->setLogicalId($value[id]);
					$eqLogic->setEqType_name('Diagral_eOne');
          $eqLogic->setCategory('security', 1);
					$eqLogic->setConfiguration('systemid', $key);
				} else {
					log::add('Diagral_eOne', 'info', "Alarme ".$value[name]." mise à jour.");
          $eqLogic = $Alarm;
          $eqLogic->setName($value[name]);
          $eqLogic->setIsEnable(1);
          $eqLogic->setIsVisible(1);
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
      # Commande Status
      $status = $this->getCmd(null, 'status');
      if (!is_object($status)) {
        $status = new Diagral_eOneCmd();
        $status->setName(__('Statut', __FILE__));
      }
      $status->setLogicalId('status');
      $status->setEqLogic_id($this->getId());
      $status->setType('info');
      $status->setSubType('string');
      $status->save();

      # Commande Refresh
      $refresh = $this->getCmd(null, 'refresh');
  		if (!is_object($refresh)) {
  			$refresh = new Diagral_eOneCmd();
  			$refresh->setName(__('Rafraichir', __FILE__));
  		}
  		$refresh->setEqLogic_id($this->getId());
  		$refresh->setLogicalId('refresh');
  		$refresh->setType('action');
  		$refresh->setSubType('other');
  		$refresh->save();
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

    public function getDiagralStatus($systemId) {
      $MyAlarm = new Mguyard\Diagral\Diagral_eOne(config::byKey('login', 'Diagral_eOne'),config::byKey('password', 'Diagral_eOne'));
      $MyAlarm->verbose = False;
      $MyAlarm->login();
      $MyAlarm->getSystems();
      $data = $MyAlarm->setSystemId(intval($systemId));
      $MyAlarm->getConfiguration();
      $MyAlarm->connect(config::byKey('mastercode', 'Diagral_eOne'));
      // Si nous n'avons pas d'information sur l'état de l'alarme (session existante), on demande les informations
      if(empty($MyAlarm->systemState)) {
        $MyAlarm->getAlarmStatus();
      }
      // TODO : Voir pourquoi le logout ne marche plus
      //$MyAlarm->logout();
      return $MyAlarm->systemState;
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
          $eqlogic->checkAndUpdateCmd('status', $status); // on met à jour la commande avec le LogicalId "story"  de l'eqlogic
          break;
		}
    }

    /*     * **********************Getteur Setteur*************************** */
}
