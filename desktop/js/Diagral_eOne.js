
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


/*
* Permet la réorganisation des commandes dans l'équipement
*/
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});


$('.eqLogicAction[data-action=synchronize]').on('click', function (e) {
  $('#div_alert').showAlert({message: '{{Synchronisation en cours}}', level: 'warning'});
  $.ajax({// fonction permettant de faire de l'ajax
    type: "POST", // methode de transmission des données au fichier php
    url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
    data: {
      action: "synchronize"
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
        $('#div_alert').showAlert({message: '{{Synchronisation réalisée avec succès}}', level: 'success'});
        setTimeout( function() {
          location.reload();
        }, 2000);
      }

  });
});


// Recherche d'équipement pour les notifications
$('#notificationEqLogic').on('click', function () {
    var plugin = document.getElementById("notificationPlugin").value;
    jeedom.eqLogic.getSelectModal({eqLogic: {eqType_name: plugin}}, function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=notificationEqLogic]').value(result.human);
    });
});

// S'execute quand on entre dans l'onglet de Notification Diagral
$(window).on('hashchange load', function() {
    if (location.hash === "#notificationDiagral") {
        var urlParams = new URLSearchParams(window.location.search);
        var eqId = urlParams.getAll('id').toString();
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
            data: {
                action: "notificationVerifyScenario",
                eqID: eqId,
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                var JSONreturn = JSON.parse(data.result);
                if(JSONreturn['scenarioExist']) {
                    $( "#notificationButtonUpdate" ).show();
                    $( "#divNotificationScenarioName" ).show();
                    $( "#notificationScenarioName" ).empty();
                    $( "#notificationScenarioName" ).append( "<span class='label label-info'><a target='_blank' href='/index.php?v=d&p=scenario&id=" + JSONreturn['scenarioID'] +"'>" + JSONreturn['scenarioName'] + "</a></span>" )
                } else {
                    $( "#notificationButtonCreate" ).show();
                }
            }
        });
    }
});


// Genere ou met à jour le scénario de Notification
$('#notificationGenerateScenario, #notificationUpdateScenario').click( function() {
    var urlParams = new URLSearchParams(window.location.search);
    var eqId = urlParams.getAll('id').toString();
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
        data: {
            action: "notificationGenerateUpdateScenario",
            eqID: eqId,
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
            $('#div_alert').showAlert({message: '{{Création/Modification du scénario de notification Diagral réalisée avec succès}}', level: 'success'});
            setTimeout( function() {
                location.reload();
            }, 2000);
        }
    });
});

// Supprime le scénario de notification
$('#notificationDeleteScenario').click( function() {
    var urlParams = new URLSearchParams(window.location.search);
    var eqId = urlParams.getAll('id').toString();
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
        data: {
            action: "notificationDeleteScenario",
            EqId: eqId,
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
            $('#div_alert').showAlert({message: '{{Suppression du scénario de notification Diagral réalisée avec succès}}', level: 'success'});
            setTimeout( function() {
                location.reload();
            }, 2000);
        }
    });
});

// Affiche la modale des Evenements
$('#bt_showEvents').on('click',function() {
	$('#md_modal').dialog({title: "{{Journal d'activité}}"});
    $('#md_modal').load('index.php?v=d&plugin=Diagral_eOne&modal=events.Diagral_eOne&id='
        + $('.eqLogicAttr[data-l1key=id]').value()
        )
        .dialog('open');
});

// Affiche la modale des videos d'ImageDetector / Cameras
$('#bt_showVideos').on('click',function() {
	$('#md_modal').dialog({title: "{{Liste des videos disponibles}}"});
    $('#md_modal').load('index.php?v=d&plugin=Diagral_eOne&modal=videos.Diagral_eOne&id='
        + $('.eqLogicAttr[data-l1key=id]').value()
        )
        .dialog('open');
});


// Application des templates d'affichage
$('.eqLogicAttr[data-l1key=id], .eqLogicAttr[data-l1key=configuration][data-l2key=type]').on('change', function () {
    var eqLogicId = $('.eqLogicAttr[data-l1key=id]').val();
    var type = $('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').val();
    // Custom Template
    $('.eqCustom').hide();
    var cssClass = '.eq' + type.charAt(0).toUpperCase() + type.slice(1);
    $(cssClass).show();
    // Generation du lien vers la centrale
    if ($.inArray(type, ['module', 'imagedetector', 'camera', 'adyx-portal', 'adyx-shutter', 'adyx-garage_door', 'knx-shutter', 'knx-light'])) {
        $('.eqCentralLink').show();
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
            data: {
                action: "generateCentralLink",
                eqID: eqLogicId,
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                var JSONreturn = JSON.parse(data.result);
                if(JSONreturn['centraleId'] !== "") {
                    $( "#centralLink" ).empty();
                    $( "#centralLink" ).append( "<a class='btn btn-info btn-sm cmdAction' href='/index.php?v=d&m=Diagral_eOne&p=Diagral_eOne&id=" + JSONreturn['centraleId'] + "' target='_blank'><i class='fas fa-info'></i> {{Voir}}</a>" )
                }
            }
        });
    }
    // Si on a un EqLogicId alors on change l'image selon le type d'équipement
    if (eqLogicId) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
        data: {
            action: "getIconPath",
            eqLogicId: eqLogicId,
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
            var JSONreturn = JSON.parse(data.result);
            if(JSONreturn['iconPath']) {
                $("img[name*='icon_visu']").attr("src", JSONreturn['iconPath']);
            }
        }
        });
    }
    // Si on a un EqLogicId alors on rempli l'onglet des équipements associés
    if (eqLogicId) {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/Diagral_eOne/core/ajax/Diagral_eOne.ajax.php", // url du fichier php
            data: {
                action: "getChildDevices",
                eqLogicId: eqLogicId,
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                var JSONreturn = JSON.parse(data.result);
                $( "#childDeviceList" ).empty();
                $( "#childDeviceList" ).append( "<ul>" );
                //console.log(JSONreturn);
                $.each(JSONreturn, function(i, item) {
                    //console.log(JSONreturn[i].name);
                    $( "#childDeviceList" ).append( "<li><a target='_blank' href='/index.php?v=d&m=Diagral_eOne&p=Diagral_eOne&id=" + JSONreturn[i].id + "'>" +  JSONreturn[i].name + "</a></li>")
                });
                $( "#childDeviceList" ).append( "</ul>" );
            }
            });
        }
});