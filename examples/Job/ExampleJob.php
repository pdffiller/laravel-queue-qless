<?php

namespace LaravelQless\Examples\Job;

use LaravelQless\Job\AbstractJob;
use Qless\Jobs\BaseJob;

class ExampleJob extends AbstractJob
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function perform(BaseJob $job)
    {
        \Log::info(
            'Example Job Qless',
            [
                'data' => $job->getData(),
                'jobId' => $job->jid,
            ]
        );
        $job->complete();
    }
}
