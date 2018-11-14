<?php
namespace LaravelQless\Contracts;

use Qless\Jobs\BaseJob;

/**
 * Interface QlessJob
 * @package LaravelQless\Contracts
 */
interface QlessJob
{
    /**
     * @param BaseJob $job
     * @return mixed
     */
    public function perform(BaseJob $job);
}
