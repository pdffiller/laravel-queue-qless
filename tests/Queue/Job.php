<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Job\AbstractJob;
use Qless\Jobs\BaseJob;

class Job extends AbstractJob
{
    public function perform(BaseJob $job)
    {
        if ($job->getQueue() === 'handler_test') {
            $job->getData()['classHandler'] = self::class;
        }

        if ($job->getTags()) {
            $job->getData()['tags'] = $job->getTags();
        }

        $job->complete();
    }
}
