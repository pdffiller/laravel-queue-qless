<?php

namespace LaravelQless\Tests\Queue;

use Orchestra\Testbench\TestCase;
use Illuminate\Queue\Connectors\ConnectorInterface;
use LaravelQless\Queue\QlessConnector;
use LaravelQless\Queue\QlessQueue;

class QlessConnectorTest extends TestCase
{
    public function testShouldImplementConnectorInterface()
    {
        $rc = new \ReflectionClass(QlessConnector::class);
        $this->assertTrue($rc->implementsInterface(ConnectorInterface::class));
    }

    public function testConnect()
    {
        $connector = new QlessConnector();
        $queue = $connector->connect([
            'redis_connection' => 'qless'
        ]);
        $this->assertInstanceOf(QlessQueue::class, $queue);
    }

    /**
     * Set laravel config
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default redis
        $app['config']->set('database.redis.qless', [
            'host' => REDIS_HOST,
            'port' => REDIS_PORT,
        ]);
    }
}
