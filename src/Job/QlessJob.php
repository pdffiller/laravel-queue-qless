<?php

namespace LaravelQless\Job;

use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Queue\Job as JobContract;
use Qless\Jobs\BaseJob;

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
     * @var string
     */
    protected $payload;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @var bool
     */
    protected $released = false;

    public function __construct(BaseJob $job, string $payload, string $connectionName)
    {
        $this->job = $job;
        $this->payload = $payload;
        $this->connectionName = $connectionName;
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
        $this->job->perform();
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return mixed
     */
    public function release($delay = 0)
    {
        //$this->delete();
        $this->released = true;
        return \Queue::later($delay, $this->job->getKlass(), $this->job->getData(), $this->job->getQueue());
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
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->job->getRemaining();
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e)
    {
        $this->job->fail($e->getCode(), $e->getMessage());
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
     * @return int|null
     */
    public function timeout()
    {
        return $this->job->ttl();
    }

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
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
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName()
    {
        return $this->job->getKlass(); // ??
    }

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
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
}
