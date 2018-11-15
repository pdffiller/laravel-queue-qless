<?php

namespace LaravelQless\Examples\Job;

use LaravelQless\Contracts\QlessJob;
use Qless\Jobs\BaseJob;

class ExampleJob implements QlessJob
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
