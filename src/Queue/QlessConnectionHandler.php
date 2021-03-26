<?php

namespace LaravelQless\Queue;

use ArrayIterator;
use Qless\Client;

/**
 * Class QlessConnectionHandler
 * @package LaravelQless\Queue
 */
class QlessConnectionHandler
{

    /** @var Client[] */
    private $clients;

    /** @var ArrayIterator */
    private $clientIterator;

    /**
     * QlessConnectionHandler constructor.
     * @param Client[] $clients
     */
    public function __construct(array $clients)
    {
        $this->init($clients);
    }

    private function init(array $clients): void
    {
        foreach ($clients as $client) {
            if (!$client instanceof Client) {
                continue;
            }
            $this->clients[] = $client;

        }
        if (empty($this->clients)) {
            throw new \Exception("No configs found");
        }

        $this->clientIterator = new ArrayIterator($this->clients);
    }

    public function getRandomClient(): Client
    {
        return $this->clients[array_rand($this->clients)];
    }

    public function getAllClients(): array
    {
        return $this->clients;
    }

    public function getCurrentClient(): Client
    {
        if ($this->clientIterator->current() === null) {
            return $this->getNextClient();
        }

        return $this->clientIterator->current();
    }

    public function getNextClient(): Client
    {
        if ($this->clientIterator->current() === null) {
            $this->clientIterator->rewind();
        }

        $currentClient = $this->clientIterator->current();
        $this->clientIterator->next();

        return $currentClient;
    }

}
