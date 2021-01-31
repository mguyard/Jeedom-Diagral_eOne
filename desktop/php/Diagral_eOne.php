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
            <div class="cursor eqLogicAction logoSecondary" onclick="window.open('https:\/\/mguyard.github.io/Jeedom-Documentations/fr_FR/Diagral_eOne/documentation', '_blank')">
                <i class="fas fa-book"></i>
                <br>
                <span>{{Documentation}}</span>
            </div>
        </div>

        <!-- Champ de recherche -->
		<div class="input-group" style="margin:5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
			</div>
		</div>

        <!-- Liste des Centrale -->
        <legend><i class="fas fa-table"></i> {{Mes centrales}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getConfiguration('type', '') != 'centrale') continue;
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Liste des Detecteurs a Image -->
        <legend><i class="fas fa-table"></i> {{Mes detecteurs à image}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getConfiguration('type', '') != 'imagedetector') continue;
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div> <!-- /.eqLogicThumbnailDisplay -->

    <!-- Page de présentation de l'équipement -->
    <div class="col-xs-12 eqLogic" style="display: none;">
        <!-- barre de gestion de l'équipement -->
        <div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs">  {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
        <!-- Onglets -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation" class="eqCustom eqCentrale"><a href="#badges" aria-controls="badges" role="tab" data-toggle="tab"><i class="fa fa-id-badge"></i> {{Badges}}</a></li>
            <li role="presentation" class="eqCustom eqCentrale"><a href="#notificationDiagral" aria-controls="notificationDiagral" role="tab" data-toggle="tab"><i class="fas fa-envelope-open-text"></i></i> {{Notifications Diagral}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>
        <div class="tab-content">
            <!-- Onglet de configuration de l'équipement -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <div class="row">
					<!-- Partie gauche de l'onglet "Equipements" -->
					<!-- Paramètres généraux de l'équipement -->
					<div class="col-lg-7">
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
                                    <label class="col-sm-3 control-label">{{Type}}</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type" disabled/>
                                    </div>
                                </div>
                                <br/>
                                <div><legend><i class="fas fa-cog"></i> {{Paramètres}}</legend></div>
                                <!-- Template for Centrale -->
                                <div class="eqCustom eqCentrale">
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
                                </div>
                                <!-- Template for Image Detectors -->
                                <div class="eqCustom eqImagedetector">
                                <div class="form-group">
                                        <label class="col-sm-3 control-label">{{Video Auto Download}}
                                            <sup><i class="fa fa-question-circle tooltips" title="Recupère de façon automatique les videos disponibles" style="font-size:1em;color:bleu;"></i></sup>
                                        </label>
                                        <div class="col-sm-3">
                                            <input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autoDlVideo"/>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{Centrale}}
                                            <sup><i class="fa fa-question-circle tooltips" title="Lien vers la centrale qui gère ce détecteur" style="font-size:1em;color:bleu;"></i></sup>
                                        </label>
                                        <div id="centralLink" class="col-sm-3">
                                            <!-- Import button by JS -->
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
					<!-- Partie droite de l'onglet "Equipement" -->
					<!-- Affiche l'icône du plugin par défaut mais vous pouvez y afficher les informations de votre choix -->
                    <div class="col-lg-5">
                        <legend><i class="fas fa-info"></i> {{Informations}}</legend>
                        <div class="form-group">
                            <div class="text-center">
                                <img name="icon_visu" src="<?= $plugin->getPathImgIcon(); ?>" style="max-width:160px;"/>
                            </div>
                            <div class="eqCustom eqCentrale">
                                <div><legend><i class="fas fa-info"></i> {{Journal d'activité}}</legend></div>
                                <div class="col-sm-3"></div>
                                <div class="col-sm-6">
                                    <a class="btn btn-info btn-sm cmdAction" id="bt_showEvents"><i class="fas fa-info"></i> {{Consulter}}</a>
                                </div>
                            </div>
                            <div class="eqCustom eqImagedetector">
                                <div><legend><i class="fas fa-info"></i> {{Liste des vidéos disponibles}}</legend></div>
                                <div class="col-sm-3"></div>
                                <div class="col-sm-6">
                                    <a class="btn btn-info btn-sm cmdAction" id="bt_showImagedetectorVideos"><i class="fas fa-video"></i> {{Consulter}}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /.row-->
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

            <!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br/><br/>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th>{{Id}}</th>
								<th>{{Nom}}</th>
								<th>{{Type}}</th>
								<th>{{Options}}</th>
								<th>{{Paramètres}}</th>
								<th>{{Action}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

        </div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'Diagral_eOne', 'js', 'Diagral_eOne');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
