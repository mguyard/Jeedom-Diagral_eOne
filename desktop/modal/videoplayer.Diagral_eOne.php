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

// Base du plugin
$plugin = plugin::byId('Diagral_eOne');
$pluginBasePath = dirname($plugin->getFilepath(), 2);

// Récupere la video passé en argument
$videoArg = init(video);

?>

<div class='container'>
    <div class="col-lg-12">
        <video class='video-fluid z-depth-1' height='100%' width='100%' controls autoplay allowfullscreen>
            <source src='core/php/downloadFile.php?pathfile=<?php echo $pluginBasePath.$videoArg ?>' type='<?php echo mime_content_type($pluginBasePath.$videoArg)?>'>
            <p>Votre navigateur ne prend pas en charge les vidéos HTML5. Voici <a href="core/php/downloadFile.php?pathfile=<?php echo $pluginBasePath.$videoArg ?>">un lien pour télécharger la vidéo</a>.</p>
        </video>
    </div>
</div>
