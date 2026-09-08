<?php

namespace LaravelQless\Queue;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\InvalidPayloadException;
use Illuminate\Queue\Queue;
use LaravelQless\Contracts\JobHandler;
use LaravelQless\Job\AbstractJob;
use LaravelQless\Job\QlessJob;
use Qless\Client;
use Qless\Topics\Topic;

/**
 * Class QlessQueue
 * @package LaravelQless\Queue
 */
class QlessQueue extends Queue implements QueueContract
{
    public const JOB_OPTIONS_KEY = '__QLESS_OPTIONS';

    private const WORKER_PREFIX = 'laravel_';

    /**
     * @var string
     */
    private $defaultQueue;

    /**
     * @var array
     */
    protected $config;

    /** @var QlessConnectionHandler */
    private $clients;

    /**
     * QlessQueue constructor.
     * @param QlessConnectionHandler $clients
     * @param array $config
     */
    public function __construct(QlessConnectionHandler $clients, array $config)
    {
        $this->clients = $clients;
        $this->defaultQueue = $config['queue'] ?? null;
        $this->connectionName = $config['connection'] ?? '';
        $this->config = $config;
    }

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size($queue = null): int
    {
        return $this->getNextConnection()->length($queue ?? '');
    }

    /**
     * Get the number of jobs waiting to be processed.
     *
     * @param \UnitEnum|string|null $queue
     * @return int
     */
    public function pendingSize($queue = null): int
    {
        return $this->getQueueCount($queue, 'waiting');
    }

    /**
     * Get the number of delayed (scheduled) jobs.
     *
     * @param string|null $queue
     * @return int
     */
    public function delayedSize($queue = null): int
    {
        return $this->getQueueCount($queue, 'scheduled');
    }

    /**
     * Get the number of jobs currently reserved (running) by workers.
     *
     * @param string|null $queue
     * @return int
     */
    public function reservedSize($queue = null): int
    {
        return $this->getQueueCount($queue, 'running');
    }

    /**
     * Qless does not expose the creation time of individual pending jobs.
     *
     * @param \UnitEnum|string|null $queue
     * @return int|null
     */
    public function creationTimeOfOldestPendingJob($queue = null): ?int
    {
        return null;
    }

    /**
     * @param \UnitEnum|string|null $queue
     * @param string $key
     * @return int
     */
    private function getQueueCount($queue, string $key): int
    {
        if ($queue instanceof \UnitEnum) {
            $queue = $queue->value;
        }

        $queue = $queue ?? $this->defaultQueue ?? '';
        $queue = (string) $queue;

        $count = 0;

        foreach ($this->getAllConnections() as $connection) {
            foreach ($connection->getQueues()->getCounts() as $stats) {
                if (($stats['name'] ?? null) === $queue) {
                    $count += (int) ($stats[$key] ?? 0);
                    break;
                }
            }
        }

        return $count;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string $queue
     * @param array $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payloadData = array_merge(json_decode($payload, true), $options);

        $queue = $queue ?? $this->defaultQueue;

        $queueObj = $this->getRandomConnection()->queues[$queue];

        $qlessOptions = $payloadData['data'][self::JOB_OPTIONS_KEY] ?? [];

        $options = array_merge($qlessOptions, $options);

        return $queueObj->put(
            $payloadData['job'],
            $payloadData['data'],
            $options['jid'] ?? null,
            $options['delay'] ?? null,
            $options['retries'] ?? null,
            $options['priority'] ?? null,
            $options['tags'] ?? null,
            $options['depends'] ?? null
        );
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed $data
     * @param string $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->makePayload($job, (array)$data), $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string|object $job
     * @param mixed $data
     * @param string $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $options = $data[self::JOB_OPTIONS_KEY] ?? [];
        $options = array_merge($options, ['timeout' => $delay]);

        return $this->pushRaw(
            $this->makePayload($job, $data, $options),
            $queue,
            $options
        );
    }

    /**
     * Recurring Jobs
     *
     * @param int $interval
     * @param string $job
     * @param array $data
     * @param string|null $queue
     * @return string
     */
    public function recur(int $interval, string $job, array $data, ?string $queue = null): string
    {
        $queueObj = $this->getNextConnection()->queues[$queue];

        $options = $data[self::JOB_OPTIONS_KEY] ?? [];
        $options = array_merge($options, ['interval' => $interval]);

        return $queueObj->recur(
            $job,
            $data,
            $options['interval'],
            $options['offset'] ?? null,
            $options['jid'] ?? null,
            $options['retries'] ?? null,
            $options['priority'] ?? null,
            $options['backlog'] ?? null,
            $options['tags'] ?? null
        );
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     *
     * @return QlessJob|null
     * @throws BindingResolutionException
     */
    public function pop($queue = null)
    {
        $connectionCount = $this->getClientCount();

        for ($i = 0; $i < $connectionCount; $i++) {
            $connection = $this->getNextConnection();

            /** @var \Qless\Queues\Queue $queueObj */
            $queueObj = $connection->queues[$queue];

            /** @var \Qless\Jobs\BaseJob $job */
            $job = $queueObj->pop(self::WORKER_PREFIX . $connection->getWorkerName());

            if ($job) {
                break;
            }
        }

        if (!$job) {
            return null;
        }

        $payload = $this->makePayload($job->getKlass(), $job->getData());

        return new QlessJob(
            $this->container,
            $this,
            app()->make(JobHandler::class),
            $job,
            $payload
        );
    }

    /**
     * @param string $topic
     * @param string|null $queueName
     * @return bool
     */
    public function subscribe(string $topic, string $queueName = null): bool
    {
        $queueName = $queueName ?? $this->defaultQueue;

        $result = true;
        foreach ($this->getAllConnections() as $connection) {
            /** @var \Qless\Queues\Queue $queue */
            $queue = $connection->queues[$queueName];
            $result = $queue->subscribe($topic) && $result;
        }

        return $result;
    }

    /**
     * @param string $topic
     * @param string|null $queueName
     * @return bool
     */
    public function unSubscribe(string $topic, string $queueName = null): bool
    {
        $queueName = $queueName ?? $this->defaultQueue;

        $result = true;
        foreach ($this->getAllConnections() as $connection) {
            /** @var \Qless\Queues\Queue $queue */
            $queue = $connection->queues[$queueName];
            $result = $queue->unSubscribe($topic) && $result;
        }

        return $result;
    }

    /**
     * @param string $topicName
     * @param string $job
     * @param array $data
     * @param array $options
     * @return array|string
     */
    public function pushToTopic(string $topicName, string $job, array $data = [], array $options = [])
    {
        $topic = new Topic($topicName, $this->getRandomConnection());

        $qlessOptions = $payloadData['data'][self::JOB_OPTIONS_KEY] ?? [];
        $options = array_merge($qlessOptions, $options);

        return $topic->put(
            $job,
            $data,
            $options['jid'] ?? null,
            $options['delay'] ?? null,
            $options['retries'] ?? null,
            $options['priority'] ?? null,
            $options['tags'] ?? null,
            $options['depends'] ?? null
        );
    }

    /**
     * @param string|object $job
     * @param mixed|string $data
     * @param array $options
     * @return string
     */
    protected function makePayload($job, $data = [], $options = []): string
    {
        $displayName = '';
        if ($job instanceof AbstractJob) {
            $displayName = get_class($job);
            $data = array_merge($job->toArray(), $data);
        } elseif (is_object($job)) {
            $displayName = get_class($job);
        }

        if (is_string($job)) {
            $displayName = explode('@', $job)[0];
        }

        $qlessOptions = $data[self::JOB_OPTIONS_KEY] ?? [];
        $data[self::JOB_OPTIONS_KEY] = array_merge($qlessOptions, $options);

        $payload = json_encode([
            'displayName' => $displayName,
            'job' => is_string($job) ? $job : $displayName,
            'data' => $data,
        ]);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: ' . json_last_error()
            );
        }

        return $payload;
    }

    /**
     * @return Client[]
     */
    public function getAllConnections(): array
    {
        return $this->clients->getAllClients();
    }

    public function getRandomConnection(): Client
    {
        return $this->clients->getNextClient();
    }

    public function getNextConnection(): Client
    {
        return $this->clients->getNextClient();
    }

    public function getCurrentConnection(): Client
    {
        return $this->clients->getCurrentClient();
    }

    /**
     * @deprecated use \LaravelQless\Queue\QlessQueue::getCurrentConnection
     * @alias
     */
    public function getConnection(): Client
    {
        return $this->getCurrentConnection();
    }

    public function getClientCount(): int
    {
        return $this->clients->getClientCount();
    }
}
