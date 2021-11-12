<?php

namespace LaravelQless\Job;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\JobName;
use Illuminate\Queue\ManuallyFailedException;
use LaravelQless\Queue\QlessQueue;
use Qless\Jobs\BaseJob;
use LaravelQless\Contracts\JobHandler;

/**
 * Class QlessJob
 * @package LaravelQless\Job
 */
class QlessJob extends Job implements JobContract
{
    /**
     * @var BaseJob
     */
    protected $job;

    /**
     * @var QlessQueue
     */
    protected $qlessQueue;

    /**
     * @var string
     */
    protected $payload;

    /**
     * @var JobHandler
     */
    protected $handler;

    /**
     * QlessJob constructor.
     * @param Container $container
     * @param QlessQueue $qlessQueue
     * @param JobHandler $handler
     * @param BaseJob $job
     * @param string $payload
     */
    public function __construct(
        Container $container,
        QlessQueue $qlessQueue,
        JobHandler $handler,
        BaseJob $job,
        string $payload
    ) {
        $this->container = $container;
        $this->qlessQueue = $qlessQueue;
        $this->job = $job;
        $this->payload = $payload;
        $this->connectionName = $qlessQueue->getConnectionName();
        $this->handler = $handler;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getJid();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->job->getData()->toArray();
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload()
    {
        return json_decode($this->payload, true);
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->handler->perform($this->job);

        if ($this->job->failed) {
            $this->failed(new ManuallyFailedException());
        }
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return mixed
     */
    public function release($delay = 0)
    {
        $this->released = true;
        return $this->qlessQueue->later($delay, $this->job->getKlass(), $this->job->getData(), $this->job->getQueue());
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->job->cancel();
        $this->deleted = true;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job->getRetries();
    }

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries()
    {
        return $this->job->getRetries();
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int
     */
    public function timeout()
    {
        return (int) $this->job->ttl();
    }

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int
     */
    public function timeoutAt()
    {
        return Carbon::now()->addSeconds($this->timeout())->getTimestamp();
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName()
    {
        return $this->job->getKlass();
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->job->getQueue();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->payload;
    }
    
    /**
     * Process an exception that caused the job to fail.
     *
     * @param  \Throwable|null  $e
     * @return void
     */
    protected function failed($e)
    {
        $payload = $this->payload();

        [$class, $method] = JobName::parse($payload['job']);

        if (class_exists($class) && method_exists($class, 'failed')) {
            $this->instance = $this->resolve($class);
            $this->instance->failed($payload['data'], $e);
        }
    }
}
