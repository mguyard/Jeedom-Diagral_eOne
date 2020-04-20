
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


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" /> {{Historiser}}<br/></span>';
    tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" /> {{Affichage}}<br/></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}


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
