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

function Websocket_install()
{
    // Set default config
    $allowedHosts = [];
    array_push($allowedHosts, config::byKey('internalAddr', 'core', 'localhost'));
    array_push($allowedHosts, config::byKey('externalAddr', 'core', 'localhost'));
    $allowedHosts = array_unique($allowedHosts);
    config::save('port', 8090, 'Websocket');
    config::save('readDelay', 2, 'Websocket');
    config::save('authDelay', 1, 'Websocket');
    config::save('allowedHosts', implode(',', $allowedHosts), 'Websocket');

    // Installing daemon
    log::add('Websocket', 'info', 'Installing daemon');
    exec(system::getCmdSudo() . 'cp '.dirname(__FILE__).'/../resources/jeedom-websocket.service /etc/systemd/system/jeedom-websocket.service');
    exec(system::getCmdSudo() . 'systemctl daemon-reload');
    exec(system::getCmdSudo() . 'systemctl start jeedom-websocket');
    exec(system::getCmdSudo() . 'systemctl enable jeedom-websocket');
    $active = trim(shell_exec('systemctl is-active jeedom-websocket'));
    $enabled = trim(shell_exec('systemctl is-enabled jeedom-websocket'));
    if ($active !== 'active' || $enabled !== 'enabled') {
        log::add('Websocket', 'error', "Daemon is not fully installed ($active / $enabled)");
    }
    log::add('Websocket', 'info', "Daemon installed ($active / $enabled)");
}

function Websocket_update()
{
    log::add('Websocket', 'info', 'Updating daemon');
    exec(system::getCmdSudo() . 'systemctl restart jeedom-websocket');
}

function Websocket_remove()
{
    log::add('Websocket', 'info', 'Removing daemon');
    exec(system::getCmdSudo() . 'systemctl disable jeedom-websocket');
    exec(system::getCmdSudo() . 'systemctl stop jeedom-websocket');
    exec(system::getCmdSudo() . 'rm /etc/systemd/system/jeedom-websocket.service');
    exec(system::getCmdSudo() . 'systemctl daemon-reload');
    log::add('Websocket', 'info', "Daemon removed");
}
