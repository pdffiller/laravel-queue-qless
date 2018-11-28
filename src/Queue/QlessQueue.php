<?php

namespace LaravelQless\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\InvalidPayloadException;
use Illuminate\Queue\Queue;
use LaravelQless\Job\QlessJob;
use Qless\Client;
use Qless\Topics\Topic;

/**
 * Class QlessQueue
 * @package LaravelQless\Queue
 */
class QlessQueue extends Queue implements QueueContract
{
    /**
     * @var Client
     */
    private $connect;

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var string
     */
    protected $defaultQueue;

    /**
     * QlessQueue constructor.
     * @param Client $connect
     * @param array $config
     */
    public function __construct(Client $connect, array $config)
    {
        $this->connect = $connect;
        $this->defaultQueue = $config['queue'] ?? '';
        $this->connectionName = $config['connection'] ?? '';
        $this->config = $config;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return $this->getConnection()->length($queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queueName
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queueName = null, array $options = [])
    {
        $payloadData = array_merge(json_decode($payload, true), $options);

        $queueName = $queueName ?? $this->defaultQueue;

        $queue = $this->getConnection()->queues[$queueName];

        return $queue->put(
            $payloadData['job'],
            $payloadData['data'],
            null,
            $payloadData['timeout'],
            $payloadData['maxTries']
        );
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed   $data
     * @param  string  $queueName
     * @return mixed
     */
    public function push($job, $data = '', $queueName = null)
    {
        return $this->pushRaw($this->makePayload($job, $data), $queueName);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed   $data
     * @param  string  $queueName
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queueName = null)
    {
        return $this->pushRaw(
            $this->makePayload($job, $data, ['timeout' => $delay]),
            $queueName,
            ['timeout' => $delay]
        );
    }

    /**
     * Recurring Jobs
     *
     * @param int $interval
     * @param string $job
     * @param array $data
     * @param string $queueName
     * @return string
     */
    public function recur(int $interval, string $job, array $data, ?string $queueName = null)
    {
        /** @var \Qless\Queues\Queue $queue */
        $queue = $this->getConnection()->queues[$queueName];

        return $queue->recur($job, $data, $interval);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queueName
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queueName = null)
    {
        $queueName = $queueName ?? $this->defaultQueue;

        /** @var \Qless\Queues\Queue $queue */
        $queue = $this->getConnection()->queues[$queueName];

        $job = $queue->pop();

        if (!$job) {
            return null;
        }

        $payload = $this->makePayload($job->getKlass(), $job->getData());

        return new QlessJob(
            $job,
            $payload,
            $this->getConnectionName()
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

        /** @var \Qless\Queues\Queue $queue */
        $queue = $this->getConnection()->queues[$queueName];

        return $queue->subscribe($topic);
    }

    /**
     * @param string $topic
     * @param string|null $queueName
     * @return bool
     */
    public function unSubscribe(string $topic, string $queueName = null): bool
    {
        $queueName = $queueName ?? $this->defaultQueue;

        /** @var \Qless\Queues\Queue $queue */
        $queue = $this->getConnection()->queues[$queueName];

        return $queue->unSubscribe($topic);
    }

    /**
     * @param string $topic
     * @param string $job
     * @param array $data
     * @param array $options
     * @return array
     */
    public function pushToTopic(string $topic, string $job, array $data = [], array $options = []): array
    {
        $topic = new Topic($topic, $this->getConnection());

        return $topic->put(
            $job,
            $data,
            null,
            $options['timeout'] ?? null,
            $options['maxTries'] ?? null
        );
    }

    /**
     * @param string $job
     * @param mixed|string $data
     * @param array $options
     * @return string
     */
    protected function makePayload(string $job, $data, $options = [])
    {
        $payload = json_encode([
            'displayName' => explode('@', $job)[0],
            'job' => $job,
            'maxTries' => array_get($options, 'maxTries'),
            'timeout' => array_get($options, 'timeout'),
            'data' => $data,
        ]);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }

        return $payload;
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name): self
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * @return Client
     */
    private function getConnection(): Client
    {
        return $this->connect;
    }
}
