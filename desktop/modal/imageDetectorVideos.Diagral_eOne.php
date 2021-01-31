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
// Lance la récuperations des videos disponibles
$videosList = $eqLogic->listImageDetectorVideos(False);
// Tri le tableau en descendant (date la plus recente en haut)
usort($videosList, function ($item1, $item2) {
    return $item2['timestamp'] <=> $item1['timestamp'];
});

function getIcon($type) {
    switch ($type) {
        case 'ONDEMAND':
            $icon = 'icon_blue fas fa-hand-point-up';
            break;
        default:
            $icon = '';
    }
    return "<span title='" . $type . "'><i class='fa-2x " . $icon . "'></i></span>";
}

?>

<div class="container">
	<h2>Liste des videos <?php $eqLogic->getName() ?> - Diagral eOne</h2>
	<br/><br/>
	<table class="table">
        <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{Date}}</th>
                <th scope="col">{{Type}}</th>
                <th scope="col">{{Id}}</th>
                <th scope="col">{{Format}}</th>
                <th scope="col">{{Durée (secondes)}}</th>
                <th scope="col">{{Actions}}</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($videosList as $key => $video) {
                echo "<tr>";
                    echo "<th scope='row'>" . $key . "</th>";
                    $date = new \DateTime('now', new \DateTimeZone(config::byKey('timezone')));
                    $date->setTimestamp($video['timestamp']);
                    echo "<td>" . $date->format('Y-m-d H:i:s') . "</td>";
                    echo "<td>" . getIcon($video['type']) . "</td>";
                    echo "<td>" . $video['id'] . "</td>";
                    echo "<td>" . $video['format'] . "</td>";
                    echo "<td>" . $video['durationMs'] / 1000 . "</td>";
                    echo "<td>
                        <a href='/plugins/Diagral_eOne/data/videos/".$eqLogic->getConfiguration('type')."/".$eqLogic->getConfiguration('index')."/".$video['timestamp'].".mp4' target='_blank'> <i class='icon_green fas fa-2x fa-play-circle'></i></a>
                    </td>";
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
