<?php

namespace LaravelQless\Handler;

use LaravelQless\Contracts\JobHandler;
use Qless\Jobs\BaseJob;

class DefaultHandler implements JobHandler
{
    public function perform(BaseJob $job): void
    {
        $job->perform();
    }
}
