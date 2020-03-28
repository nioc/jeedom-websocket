<?php
namespace JeedomNotifier;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Notifier implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage List of unauthenticated clients (waiting for authentication message)
     */
    private $unauthenticatedClients;

    /**
     * @var \SplObjectStorage List of authenticated clients (receiving events broadcasts)
     */
    private $authenticatedClients;

    /**
     * @var bool Has authenticated clients (need to read events)
     */
    private $hasAuthenticatedClients;

    /**
     * @var bool Has unauthenticated clients (need to check authentication delay and maybe close connection)
     */
    private $hasUnauthenticatedClients;

    /**
     * @var int Number of seconds before an unauthenticated connection is closed
     */
    private $authDelay;

    /**
     * @var int Timestamp of the last events read
     */
    private $lastReadTimestamp;

    /**
     * Notifier constructor
     *
     * @param int $authDelay Number of seconds before an unauthenticated connection is closed
     */
    public function __construct($authDelay)
    {
        $this->unauthenticatedClients = new \SplObjectStorage;
        $this->authenticatedClients = new \SplObjectStorage;
        $this->hasAuthenticatedClients = false;
        $this->hasUnauthenticatedClients = false;
        $this->authDelay = $authDelay;
        $this->lastReadTimestamp = time();
    }

    /**
     * Process the logic (read events and broadcast to authenticated clients, close authenticated clients)
     */
    public function process()
    {
        if ($this->hasUnauthenticatedClients) {
            // Check is there is unauthenticated clients for too long
            \log::add('Websocket', 'debug', 'Close unauthenticated client');
            $current = time();
            foreach ($this->unauthenticatedClients as $client) {
                if ($current - $client->openTimestamp > $this->authDelay) {
                    // Client has been connected without authentication for too long, close connection
                    \log::add('Websocket', 'warning', "Close unauthenticated client #{$client->resourceId} from IP: {$client->ip}");
                    $client->close();
                }
            }
        }
        if ($this->hasAuthenticatedClients) {
            // Read events from Jeedom
            \log::add('Websocket', 'debug', "Request events from {$this->lastReadTimestamp}");
            $events = \event::changes($this->lastReadTimestamp);
            $this->lastReadTimestamp = time();
            if (count($events['result']) > 0) {
                // There is some events to broadcast
                $this->broadcast($events);
            }
        }
    }

    /**
     * Update authenticated clients flag
     */
    private function setAuthenticatedClientsCount()
    {
        $this->hasAuthenticatedClients = $this->authenticatedClients->count() > 0;
        if (!$this->hasAuthenticatedClients) {
            \log::add('Websocket', 'debug', 'There is no more authenticated client');
        }
    }

    /**
     * Update unauthenticated clients flag
     */
    private function setUnauthenticatedClientsCount()
    {
        $this->hasUnauthenticatedClients = $this->unauthenticatedClients->count() > 0;
        if (!$this->hasUnauthenticatedClients) {
            \log::add('Websocket', 'debug', 'There is no more unauthenticated client');
        }
    }

    /**
     * Authenticate client
     *
     * @param \Ratchet\ConnectionInterface $conn Connection to authenticate
     * @param string $msg Message sent by client, should contains a JSON object with an `apiKey` attribute set with user hash
     */
    private function authenticate(ConnectionInterface $conn, $msg)
    {
        // Remove client from unauthenticated clients list
        $this->unauthenticatedClients->detach($conn);
        $this->setUnauthenticatedClientsCount();
        // Parse message
        $objectMsg = json_decode($msg);
        if ($objectMsg === null || !property_exists($objectMsg, 'apiKey')) {
            \log::add('Websocket', 'warning', "Authentication failed (invalid message) for client #{$conn->resourceId} from IP: {$conn->ip}");
            $conn->close();
            return;
        }
        // Try to get user by his API key
        $user = \user::byHash($objectMsg->apiKey);
        if ($user === false || $user->getEnable() === false) {
            // Invalid user API key or user is disabled
            \log::add('Websocket', 'warning', "Authentication failed (invalid credentials) for client #{$conn->resourceId} from IP: {$conn->ip}");
            $conn->close();
        } else {
            if (!$this->hasAuthenticatedClients) {
                // It is the first client, we store current timestamp for fetching events since this moment
                $this->lastReadTimestamp = time();
            }
            $conn->username = $user->getLogin();
            $this->authenticatedClients->attach($conn);
            $this->hasAuthenticatedClients = true;
            \log::add('Websocket', 'info', "#{$conn->resourceId} is authenticated as '{$conn->username}'");
        }
    }

    /**
     * Broadcast data to authenticated clients
     *
     * @param object $data Data to send to clients
     */
    private function broadcast($data)
    {
        \log::add('Websocket', 'debug', 'Broadcast message');
        foreach ($this->authenticatedClients as $client) {
            $client->send(json_encode($data));
        }
    }

    /**
     * Callback for connection open (add to unauthenticated clients list)
     *
     * @param \Ratchet\ConnectionInterface $conn Connection to authenticate
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Add some useful informations
        $conn->openTimestamp = time();
        $conn->username = '?';
        if ($conn->httpRequest->hasHeader('X-Forwarded-For')) {
            $conn->ip = $conn->httpRequest->getHeader('X-Forwarded-For')[0];
        } else {
            $conn->ip = '?';
        }
        // Add client to unauthenticated clients list for handling his unauthentication
        $this->unauthenticatedClients->attach($conn);
        $this->hasUnauthenticatedClients = true;
        \log::add('Websocket', 'info', "New connection: #{$conn->resourceId} from IP: {$conn->ip}");
        \log::add('Websocket', 'debug', 'New connection headers: '.json_encode($conn->httpRequest->getHeaders()));
    }

    /**
     * Callback for incoming message from client (try to authenticate unauthenticated client)
     *
     * @param \Ratchet\ConnectionInterface $from Connection sending message
     * @param string $msg Data received from the client
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        \log::add('Websocket', 'debug', "Incoming message from #{$from->resourceId}");
        if ($this->unauthenticatedClients->contains($from)) {
            // this is a message from an unauthenticated client, check if it contains credentials
            $this->authenticate($from, $msg);
        }
    }

    /**
     * Callback for connection close (remove client from lists)
     *
     * @param \Ratchet\ConnectionInterface $conn Connection closing
     */
    public function onClose(ConnectionInterface $conn)
    {
        // Remove client from lists
        \log::add('Websocket', 'info', "Connection #{$conn->resourceId} ({$conn->username}) has disconnected");
        $this->unauthenticatedClients->detach($conn);
        $this->authenticatedClients->detach($conn);
        $this->setAuthenticatedClientsCount();
        $this->setUnauthenticatedClientsCount();
    }

    /**
     * Callback for connection error (remove client from lists)
     *
     * @param \Ratchet\ConnectionInterface $conn Connection in error
     * @param \Exception $e Exception encountered
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        \log::add('Websocket', 'error', "An error has occurred: {$e->getMessage()}");
        $conn->close();
        // Remove client from lists
        $this->unauthenticatedClients->detach($conn);
        $this->authenticatedClients->detach($conn);
        $this->setAuthenticatedClientsCount();
        $this->setUnauthenticatedClientsCount();
    }
}
