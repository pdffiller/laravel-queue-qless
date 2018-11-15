<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Contracts\QlessJob;
use Qless\Jobs\BaseJob;

class Job implements QlessJob
{
    public function perform(BaseJob $job)
    {
        $job->complete();
    }
}