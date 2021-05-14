<?php

namespace LaravelQless\Queue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Qless\Client;

/**
 * Class QlessConnector
 * @package LaravelQless\Queue
 */
class QlessConnector implements ConnectorInterface
{
    private const REDIS_CONNECTION_CONFIG_KEY = 'redis_connection';
    private const CONFIG_PATH_PREFIX = 'database.redis.';

    private const DEFAULT_CONNECTION_CONFIG = 'qless';

    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return QlessQueue
     * @throws \Exception
     */
    public function connect(array $config): QlessQueue
    {
        $redisConnection = Arr::get($config, self::REDIS_CONNECTION_CONFIG_KEY, self::DEFAULT_CONNECTION_CONFIG);

        if (!is_array($redisConnection)) {
            $redisConnection = [$redisConnection];
        }

        $clients = [];
        foreach ($redisConnection as $connection) {
            $qlessConfig = Config::get(self::CONFIG_PATH_PREFIX . $connection, []);
            $clients[] = new Client($qlessConfig);
        }

        return new QlessQueue(
            new QlessConnectionHandler($clients),
            $config
        );
    }
}
