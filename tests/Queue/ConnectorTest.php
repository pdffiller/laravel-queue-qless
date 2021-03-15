<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Contracts\JobHandler;
use Orchestra\Testbench\TestCase;
use Illuminate\Queue\Connectors\ConnectorInterface;
use LaravelQless\Queue\QlessConnector;
use LaravelQless\Queue\QlessQueue;
use Predis\Client;

class ConnectorTest extends TestCase
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
            'redis_connection' => 'qless',
            'connection' => 'qless',
        ]);
        $this->assertInstanceOf(QlessQueue::class, $queue);
    }

    public function testShardingConnect()
    {
        $connector = new QlessConnector();
        $queue = $connector->connect([
            'redis_connection' => ['qless1', 'qless2'],
            'connection' => 'qless',
        ]);
        $this->assertInstanceOf(QlessQueue::class, $queue);


        $this->assertInstanceOf(Client::class, $queue->getNextConnection());
        $this->assertInstanceOf(Client::class, $queue->getNextConnection());
        $this->assertInstanceOf(Client::class, $queue->getNextConnection());
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

    protected function getApplicationProviders($app)
    {
        $app->bindIf(JobHandler::class, CustomHandler::class);

        return $app['config']['app.providers'];
    }
}
