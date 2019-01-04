<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Contracts\JobHandler;
use Qless\Jobs\BaseJob;

class CustomHandler implements JobHandler
{
    public function perform(BaseJob $job): void
    {
        if ($job->getQueue() === 'handler_test') {
            (new Job)->perform($job);
        }
    }
}
