<?php

namespace LaravelQless\Tests;

use Illuminate\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use LaravelQless\LaravelQlessServiceProvider;
use LaravelQless\Queue\QlessConnector;

class LaravelQlessServiceProviderTest extends TestCase
{

    public function testShouldSubClassServiceProviderClass()
    {
        $rc = new \ReflectionClass(LaravelQlessServiceProvider::class);
        $this->assertTrue($rc->isSubclassOf(ServiceProvider::class));
    }


    public function testBoot()
    {
        /*$queueMock = $this->createMock(QueueManager::class);
        $queueMock
            ->expects($this->once())
            ->method('addConnector')
            ->with('qless', $this->isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($driver, \Closure $resolver) {
                $connector = $resolver();
                $this->assertInstanceOf(QlessConnector::class, $connector);
            })
        ;
        $app = Container::getInstance();
        $app['queue'] = $queueMock;
        $providerMock = new LaravelQlessServiceProvider($app);
        $providerMock->boot();*/
    }
}
