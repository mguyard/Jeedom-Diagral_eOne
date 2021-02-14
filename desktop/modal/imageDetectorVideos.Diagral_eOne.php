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
// Base du plugin
$plugin = plugin::byId('Diagral_eOne');
$pluginBasePath = dirname($plugin->getFilepath(), 2);
// Lance la récuperations des videos disponibles
$videosList = $eqLogic->listImageDetectorVideos(False);

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
	<h2>Liste des videos "<?php echo $eqLogic->getName() ?>"</h2>
	<br/><br/>
    <?php
        if (empty($videosList)) {
            echo "<b>Aucune videos disponibles</b>";
            exit(0);
        }
    ?>
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
                // Si il y a plus de videos que le nombre maximale a stocker, on ne les affiches plus.
                if ($key >= config::byKey('video_retention', 'Diagral_eOne')) {
                    break;
                }
                $video_path = '/data/videos/'.$eqLogic->getConfiguration('type').'/'.$eqLogic->getLogicalId().'/'.$video['timestamp'].'.mp4';
                echo "<tr>";
                    echo "<th scope='row'>" . $key . "</th>";
                    $date = new \DateTime('now', new \DateTimeZone(config::byKey('timezone')));
                    $date->setTimestamp($video['timestamp']);
                    echo "<td>" . $date->format('Y-m-d H:i:s') . "</td>";
                    echo "<td>" . getIcon($video['type']) . "</td>";
                    echo "<td>" . $video['id'] . "</td>";
                    echo "<td>" . $video['format'] . "</td>";
                    echo "<td>" . $video['durationMs'] / 1000 . "</td>";
                    echo "<td>";
                        if ($eqLogic->getConfiguration('autoDlVideo', '0') == '1' && file_exists($pluginBasePath.$video_path)) {
                            echo "<a href='/plugins/Diagral_eOne".$video_path."' target='_blank'> <i class='icon_green fas fa-2x fa-play-circle'></i></a>";
                        } else {
                            echo "<p class='state tooltips tooltipstered' title='Video non disponible car la configuration Video Auto Download est désactivée sur le détecteur ou bien le téléchargement de la vidéo a échoué (consultez les logs).'> <i class='icon_red fas fa-2x fa-video-slash'></i></p>";
                        }
                    echo "</td>";
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>
