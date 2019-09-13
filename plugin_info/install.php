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

function Diagral_eOne_install() {
    Diagral_eOne_Cron_Pull('create');
    Diagral_eOne_Cron_JSON('create');
}

function Diagral_eOne_update() {
    Diagral_eOne_Cron_Pull('update');
    Diagral_eOne_Cron_JSON('update');
}


function Diagral_eOne_remove() {
    Diagral_eOne_Cron_Pull('remove');
    Diagral_eOne_Cron_JSON('remove');
}

function Diagral_eOne_Cron_Pull($action) {
    $cron = cron::byClassAndFunction('Diagral_eOne', 'pull');
    switch ($action) {
        case 'create':
            if ( ! is_object($cron)) {
                $cron = new cron();
                $cron->setClass('Diagral_eOne');
                $cron->setFunction('pull');
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setTimeout(2);
                $cron->setSchedule('*/10 * * * *');
                $cron->save();
            }
            break;
        case 'remove':
            if (is_object($cron)) {
                $cron->remove(true);
            }
            break;
        case 'update':
            if ( ! is_object($cron)) {
                Diagral_eOne_Cron_Pull('create');
            }
            break;
    }
}

function Diagral_eOne_Cron_JSON($action) {
    $cron = cron::byClassAndFunction('Diagral_eOne', 'generateJsonAllDevices');
    switch ($action) {
        case 'create':
            $random_minutes = random_int(0, 59);
            $random_hours = random_int(0, 23);
            if ( ! is_object($cron)) {
                $cron = new cron();
                $cron->setClass('Diagral_eOne');
                $cron->setFunction('generateJsonAllDevices');
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setTimeout(5);
                $cron->setSchedule($random_minutes . ' ' . $random_hours . ' * * 7');
                $cron->save();
            }
            break;
        case 'remove':
            if (is_object($cron)) {
                $cron->remove(true);
            }
            break;
        case 'update':
            if ( ! is_object($cron)) {
                Diagral_eOne_Cron_JSON('create');
            }
            break;
    }
}

?>
