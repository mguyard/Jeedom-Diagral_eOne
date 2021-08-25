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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

// Recupere l'Eq correspond à l'equipement
$eqLogic = eqLogic::byId(init(id));
// Récupère la liste des groupes pour cet equipement
$groupArray = $eqLogic->generateGroupsList();
// Récupère les evenements jusqu'a 1 mois en arrière
$events = Diagral_eOne::getEvents(init(id), date("Y-m-d 00:00:00",strtotime("-1 month")));

// Extrait du details les informations utiles
function getAction($details) {
    $arrayDetails = explode(' / ', $details);
    $cmdReceived = explode(' : ', $arrayDetails[0]);
    $cmdFinal = explode(' : ', $arrayDetails[1]);
    $cmdAuthor = $arrayDetails[2];
    return array(
        "cmdReceived" => $cmdReceived[1],
        "cmdFinal" => $cmdFinal[1],
        "cmdAuthor" => $cmdAuthor
    );
}

// Sublime le texte
function magnifyText($title, $details) {
    // Si aucun details, alors on met le titre a la place
    if (empty($details)) {
        $details = $title;
    }
    // On modifie le titre pour y mettre une icone
    $title = getIcon($title, $details);
    // Retour à la ligne a la place des Slash de séparation
    $details = str_replace(' / ', '<br/>', $details);
    // Si l'evenement correspond a une detection, on met le detail en gras
    if (preg_match('/^Détection.*/', $title)) {
        $details = '<b>' . $detals . '</b>';
    }

    return array (
        'title' => $title,
        'details' => $details
    );
}

// Defini l'icone pour chaque type d'evenement
function getIcon($title, $details) {
    switch ($title) {
        case 'Marche / Arrêt':
            $action = getAction($details);
            switch ($action['cmdReceived']) {
                case 'Arrêt':
                    $icon = 'icon_green jeedomapp-lock-ouvert';
                    break;
                case 'Marche présence':
                    $icon = 'fas jeedomapp-night';
                    break;
                case 'Marche groupe':
                    $icon = 'icon_red jeedomapp-lock-ferme';
                    break;
            }
            break;
        case (preg_match('/^Détection.*/', $title) ? true : false) :
            $icon = 'icon_red fas fa-eye';
            break;
        case 'Disponibilité média':
            $icon = 'icon_red fas fa-ethernet';
            break;
        case 'Réseau ADSL':
            $icon = 'icon_red fas fa-cloud-upload-alt';
            break;
        case 'Accès distant via le transmetteur':
            $icon = 'icon_red fas fa-network-wired';
            break;
        default:
            $icon = 'icon_blue fas fa-question';
            break;
    }
    return "<span title='" . $title . "'><i class='fa-2x " . $icon . "'></i></span>";
}

// Traduit les id de groupes en texte
function translateGroupsId($groups, $groupArray) {
    $groupsTranslated = "";
    foreach ($groups as $group) {
        $groupsTranslated = $groupsTranslated . "<br/>" . $groupArray[$group];
    }
    return preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $groupsTranslated);
}

?>

<div class="container">
	<h2>Journal d'activité - Diagral eOne</h2>
	<br/><br/>
	<table class="table">
        <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{Date}}</th>
                <th scope="col">{{Action}}</th>
                <th scope="col">{{Détails}}</th>
                <th scope="col">{{Equipement}}</th>
                <th scope="col">{{Groupes}}</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($events as $key => $event) {
                $magnifyText = magnifyText($event['title'], $event['details']);
                echo "<tr>";
                    echo "<th scope='row'>" . $key . "</th>";
                    echo "<td>" . $event['date'] . "</td>";
                    echo "<td>" . $magnifyText['title'] . "</td>";
                    echo "<td>" . $magnifyText['details'] . "</td>";
                    echo "<td>" . $event['device'] . "</td>";
                    echo "<td>" . translateGroupsId($event['groups'], $groupArray) . "</td>";
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>

