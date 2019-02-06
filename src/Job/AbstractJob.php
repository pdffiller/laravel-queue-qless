<?php

namespace LaravelQless\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Bus\Dispatchable;
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
     * @return array
     */
    public function toArray(): array
    {
        return (array) $this->data;
    }
}
