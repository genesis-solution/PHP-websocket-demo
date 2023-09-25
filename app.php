<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require dirname( __FILE__ ) . '/vendor/autoload.php';

class MySocket implements MessageComponentInterface {

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->isSocketForWordpress = false;
    }

    public function onOpen(ConnectionInterface $conn) {

        $queryParams = $conn->httpRequest->getUri()->getQuery();

        parse_str($queryParams, $params);

        if (isset($params['source']) && !empty($params['source'])) {
            // Access the parameter values
            $param1 = $params['source'];

            if ($param1 == "wp")
            {
                if (!$this->isSocketForWordpress)
                {
                    // Store the new connection in $this->clients
                    $this->clients->attach($conn);
                    $this->isSocketForWordpress = true;
                    echo "New WP connection! ({$conn->resourceId})\n";
                }
            }
            else {
                $this->clients->attach($conn);
                echo "New local connection! ({$conn->resourceId})\n";
            }
        }

    }

    public function onMessage(ConnectionInterface $from, $msg) {

        foreach ( $this->clients as $client ) {

            if ( $from->resourceId == $client->resourceId ) {
                continue;
            }

            $client->send( "$msg" ); // Client $from->resourceId said
        }
    }

    public function onClose(ConnectionInterface $conn) {
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MySocket()
        )
    ),
    8082
);

$server->run();
