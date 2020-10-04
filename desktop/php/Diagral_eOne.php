<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('Diagral_eOne');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

// Liste des plugins de Notification compatible
$pluginCompatible = array(
    'maillistener' => 'Mail Listener'
);

?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <!--
            <div class="cursor eqLogicAction logoPrimary" style="color:#c6d92d;" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
            -->
            <div class="cursor eqLogicAction logoSecondary" data-action="synchronize">
                <i class="fas fa-sync"></i>
                <br>
                <span>{{Synchronisation}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuration}}</span>
            </div>
        </div>
    <legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
	<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
    <div class="eqLogicThumbnailContainer">
        <?php
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
            echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
            echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
            echo '<br>';
            echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
        <span class="input-group-btn">
            <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
		</span>
	</div>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
        <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
        <li role="presentation"><a href="#badges" aria-controls="badges" role="tab" data-toggle="tab"><i class="fa fa-id-badge"></i> {{Badges}}</a></li>
        <li role="presentation"><a href="#notificationDiagral" aria-controls="notificationDiagral" role="tab" data-toggle="tab"><i class="fas fa-envelope-open-text"></i></i> {{Notifications Diagral}}</a></li>
        <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
        <div role="tabpanel" class="tab-pane active" id="eqlogictab">
            <br/>
            <form class="form-horizontal">
                <fieldset>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                            <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement template}}"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                        <div class="col-sm-3">
                            <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                <option value="">{{Aucun}}</option>
                                <?php
                                foreach (jeeObject::all() as $object) {
                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
	                <div class="form-group">
                        <label class="col-sm-3 control-label">{{Catégorie}}</label>
                        <div class="col-sm-9">
                            <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"></label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                            <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                    </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Master Code}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="mastercode" placeholder="Master Code"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{System Id}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="systemid" placeholder="System ID" disabled/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Sécurisation désarmement}}</label>
                        <div class="col-sm-3">
                            <input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="secureDisarm"/>
                        </div>
                    </div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <br/>
                        <a class="btn btn-info btn-sm cmdAction" id="bt_showEvents"><i class="fas fa-info"></i> {{Journal d'activité}}</a>
                    </div>
                </fieldset>
            </form>
        </div>
        <div role="tabpanel" class="tab-pane" id="notificationDiagral">
            <br/>
            <form class="form-horizontal col-sm-6">
                <fieldset>
                    <div class="form-group">
                        <label class="col-sm-6 control-label">{{Choix du plugin de notification}}</label>
                        <div class="col-sm-6">
                            <select class="eqLogicAttr form-control" id="notificationPlugin" data-l1key="configuration" data-l2key="notificationPlugin">
                                <option disabled selected value="FAKEPLUGINTOBLOCKLIST"> -- {{Choisissez un plugin compatible}} -- </option>
                                <?php
                                // Generation de la liste des plugins compatibles
                                foreach ($pluginCompatible as $pluginID => $pluginName) {
                                    print "<option value='" . $pluginID . "'" . $fieldSelected . ">" . $pluginName . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-6 control-label">{{Commande de réception de notification}}</label>
                        <div class="col-sm-6 input-group">
                            <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="notificationEqLogic"/>
                            <span class="input-group-btn">
                                <a class="btn btn-default cursor" title="Rechercher l'équipement de notification" id="notificationEqLogic"><i class="fas fa-list-alt"></i></a>
                            </span>
                        </div>
                    </div>
                    <div id="divNotificationScenarioName" class='form-group' hidden>
                        <label class='col-sm-6 control-label'>{{Nom du scénario de réception de notification}}</label>
                        <div id='notificationScenarioName' class='col-sm-6'>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6"></div>
                            <div id="notificationButtonCreate" class="col-sm-6" hidden>
                                <br/>
                                <button type='button' id='notificationGenerateScenario' class='btn btn-success btn-lg'>Génération automatique du scénario</button>
                            </div>
                            <div id="notificationButtonUpdate" class="col-sm-6" hidden>
                                <br/>
                                <div class='col-sm-4'><button type='button' id='notificationUpdateScenario' class='btn btn-warning btn-lg'>Mise à jour du scénario</button></div>
                                <div class='col-sm-4'><button type='button' id='notificationDeleteScenario' class='btn btn-danger btn-lg'>Suppression du scénario</button></div>
                            </div>
                    </div>
                </fieldset>
            </form>
            <div class="alert alert-info col-sm-6">
                Afin d'éviter de requêter trop regulièrement le Cloud Diagral, le plugin peut recevoir les notifications de Diagral.<br/>
                Les notifications peuvent être de types :
                <ul>
                    <li>Activation/Désactivation d'alarme - qui lancera une action de refresh sur le plugin</li>
                    <li>Détection d'intrusion - qui activera la commande "Alarme déclenchée"</li>
                </ul>
                <br/>
                Actuellement seul les plugins suivants sont officiellement supportés pour recevoir de façon automatisée les alertes (au travers du bouton "Génération du scénario" ci-dessous).
                <ul>
                    <?php
                    foreach ($pluginCompatible as $pluginID => $pluginName) {
                        print "<li><a href='https://market.jeedom.com/index.php?v=d&p=market&type=plugin&&name=" . urlencode($pluginName) . "'>". $pluginName . "</a></li>";
                    }
                    ?>
                </ul>
                <i>Pour supporter d'autres plugins n'hésitez pas à contacter le developpeur <a href="https://github.com/mguyard/Jeedom-Diagral_eOne/issues/new">en ouvrant une "Demande d'evolution" sur le Github du plugin</a></i>
                <br/><br/>
                Se référer à la <a target="_blank" href="https://mguyard.github.io/Jeedom-Diagral_eOne/fr_FR/">documentation</a> pour plus de détails.
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="badges">
            <br/>
            <form class="form-horizontal">
                <fieldset>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 1 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge1-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 2 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge2-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 3 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge3-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 4 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge4-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 5 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge5-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 6 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge6-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 7 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge7-alias" placeholder="Alias"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Badge 8 - Alias}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="badge8-alias" placeholder="Alias"/>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
        <div role="tabpanel" class="tab-pane" id="commandtab">
            <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
            <table id="table_cmd" class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>{{Nom}}</th><th>{{Type}}</th><th>{{Configuration}}</th><th>{{Action}}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>


<?php include_file('desktop', 'Diagral_eOne', 'js', 'Diagral_eOne');?>
<?php include_file('core', 'plugin.template', 'js');?>
