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

    private const CONFIG_HOST_KEY = 'host';
    private const CONFIG_PASSWORD_KEY = 'password';
    private const CONFIG_PORT_KEY = 'port';
    private const CONFIG_DATABASE_KEY = 'database';

    private const CONFIG_SEPARATOR = ',';

    public const DEFAULT_PASSWORD = null;
    public const DEFAULT_PORT = 6379;
    public const DEFAULT_DATABASE = 0;

    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return QlessQueue
     */
    public function connect(array $config): QlessQueue
    {
        $redisConnection = Arr::get($config,
            self::REDIS_CONNECTION_CONFIG_KEY,
            self::DEFAULT_CONNECTION_CONFIG);

        $qlessConfig = Config::get(self::CONFIG_PATH_PREFIX . $redisConnection, []);

        if ($this->hasSharding($qlessConfig)) {
            $shardingConfigs = $this->getShardingConfigs($qlessConfig);
            $qlessConfig = $shardingConfigs[rand(0, count($shardingConfigs) - 1)];
        }

        return new QlessQueue(
            new Client($qlessConfig),
            $config
        );
    }

    private function hasSharding(array $connectionConfig): bool
    {
        $hostList = $this->extractConfigValues(self::CONFIG_HOST_KEY, $connectionConfig);
        return (count($hostList) > 1);
    }

    private function getShardingConfigs(array $connectionConfig): array
    {
        $shardingList = [];

        $hostList = $this->extractConfigValues(self::CONFIG_HOST_KEY, $connectionConfig);
        $portList = $this->extractConfigValues(self::CONFIG_PORT_KEY, $connectionConfig);
        $passwordList = $this->extractConfigValues(self::CONFIG_PASSWORD_KEY, $connectionConfig);
        $databaseList = $this->extractConfigValues(self::CONFIG_DATABASE_KEY, $connectionConfig);

        foreach ($hostList as $i => $host) {
            if ($host === '') {
                continue;
            }
            $shardingList[] = $this->getQlessConfig(
                $host,
                $passwordList[$i] ?? null,
                $portList[$i] ?? null,
                $databaseList[$i] ?? null
            );
        }

        return $shardingList;
    }

    private function extractConfigValues(string $key, array $connectionConfig): array
    {
        $value = $connectionConfig[$key] ?? null;

        if (!$value) {
            return [];
        }

        $values = explode(self::CONFIG_SEPARATOR, $value);

        return array_map(function ($v) {
            return trim($v) ?: null;
        }, $values);
    }

    private function getQlessConfig(
        string $host,
        ?string $password,
        ?int $port,
        ?int $database
    ): array
    {
        return [
            self::CONFIG_HOST_KEY => $host,
            self::CONFIG_PASSWORD_KEY => $password ?: self::DEFAULT_PASSWORD,
            self::CONFIG_PORT_KEY => $port ?: self::DEFAULT_PORT,
            self::CONFIG_DATABASE_KEY => $database ?: self::DEFAULT_DATABASE,
        ];
    }

}
