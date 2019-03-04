<?php

namespace LaravelQless\Tests\Queue;

use Illuminate\Queue\QueueManager;
use LaravelQless\Contracts\JobHandler;
use LaravelQless\Handler\DefaultHandler;
use LaravelQless\Job\QlessJob;
use LaravelQless\Queue\QlessConnector;
use LaravelQless\Queue\QlessQueue;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Qless\Client;

class QueueTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testShouldImplementQueueInterface()
    {
        $rc = new \ReflectionClass(QlessQueue::class);
        $this->assertTrue($rc->implementsInterface(QueueContract::class));
    }

    /**
     * @throws \ReflectionException
     */
    public function testShouldBeSubClassOfQueue()
    {
        $rc = new \ReflectionClass(QlessQueue::class);
        $this->assertTrue($rc->isSubclassOf(Queue::class));
    }

    /**
     * @throws \Exception
     */
    public function testPushPop()
    {
        $queue = $this->getQueue();

        $queueName = str_random(16);

        $jobId = $queue->push(Job::class, ['firstKey' => 'firstValue'], $queueName);

        $job = $queue->pop($queueName);

        $job->fire();

        $this->assertInstanceOf(QlessJob::class, $job);

        $this->assertEquals($jobId, $job->getJobId());

        $this->assertEquals($job->getQueue(), $queueName);

        $this->assertEquals($job->getName(), Job::class);

        $data = $job->getData();
        unset($data[QlessQueue::JOB_OPTIONS_KEY]);

        $this->assertEquals($data, ['firstKey' => 'firstValue']);
    }

    public function testSubscribe()
    {
        $queue = $this->getQueue();

        $queuePrefix = str_random(4) . '_';

        for ($i = 1; $i<=3; $i++) {
            $queue->push(Job::class, ['firstKey' => 'firstValue'], $queuePrefix . $i);
            $job = $queue->pop($queuePrefix . $i);
            $job->fire();
        }

        $queue->subscribe('developing#', $queuePrefix . '1');
        $queue->subscribe('*.test.*', $queuePrefix . '2');
        $queue->subscribe('*.*.awesome', $queuePrefix . '3');

        $queue->pushToTopic('developing.is.awesome', Job::class, ['key' => 'value']);

        $job1 = $queue->pop($queuePrefix . '1');
        $job1->fire();
        $job2 = $queue->pop($queuePrefix . '2');
        $job3 = $queue->pop($queuePrefix . '3');
        $job3->fire();

        $this->assertEquals($job1->getName(), Job::class);
        $this->assertEquals($job2, null);
        $this->assertEquals($job3->getName(), Job::class);

        $data1 = $job1->getData();
        $data3 = $job3->getData();
        unset($data1[QlessQueue::JOB_OPTIONS_KEY], $data3[QlessQueue::JOB_OPTIONS_KEY]);

        $this->assertEquals($data1, ['key' => 'value']);
        $this->assertEquals($data3, ['key' => 'value']);
    }

    public function testUnSubscribe()
    {
        $queue = $this->getQueue();

        $queuePrefix = str_random(4) . '_';

        for ($i = 1; $i<=2; $i++) {
            $queue->push(Job::class, ['firstKey' => 'firstValue'], $queuePrefix . $i);
            $job = $queue->pop($queuePrefix . $i);
            $job->fire();
        }

        $queue->subscribe('developing.*.*', $queuePrefix . '1');
        $queue->subscribe('*.*.awesome', $queuePrefix . '2');

        $queue->unSubscribe('*.*.awesome', $queuePrefix . '2');

        $queue->pushToTopic('developing.is.awesome', Job::class, ['key' => 'value']);

        $job1 = $queue->pop($queuePrefix . '1');
        $job1->fire();
        $job2 = $queue->pop($queuePrefix . '2');

        $this->assertEquals($job1->getName(), Job::class);
        $this->assertEquals($job2, null);
    }

    /**
     * @throws \Exception
     */
    public function testSize()
    {
        $queueName = str_random();

        $queue = $this->getQueue();

        $this->assertEquals($queue->size($queueName), 0);

        $queue->push(Job::class, ['firstKey' => 'firstValue'], $queueName);

        $this->assertEquals($queue->size($queueName), 1);

        $job = $queue->pop($queueName);

        $job->delete();

        $this->assertEquals($queue->size($queueName), 0);
    }

    /**
     * @throws \Exception
     */
    public function testJobOptions()
    {
        $queueName = str_random();

        $jid = str_random(16);

        $queue = $this->getQueue();

        $data = [
            QlessQueue::JOB_OPTIONS_KEY => [
                'jid' => $jid,
                'tags' => ['tag1', 'tag_second'],
            ],
            'firstKey' => 'firstValue',
        ];

        $queue->push(Job::class, $data, $queueName);

        $job = $queue->pop($queueName);
        $job->fire();

        $this->assertEquals($jid, $job->getJobId());
        $this->assertEquals(['tag1', 'tag_second'], $job->getData()['tags']);
    }

    public function testDispatchJob()
    {
        $this->setEnv();

        $queueName = str_random();

        $job = new Job(['dispatch' => 'work']);

        $dispatch = dispatch($job)->onQueue($queueName)->onConnection('qless');
        unset($dispatch);

        $queue = $this->getQueue();

        $job = $queue->pop($queueName);

        $this->assertNotEmpty($job);

        $job->fire();

        $data = $job->getData();

        $this->assertEquals('work', $data['dispatch']);
    }

    public function testDispatchNowJob()
    {
        $this->setEnv();

        $queueName = str_random();

        $dispatch = Job::dispatchNow(['dispatchNow' => 'work_as_sync'])
            ->onQueue($queueName)
            ->onConnection('qless');

        unset($dispatch);

        $queue = $this->getQueue();

        $job = $queue->pop($queueName);

        $this->assertEquals($_SERVER['payload']['dispatchNow'], 'work_as_sync');

        $this->assertNull($job);
    }

    protected function getQueue()
    {
        $queue = new QlessQueue(
            new Client([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
            ]),
            [
                'queue' => 'test_qless_queue',
                'connection' => 'qless',
            ]
        );

        $queue->setContainer($this->app);

        return $queue;
    }


    protected function setEnv()
    {
        $this->app['config']->set('queue.default', 'qless');
        $qlessConfig = [
            'driver' => 'qless',
            'connection' => 'qless',
            'queue' => 'default',
            'redis_connection' => 'qless',
        ];
        $this->app['config']->set('queue.connections.qless', $qlessConfig);

        $redisConfig = [
            'host' => REDIS_HOST,
            'password' => null,
            'port' => REDIS_PORT,
            'database' => 0,
        ];

        $this->app['config']->set('database.redis.qless', $redisConfig);

        $queueManager = new QueueManager($this->app);
        $queueManager->addConnector('qless', function () {
            return new QlessConnector;
        });
        $queueManager->setDefaultDriver('qless');

        $this->app['queue'] = $queueManager;
    }

    protected function getApplicationProviders($app)
    {
        $app->bind(JobHandler::class, DefaultHandler::class);

        return $app['config']['app.providers'];
    }
}
