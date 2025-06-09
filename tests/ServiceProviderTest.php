<?php

namespace LaravelQless\Tests;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use LaravelQless\LaravelQlessServiceProvider;
use LaravelQless\Queue\QlessConnector;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testShouldSubClassServiceProviderClass()
    {
        $rc = new \ReflectionClass(LaravelQlessServiceProvider::class);
        $this->assertTrue($rc->isSubclassOf(ServiceProvider::class));
    }

    public function testBoot()
    {
        $queueMock = $this->createMock(QueueManager::class);
        $queueMock
            ->expects(self::once())
            ->method('addConnector')
            ->with('qless', self::isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($driver, \Closure $resolver) {
                $connector = $resolver();
                $this->assertInstanceOf(QlessConnector::class, $connector);
            })
        ;

        $app = $this->app;
        $app['queue'] = $queueMock;

        $app['events'] = $this->createMock(Dispatcher::class);

        $providerMock = new LaravelQlessServiceProvider($app);
        $providerMock->boot();
    }
}
