<?php

namespace LaravelQless\Queue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\WorkerStopping;
use Qless\Client;

/**
 * Class QlessConnector
 * @package LaravelQless\Queue
 */
class QlessConnector implements ConnectorInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * QlessConnector constructor.
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
    * Establish a queue connection.
    *
    * @param array $config
    *
    * @return QlessQueue
    */
    public function connect(array $config): QlessQueue
    {
         $redisConnection = array_get($config, 'redis_connection', 'qless');

         $redisConfig = Config::get('database.redis.' . $redisConnection, []);

         $qlessQueue = new QlessQueue(
             new Client($redisConfig),
             $config
         );

        $this->dispatcher->listen(WorkerStopping::class, function () use ($qlessQueue) {
            $qlessQueue->getConnection()->getWorkers()->remove($qlessQueue->getConnection()->getWorkerName());
        });

         return $qlessQueue;
    }
}
