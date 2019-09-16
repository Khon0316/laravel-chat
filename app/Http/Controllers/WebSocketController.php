<?php

namespace App\Http\Controllers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketController implements MessageComponentInterface
{
    protected $clients;
    private $subscriptions;
    private $users;
    private $userResources;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->subscriptions = [];
        $this->users = [];
        $this->userResources = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $conn->send('Open!');
        $this->users[$conn->resourceId] = $conn;

        echo "resourceId:" . $conn->resourceId. "\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "msg: " . $msg . "\n";
        $data = json_decode($msg);

        if (isset($data->command)) {
            switch ($data->command) {
                case 'subscribe':
                    $this->subscriptions[$from->resourceId] = $data->channel;
                    break;
                case 'groupchat':
                    if (isset($this->subscriptions[$from->resourceId])) {
                        $target = $this->subscriptions[$from->resourceId];
                        foreach ($this->subscriptions as $id => $channel) {
                            if ($channel == $target && $id != $from->resourceId) {
                                $this->users[$id]->send($data->message);
                            }
                        }
                    }
                    break;
                case "message":
                    echo "message: ". $data->to . "\n";
                    if (isset($this->userResources[$data->to])) {
                        foreach ($this->userResources[$data->to] as $key => $resourceId) {
                            if (isset($this->users[$resourceId])) {
                                $this->users[$resourceId]->send($msg);
                            }
                        }
                        $from->send(json_encode($this->userResources[$data->to]));
                    }
                    if (isset($this->userResources[$data->from])) {
                        foreach ($this->userResources[$data->from] as $key => $resourceId) {
                            if (isset($this->users[$resourceId]) && $from->resourceId != $resourceId) {
                                $this->users[$resourceId]->send($msg);
                            }
                        }
                    }
                    break;
                case "register":
                    //
                    if (isset($data->userId)) {
                        if (isset($this->userResources[$data->userId])) {
                            if (!in_array($from->resourceId, $this->userResources[$data->userId])) {
                                $this->userResources[$data->userId][] = $from->resourceId;
                            }
                        } else {
                            $this->userResources[$data->userId] = [];
                            $this->userResources[$data->userId][] = $from->resourceId;
                        }
                    }
                    $from->send(json_encode($this->users));
                    $from->send(json_encode($this->userResources));
                    break;
                default:
                    $example = array(
                        'methods' => [
                            "subscribe" => '{command: "subscribe", channel: "global"}',
                            "groupchat" => '{command: "groupchat", message: "hello glob", channel: "global"}',
                            "message" => '{command: "message", to: "1", message: "it needs xss protection"}',
                            "register" => '{command: "register", userId: 9}',
                        ],
                    );
                    $from->send(json_encode($example));
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        unset($this->users[$conn->resourceId]);
        unset($this->subscriptions[$conn->resourceId]);
        foreach ($this->userresources as &$userId) {
            foreach ($userId as $key => $resourceId) {
                if ($resourceId==$conn->resourceId) {
                    unset( $userId[ $key ] );
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
