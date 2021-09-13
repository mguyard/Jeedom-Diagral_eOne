<?php

/*
 * This file is part of the DiagDebug distribution (https://github.com/mguyard/DiagDebug).
 * Copyright (c) 2021 Marc GUYARD (https://github.com/mguyard).
 * Version : 0.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/* * ***************************Includes Jeedom Core ********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class DiagDebug {

    private $diagDebugVersion = "0.1";
    private $plugin;
    private $zip;
    private $webSRVPathBase;
    private $zipStorage;
    private $contentAdded = FALSE;




    /* ------------------------------- Initialisation ------------------------------ */
    /**
     * Class Initialization
     * @param integer $pluginId - Jeedom plugin Id
     * @param string $zipStorage - Optional Path to store DiagDebug Archive
     */
    public function __construct(string $pluginId, string $zipStorage = NULL)
    {

        // Params Verifications
        if (empty($pluginId)) {
            throw new Exception(__METHOD__ . ' - You need to specify your pluginId');
        }
        $this->plugin = plugin::byId($pluginId);
        if (! $this->plugin instanceof plugin) {
            throw new Exception(__METHOD__ . ' - Unable to find plugin in Jeedom (based on pluginId provided)');
        }

        // Retreive WebSRV base path for Jeedom
        preg_match('/^(\/.*)\/core\/.*\/info.json$/', $this->plugin->getFilepath(), $webSRVBase);
        $this->webSRVPathBase = $webSRVBase[1];

        // Set Default DiagDebug Archive storage if not specify
        if(is_null($zipStorage)) {
            $this->zipStorage = $this->webSRVPathBase.'/plugins/' . $this->plugin->getId() . '/data/DiagDebug';
        } else {
            $this->zipStorage = rtrim($zipStorage, '/');
        }
        if (! is_dir($this->zipStorage)) {
            mkdir($this->zipStorage, 0700, TRUE);
        }

        // Remove all old DiagDebug archives
        foreach (glob($this->zipStorage . "/DiagDebug_" . $this->plugin->getId() . "_*.zip") as $filename) {
            unlink($filename);
        }

        // Instance Zip
        $now = new DateTime(null, new DateTimeZone('Europe/Paris'));
        $this->zipFile = $this->zipStorage . "/DiagDebug_" . $this->plugin->getId() . "_" . $now->format('Y-m-d_H\hi') . ".zip";
        $this->zip = new ZipArchive();
        if ($this->zip->open($this->zipFile, ZipArchive::CREATE)!==TRUE) {
            throw new Exception(__METHOD__ . ' - Unable to create DiagDebug Archive');
        }
    }






    /**
     * Remplace MagicWords
     * @param string $input
     * @return string with magic words replaced
     */
    private function replaceMagicWords(string $input): string
    {
        // Replace Magic Words
        $input = str_replace('#JEEBASE#', $this->webSRVPathBase, $input);
        $input = str_replace('#PLUGBASE#', $this->webSRVPathBase.'/plugins/'.$this->plugin->getId(), $input);

        // Return input with magic words replaced
        return $input;
    }





    /**
     * Store all plugin logs in DiagDebug Archive
     * @param integer $lines - Specify optional number of line (last lines) for each plugin logs
     * @return void
     */
    public function addPluginLogs(int $lines = NULL)
    {
        $pluginLogs = $this->plugin->getLogList();
        if(!empty($pluginLogs)) {
            foreach ($pluginLogs as $pluginLog) {
                $this->addPluginLog($pluginLog, $lines);
            }
        } else {
            throw new Exception(__METHOD__ . ' - Plugin don\'t have logs.');
        }
    }





    /**
     * Store plugin log in DiagDebug Archive
     * @param string $pluginLog - Log Name (without path) as show in Jeedom Logs interface
     * @param integer $lines - Specify optional number of line (last lines) for each plugin logs
     * @return void
     */
    public function addPluginLog(string $pluginLog, int $lines = NULL)
    {
        $pluginLogPath = log::getPathToLog($pluginLog);
        if (is_null($lines)) {
            if (is_readable($pluginLogPath)) {
                $this->zip->addFile($pluginLogPath, '/plugin/Logs/'.$pluginLog);
                $this->contentAdded = TRUE;
            } else {
                throw new Exception(__METHOD__ . ' - File ' . $pluginLogPath . ' isn\'t readable. We are not able to store this file');
            }
        } else {
            $getLines = array_reverse(log::get($pluginLog, (count(file($pluginLogPath))-$lines), $lines));
            $this->zip->addFromString('/plugin/Logs/'.$pluginLog, implode( "\n",$getLines));
            $this->contentAdded = TRUE;
        }
    }




    /**
     * Store multiple Jeedom Logs file in DiagDebug Archive
     * @param array $logs - Log Name (without path) as show in Jeedom Logs interface
     * @return void
     */
    public function addJeedomLogs(array $logs)
    {
        foreach ($logs as $log) {
            self::addJeedomLog($log);
        }
    }





    /**
     * Store a specific Jeedom Log file in DiagDebug Archive
     * @param string $log - Log Name (without path) as show in Jeedom Logs interface
     * @return void
     */
    public function addJeedomLog(string $log)
    {
        if (empty(log::liste($log))) {
            throw new Exception(__METHOD__ . ' - File ' . $log . ' don\'t exist in Jeedom. We are not able to store this file');
        } else {
            $logPath = log::getPathToLog($log);
            if (is_readable($logPath)) {
                $this->zip->addFile($logPath, '/jeedom/Logs/'.$log);
                $this->contentAdded = TRUE;
            } else {
                throw new Exception(__METHOD__ . ' - File ' . $logPath . ' isn\'t readable. We are not able to store this file');
            }
        }
    }




    /**
     * Add multiple commands result in a single file to DiagDebug Archive
     * @param array $cmds - List of system commands to run
     * @param string $filename - Filename where store commands results
     * @param boolean $sudo - Optional to specify if commands need to be run as sudo user
     * @return void
     */
    public function addCmds(array $cmds, string $filename, bool $sudo = False)
    {
        if (!empty($cmds)) {
            foreach ($cmds as $cmd) {
                // Replace Magic Words
                $cmd = self::replaceMagicWords($cmd);

                $storage .= "****************************************\n";
                $storage .= "*** Cmd : " . $cmd . "\n";
                $storage .= "****************************************\n\n";
                if ($sudo === TRUE) {
                    $storage .= shell_exec(system::getCmdSudo().' '. $cmd . ' 2>&1');
                } else {
                    $storage .= shell_exec($cmd . ' 2>&1');
                }
                $storage .= "\n\n";
            }
            // If command is success, store result, else throw exception
            if (! is_null($storage)) {
                $this->zip->addFromString('/Cmds/'.$filename, $storage);
                $this->contentAdded = TRUE;
            } else {
                throw new Exception(__METHOD__ . ' - Failed to execute command ' . $cmd);
            }
        } else {
            throw new Exception(__METHOD__ . ' - List of commands is empty');
        }
    }





    /**
     * Add command result to DiagDebug Archive
     * @param string $cmd - System command to run
     * @param string $filename - Optional filename of result. If not specified, command will be use as result filename
     * @param boolean $sudo - Optional to specify if command need to be run as sudo user
     * @return void
     */
    public function addCmd(string $cmd, string $filename = NULL, bool $sudo = False)
    {
        // Replace Magic Words
        $cmd = self::replaceMagicWords($cmd);

        // If filename isn't pass in params, cmd is use as filename
        if(is_null($filename)) {
            // If command contain slashes we replace with hash
            if (strpos($cmd, '/') === FALSE) {
                $filename = $cmd;
            } else {
                $filename = str_replace('/', '#', $cmd);
            }
        }
        // Launch command and store result
        $storage = "****************************************\n";
        $storage .= "*** Cmd : " . $cmd . "\n";
        $storage .= "****************************************\n\n";
        if ($sudo === TRUE) {
            $storage .= shell_exec(system::getCmdSudo().' '. $cmd . ' 2>&1');
        } else {
            $storage .= shell_exec($cmd . ' 2>&1');
        }
        // If command is success, store result, else throw exception
        if (! is_null($storage)) {
            $this->zip->addFromString('/Cmds/'.$filename, $storage);
            $this->contentAdded = TRUE;
        } else {
            throw new Exception(__METHOD__ . ' - Failed to execute command ' . $cmd);
        }
    }






    /**
     * Retrieve and add Plugin Configurations to DiagDebug Archive
     * @return void
     */
    public function addPluginConf()
    {
        // Retreive and store all plugin Configurations
        $pluginConfigurations = config::searchKey(NULL, $this->plugin->getId());
        foreach ($pluginConfigurations as $key => &$pluginConfiguration) {
            // If configuration can be a Password, we hide it
            if (preg_match('/(pass|password|pwd|mdp|motdepasse)/i', $pluginConfiguration['key'])) {
                $pluginConfiguration['value'] = "** HIDDED by DiagDebug **";
            }
        }
        if (!empty($pluginConfigurations)) {
            $this->zip->addFromString('/plugin/configuration', json_encode($pluginConfigurations,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $this->contentAdded = TRUE;
        } else {
            throw new Exception(__METHOD__ . ' - No plugin configuration find');
        }
    }




    /**
     * Retrieve and add Eqlogics informations to DiagDebug Archive
     * @return void
     */
    public function addAllPluginEqlogic()
    {
        $eqList = array();
        foreach (eqLogic::byType($this->plugin->getId()) as $eqLogic) {
            $eqList[$eqLogic->getId()] = array (
                'name' => $eqLogic->getName(),
                'logicalId' => $eqLogic->getLogicalId(),
                'isVisible' => $eqLogic->getIsVisible(),
                'isEnable' => $eqLogic->getIsEnable(),
                'configuration' => $eqLogic->getConfiguration(NULL)
            );
        }
        if (!empty($eqList)) {
            $this->zip->addFromString('/plugin/deviceList', json_encode($eqList,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $this->contentAdded = TRUE;
        } else {
            throw new Exception(__METHOD__ . ' - No eqLogic find');
        }
    }




    /**
     * Add files to DiagDebug Archive thru array
     * Support folder and wildcard
     * @param array $files - Files with absolute path. Can be a file, folder, or glob (wildcard)
     * @return void
     */
    public function addFiles(array $files)
    {
        if(!empty($files)) {
            foreach ($files as $file) {
                $this->addFile($file);
            }
        } else {
            throw new Exception(__METHOD__ . ' - List of files is empty.');
        }
    }



    /**
     * Add file to DiagDebug Archive
     * Support folder and wildcard
     * @param string $file - File with absolute path. Can be a file, folder, or glob (wildcard)
     * @return void
     */
    public function addFile(string $file)
    {
        // Replace Magic Words
        $file = self::replaceMagicWords($file);

        // If $file is a directory or contain wildcard
        if (is_dir($file)) {
            foreach (glob($file.'/*') as $file) {
                self::addFile($file);
            }
        }else if (strpos($file, '*') !== FALSE) {
            foreach (glob($file) as $file) {
                self::addFile($file);
            }
        }else if (is_file($file) && is_readable($file)) {
            $this->zip->addFile($file, '/files/' . $file);
            $this->contentAdded = TRUE;
        } else {
            throw new Exception(__METHOD__ . ' - File ' . $file . ' isn\'t readable. We are not able to store this file');
        }
    }



    /**
     * Provide information to be able to download DiagDebug Archive
     * @return array - with filename, filesize, absolutePath and relativePath (after the Jeedom base URL)
     */
    public function download(): array
    {
        $this->zip->setArchiveComment('Archive create by DiagDebug ' . $this->diagDebugVersion . ' - Provided by @mguyard');
        $this->zip->close();
        if (! file_exists($this->zipFile)) {
            if ($this->contentAdded === FALSE) {
                throw new Exception(__METHOD__ . ' - DiagDebug archive don\'t content files so archive don\' exist.');
            } else {
                throw new Exception(__METHOD__ . ' - DiagDebug archive don\'t exist. Probably due to an error.');
            }
            return array();
        } else {
            return array(
                'filename' => basename($this->zipFile),
                'filesize' => filesize($this->zipFile),
                'absolutePath' => $this->zipFile,
                'relativePath' => str_replace($this->webSRVPathBase, '', $this->zipFile)
            );
        }
    }

}
