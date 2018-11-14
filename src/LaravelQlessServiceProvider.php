<?php

namespace LaravelQless;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use LaravelQless\Queue\QlessConnector;

/**
 * Class LaravelQlessServiceProvider
 * @package LaravelQless
 */
class LaravelQlessServiceProvider extends ServiceProvider
{
    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        /** @var QueueManager $queue */
        $queue = $this->app['queue'];

        $queue->addConnector('qless', function () {
            return new QlessConnector($this->app['events']);
        });
    }
}
