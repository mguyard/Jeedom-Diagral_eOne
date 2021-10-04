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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');


/**
 * Typical URL
 * http://<jeedom-vhost>/plugins/Diagral_eOne/core/php/webhook.php?apikey=<apikey>&action=refresh&eq=<eqId>
 * Action and Eq are optionals
 */

/**
 * Fonction pour retourner une erreur
 */
function sendError($message) {
    header("Status: 403 Forbidden");
	header('HTTP/1.0 404 Forbidden');
	$_SERVER['REDIRECT_STATUS'] = 404;
	echo "<h1>403 Forbidden</h1>";
	echo $message;
    log::add('Diagral_eOne', 'info', "│ Error : " . $message );
    log::add('Diagral_eOne', 'info',"└────────── End Webhook");
	die();
}

log::add('Diagral_eOne', 'info', "┌────────── Received Webhook");
log::add('Diagral_eOne', 'info', "│ Source IP : ".getClientIp());

/**
 * Si l'utilisateur est banni
 */
if (user::isBan()) {
    sendError("The page that you have requested could not be found.");
}

/**
 * Si la clé API ne correspond pas à la clé du plugin
 */
if (!jeedom::apiAccess(init('apikey'), 'Diagral_eOne')) {
    user::failedLogin();
    sleep(5);
    sendError("Bad API Key- Receive " . init('apikey'));
}



/**
 * Definition des options et leur valeurs par défaut
 */

// Si l'action n'est pas spécifiée on défini que c'est un refresh sinon on verifie la valeur specifiée
if (empty(init('action'))) {
    $action = 'refresh';
    $actionDetails = '';
} else {
    $actionAllowedValue = array('refresh');
    if(in_array(init('action'), $actionAllowedValue)) {
        $action = init('action');
    } else {
        sendError("Action (" . init('action') . ") specified in 'action' parameter is not allowed. Request aborted");
    } 
}
// Si l'Eqlogic n'est pas specifié on refresh tout sinon on verifie la valeur
if (empty(init('eq'))) {
    $eq = 'all';
    $eqDetails = '';
} else {
    $eq = init('eq');
    $eqLogic = eqLogic::byId($eq);
    if (is_object($eqLogic)) {
        $eqDetails = "(". $eqLogic->getName() .")";
        // Si l'ID de l'équipement ne correspond pas à un ID d'un équipement du plugin Diagral_eOne
        if ($eqLogic->getEqType_name() != 'Diagral_eOne') {
            sendError("Device ID (" . $eqLogic->getId() . " - " . $eqLogic->getName() . ") specified in 'eq' parameter is not a device in Diagral_eOne plugin. Request aborted");
        }
        // Si l'équipement n'est pas actif
        if ($eqLogic->getIsEnable() == 0) {
            sendError("Device ID (" . $eqLogic->getId() . " - " . $eqLogic->getName() . ") is not active in Jeedom. Request aborted");
        }
        // Si l'équipement n'a pas de commande correspondante à l'action
        $cmd = $eqLogic->getCmd('action', $action);
        if (!is_object($cmd)) {
            sendError("Command '". $action ."' don't exist for Device ID (" . $eqLogic->getId() . " - " . $eqLogic->getName() . "). Request aborted");
        } else {
            $actionDetails = "(".$cmd->getName().")";
        }
    } else {
        sendError("Device ID (" . $eq . ") specified in 'eq' parameter is not a Jeedom device. Request aborted");
    }
}

log::add('Diagral_eOne', 'info', "│ Action : " . $action . " " . $actionDetails);
log::add('Diagral_eOne', 'info', "│ EqLogic : " . $eq . " " . $eqDetails);
log::add('Diagral_eOne', 'info',"└────────── End Webhook");

/**
 * Execution de la commande
 */

if ($eq == 'all') {
    Diagral_eOne::pull();
} else {
    $cmd->execute();
}

/**
 * Affichage d'un retour
 */
header("Status: 200 OK");
header('HTTP/1.0 200 OK');
$_SERVER['REDIRECT_STATUS'] = 200;
echo "<h1>Commande Transmise</h1>";
echo "Execution de la commande '".$action."' ".$actionDetails." sur l'équipement '".$eq."' ".$eqDetails;


