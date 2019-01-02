<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Contracts\QlessJob;
use Qless\Jobs\BaseJob;

class Job implements QlessJob
{
    public function perform(BaseJob $job)
    {
        if ($job->getQueue() === 'handler_test') {
            $job->getData()['classHandler'] = self::class;
        }

        $job->complete();
    }
}
