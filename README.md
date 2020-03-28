# ![Screenshot desktop](/logo.png) Jeedom Websocket

[![license: GPLv2](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0) [![GitHub release](https://img.shields.io/github/release/nioc/jeedom-websocket.svg)](https://github.com/nioc/jeedom-websocket/releases/latest)

[Jeedom](https://www.jeedom.com) plugin to provide a reliable low latency bidirectional client-server communication over [websocket](https://wikipedia.org/wiki/WebSocket) protocol.

Events are pushed to clients avoiding long polling (Ajax request) overhead.

Reduce server load by sharing the Jeedom query and broadcast result to multiples clients.

## Installation & configuration

-   Install plugin:
    -   Upload `Websocket.zip` file in the Jeedom plugin admin GUI,
    -   Set plugin logical id: `Websocket`,
    -   Save and refresh (F5).

-   Tune plugin configuration (from plugin configuration GUI):
    -   Websocket internal port: any available port (default `8090`),
    -   Period in seconds between events readings,
    -   Period in seconds before closing an unauthenticated connection,
    -   Allowed hosts (**most important**): comma-separated list of hosts which are allowed to connect to websocket, example: `myjeedom.ltd,10.0.0.42` (default set to your internal and external Jeedom instance hosts).

-   (Optional) proxying websocket port (`8090`) to regular http (`80`) / https (`443`) with Apache (require `proxy_wstunnel` module) by adding the following lines in `/etc/apache2/sites-enabled/000-default.conf`:
    ```configuration
      <Location "/myawesomesocket">
              ProxyPass ws://localhost:8090
              ProxyPassReverse ws://localhost:8090
      </Location>
    ```

-   Check [daemon configuration](/resources/jeedom-websocket.service):
    -   Does webserver user is not `www-data`?
        -   Change line `User=www-data`
    -   Does jeedom path is not `/var/www/html/`?
        -   Change line `WorkingDirectory=/var/www/html/plugins/Websocket/core/php`
    -   Does PHP path is not `/usr/bin/php`?
        -   Change line `ExecStart=/usr/bin/php bin/server.php`

## Use

To get Jeedom events, client:

1.  connect to websocket endpoint,
2.  send user API key as soon as `onopen` event occurs,
3.  do your useful stuffs with Jeedom events.

JavaScript example:

```javascript
//1. connect
const websocket = new WebSocket('ws://10.0.0.42/myawesomesocket')

//2. send user credentials
websocket.onopen = (e) => {
  const authMsg = JSON.stringify({ apiKey: 'userApiKey' })
  websocket.send(authMsg)
}

//3. Handle events
websocket.onmessage = (e) => {
  //do stuff with Jeedom events (e.data.result)
}
websocket.onerror = (e) => {
  //handle error
}
websocket.onclose = (e) => {
  //handle connection closed
}
```

## Credits

-   **[Nioc](https://github.com/nioc/)** - _Initial work_

See also the list of [contributors](https://github.com/nioc/jeedom-websocket/contributors) to this project.

This project is powered by the following components:

-   [Ratchet](https://github.com/ratchetphp/Ratchet) (MIT)
-   [NextDom plugin-template](https://github.com/NextDom/plugin-template) (GPLv2)
-   [Jeedom](https://github.com/jeedom) (GPL)
