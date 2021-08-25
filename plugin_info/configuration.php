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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
$plugin_version = config::byKey('plugin_version', 'Diagral_eOne');
if (empty($plugin_version)) {
    $plugin_version = "Development Version";
}
?>
<p class="font-weight-bold text-right"><b>Version : <?php echo $plugin_version ?></b></p>
<form class="form-horizontal">
    <fieldset>
        <legend>
			<i class="fas fa-key"></i> {{Compte Diagral}}
		</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Identifiant}}
                <sup><i class="fa fa-question-circle tooltips" title="Saisissez votre identifiant tels que vous l'utilisez sur l'application mobile Diagral E-One" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="login" type="email" placeholder="Adresse email utilisée pour votre compte Diagral"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Mot de passe}}
                <sup><i class="fa fa-question-circle tooltips" title="Saisissez votre mot de passe tels que vous l'utilisez sur l'application mobile Diagral E-One" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="password" type="password" placeholder="Mot de passe utilisé pour votre compte Diagral"/>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>
			<i class="fas fa-wrench"></i> {{Configuration}}
		</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nombre de tentatives}}
                <sup><i class="fa fa-question-circle tooltips" title="Nombre de tentative que le plugin effectuera en cas d'échec de communication avec le Cloud Diagral" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="retry" type="number" min="1" max="10" placeholder="1"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Délai entre les tentatives (secondes)}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="waitRetry" type="number" min="5" placeholder="5"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Mise à jour automatique (minutes)}}
                <sup><i class="fa fa-question-circle tooltips" title="Fréquence à laquelle le plugin va récupérer les informations sur le cloud Diagral" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="polling_interval" type="number" min="1" placeholder="10"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nombre de video à conserver}}
                <sup><i class="fa fa-question-circle tooltips" title="Le plugin peut récupérer et stocker les vidéos effectuées par un détecteur à image Diagral.<br/>Ce paramètre permet de définir le nombre de vidéo a conserver locallement dans Jeedom" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="video_retention" type="number" min="1" placeholder="100"/>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>
			<i class="fas fa-chart-line"></i> {{Suivi d'installation}}
		</legend>
        <div class="alert">
            <i>Voir la <a href="https://mguyard.github.io/Jeedom-Documentations/fr_FR/Diagral_eOne/documentation#Suivi%20d’installation">documentation</a> pour plus d'informations sur ce point.</i>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Activation de l'envoi des informations}}</label>
            <div class="col-lg-2">
                <input type="checkbox" class="configKey form-control" data-l1key="InstallBaseStatus" checked="1"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Envoi d'information anonymisée uniquement}}</label>
            <div class="col-lg-2">
                <input type="checkbox" class="configKey form-control" data-l1key="InstallBaseAnonymousOnly" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Clé unique de votre installation Jeedom}}</label>
            <div class="col-lg-4">
                <span class="label label-info"><?php echo jeedom::getHardwareKey() ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Plateforme Jeedom}}</label>
            <div class="col-lg-4">
                <span class="label label-info"><?php echo jeedom::getHardwareName() ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Votre login Market}}</label>
            <div class="col-lg-4">
                <span class="label label-info"><?php echo config::byKey('market::username') ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Version Jeedom}}</label>
            <div class="col-lg-4">
                <span class="label label-info"><?php echo jeedom::version() ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Version du plugin}}</label>
            <div class="col-lg-4">
                <span class="label label-info"><?php echo $plugin_version ." (".Diagral_eOne::getPluginBranch().")" ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Adresse Email}}
                <sup><i class="fa fa-question-circle tooltips" title="Saisissez votre email si vous souhaitez recevoir les communications du developpeur.<br/>Estimé : 2 emails maximum par an" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="InstallBaseEmailAddr" type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" placeholder="Saisissez votre email"/>
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-4"></div>
            <div class="col-lg-4">
                <button type="button" id="delete_remote_datainfo" class="btn btn-danger btn-lg">Supprimer mes données</button>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>
			<i class="fas fa-spider"></i> {{Debug}}
		</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Verbose}}</label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="verbose" disabled>
                    <option value="1">Enable</option>
                    <option value="0">Disable</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Générer un package de DiagDebug}}
                <sup><i class="fa fa-question-circle tooltips" title="Permet de générer une archive de support incluant les données nécéssaires au développeur ou à la communauté afin de vous aider" style="font-size:1em;color:grey;"></i></sup>
            </label>
            <div class="col-lg-4">
                <button type="button" id="generateDiagDebug" class="btn btn-danger btn-lg">{{Générer un DiagDebug}}</button>
            </div>
        </div>

    <script>
        function Diagral_eOne_postSaveConfiguration(){
            $.ajax({// fonction permettant de faire de l'ajax
                type: "POST", // methode de transmission des données au fichier php
                url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
                data: {
                    action: "postSave",
                    //Let's send previous values as parameters, to be able to set them back in case of bad values
                    login: "<?php echo config::byKey('login', 'Diagral_eOne'); ?>",
                    password: "<?php echo config::byKey('password', 'Diagral_eOne'); ?>",
                    retry: "<?php echo config::byKey('retry', 'Diagral_eOne', 1); ?>",
                    default_retry: "1",
                    waitRetry: "<?php echo config::byKey('waitRetry', 'Diagral_eOne', 5); ?>",
                    default_waitRetry: "5",
                    polling_interval: "<?php echo config::byKey('polling_interval', 'Diagral_eOne',10); ?>",
                    default_polling_interval: "10",
                    InstallBaseStatus: "<?php echo config::byKey('InstallBaseStatus', 'Diagral_eOne',1); ?>",
                    InstallBaseEmailAddr: "<?php echo config::byKey('InstallBaseEmailAddr', 'Diagral_eOne'); ?>",
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#ul_plugin .li_plugin[data-plugin_id=Diagral_eOne]').click();
                }
            });
        }

        // Supprime les données utilisateurs présente sur le cloud Developpeur
        $('#delete_remote_datainfo').click( function() {
            $.ajax({// fonction permettant de faire de l'ajax
                type: "POST", // methode de transmission des données au fichier php
                url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
                data: {
                    action: "delete_remote_datainfo",
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#div_alert').showAlert({message: '{{Données de suivi d\'instalaltion supprimée avec succès}}', level: 'success'});
                    setTimeout( function() {
                        location.reload();
                    }, 2000);
                }
            });
        });


        // Génère le package DiagDebug
        $('#generateDiagDebug').click( function() {
            $.ajax({// fonction permettant de faire de l'ajax
                type: "POST", // methode de transmission des données au fichier php
                url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
                data: {
                    action: "generateDiagDebug",
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#div_alert').showAlert({message: '{{<i class="fas fa-file-download"></i> Génération du DiagDebug effectuée avec succès. Cliquez <a href="' + data.result.relativePath + '" target="_blank" download>ici</a> pour télécharger l\'archive}}', level: 'success'});
                }
            });
        });
    </script>

  </fieldset>
</form>
