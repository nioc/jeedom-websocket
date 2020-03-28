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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Websocket extends eqLogic
{
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */

    public static function deamon_info()
    {
        $return = array();

        $status = trim(shell_exec('systemctl is-active jeedom-websocket'));
        $return['state'] = ($status === 'active') ? 'ok' : 'nok';

        $return['launchable'] = 'ok';
        if (!file_exists('/etc/systemd/system/jeedom-websocket.service')) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le démon n\'est pas installé ', __FILE__);
        }
        return $return;
    }
    
    public static function deamon_start($_debug = false)
    {
        log::add('Websocket', 'info', 'Starting daemon');
        exec(system::getCmdSudo() . 'systemctl restart jeedom-websocket');
        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('Websocket', 'error', 'Unable to start daemon');
            return false;
        }
    }

    public static function deamon_stop()
    {
        log::add('Websocket', 'info', 'Stopping daemon');
        exec(system::getCmdSudo() . 'systemctl stop jeedom-websocket');
    }

    /*     * *********************Méthodes d'instance************************* */

    public function preInsert()
    {
    }

    public function postInsert()
    {
    }

    public function preSave()
    {
    }

    public function postSave()
    {
    }

    public function preUpdate()
    {
    }

    public function postUpdate()
    {
    }

    public function preRemove()
    {
    }

    public function postRemove()
    {
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
      public static function postConfig_<Variable>() {
      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
      public static function preConfig_<Variable>() {
      }
     */

    /*     * **********************Getteur Setteur*************************** */
}
