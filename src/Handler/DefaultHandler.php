<?php

namespace LaravelQless\Handler;

use LaravelQless\Contracts\JobHandler;
use Qless\Jobs\BaseJob;

/**
 * Class DefaultHandler
 * @package LaravelQless\Handler
 */
class DefaultHandler implements JobHandler
{
    /**
     * @param BaseJob $job
     */
    public function perform(BaseJob $job): void
    {
        $job->perform();
    }
}
