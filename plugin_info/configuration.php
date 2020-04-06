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
?>
<form class="form-horizontal">
    <fieldset>
        <legend>
			<i class="fa fa-list-alt"></i> {{Compte Diagral}}
		</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Identifiant}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="login" type="email" placeholder="Adresse email utilisée pour votre compte Diagral"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Mot de passe}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="password" type="password" placeholder="Mot de passe utilisé pour votre compte Diagral"/>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>
			<i class="fa fa-list-alt"></i> {{Configuration}}
		</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nombre de tentatives}}</label>
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
            <label class="col-lg-4 control-label">{{Mise à jour automatique (minutes)}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="polling_interval" type="number" min="1" placeholder="10"/>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>
			<i class="fa fa-list-alt"></i> {{Communication par le developpeur}}
		</legend>
        <div class="alert alert-info">
            Ce plugin étant gratuit, Jeedom ne propose pas au developpeur le suivi des installations de son plugin.<br/>
            Ce plugin inclut un système de suivi des installations ayant pour objectif :
            <ul>
                <li>Suivre le nombre d'installation du plugin (à titre purement informatif pour le developpeur)</li>
                <li>Avoir un moyen de communication avec les utilisateurs du plugin (envoi de mail ou message privée sur Community)</li>
            </ul>
            <br/>
            Concernant les communications en provenance du développeur, elle seront <b>faibles</b> et avec <b>objectif de pouvoir échanger sur les nouveautés et évolutions à venir</b>.
            <br/>
            Afin d'assurer une totale transparence sur les données collectées, elle s'affiche ci-dessous :
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
            <label class="col-lg-4 control-label">{{Votre login Jeedom::Market}}</label>
            <div class="col-lg-4">
                <span class="label label-info"><?php echo config::byKey('market::username') ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Adresse Email}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="InstallBaseEmailAddr" type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" placeholder="Saisissez votre email si vous souhaitez recevoir les communications du developpeur"/>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>
			<i class="fa fa-list-alt"></i> {{Debug}}
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
    </script>

  </fieldset>
</form>
