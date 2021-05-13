<?php

namespace LaravelQless\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Bus\Dispatchable;
use LaravelQless\Queue\QlessConnector;
use Qless\Jobs\BaseJob;
use LaravelQless\Contracts\QlessJob;

/**
 * Class AbstractJob
 * @package LaravelQless\Job
 */
abstract class AbstractJob implements QlessJob, ShouldQueue, Arrayable
{
    use Dispatchable, Queueable;
    /**
     * @var array
     */
    protected $data = [];

    protected $isSync = false;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param BaseJob $job
     * @return mixed
     */
    abstract public function perform(BaseJob $job);

    /**
     * {@inheritdoc}
     * @return AbstractJob
     */
    public static function dispatchNow()
    {
        return (new static(...func_get_args()))->completeSync();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return (array) $this->data;
    }

    /**
     * @return $this
     */
    protected function completeSync(): self
    {
        $this->isSync = true;
        return $this;
    }

    /**
     * @throws \Exception
     */
    private function completeImmediately(): void
    {
        $connector = new QlessConnector();
        $queue = $connector->connect([
            'queue' => $this->queue,
            'connection' => $this->connection
        ]);

        $jid = $queue->push($this, $this->data, $this->queue);
        $connection = $queue->getCurrentConnection()->queues[$this->queue];

        /**@var BaseJob $job */
        $job = $connection->popByJid($jid);

        $job->perform();
    }

    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        if ($this->isSync) {
            $this->completeImmediately();
        }
    }
}
