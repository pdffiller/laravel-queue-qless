<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Contracts\JobHandler;
use LaravelQless\Handler\DefaultHandler;
use LaravelQless\Job\QlessJob;
use LaravelQless\Queue\QlessQueue;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Qless\Client;

class QlessQueueTest extends TestCase
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

        $queueName = 'test_push_pop_' . random_int(1000, 5000);

        $jobId = $queue->push(Job::class, ['firstKey' => 'firstValue'], $queueName);

        $job = $queue->pop($queueName);

        $job->fire();

        $this->assertInstanceOf(QlessJob::class, $job);

        $this->assertEquals($jobId, $job->getJobId());

        $this->assertEquals($job->getQueue(), $queueName);

        $this->assertEquals($job->getName(), Job::class);

        $this->assertEquals($job->getData(), ['firstKey' => 'firstValue']);
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

        $this->assertEquals($job1->getData(), ['key' => 'value']);
        $this->assertEquals($job3->getData(), ['key' => 'value']);
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
        $queueName = 'test_size' . random_int(1000, 5000);

        $queue = $this->getQueue();

        $this->assertEquals($queue->size($queueName), 0);

        $queue->push(Job::class, ['firstKey' => 'firstValue'], $queueName);

        $this->assertEquals($queue->size($queueName), 1);

        $job = $queue->pop($queueName);

        $job->delete();

        $this->assertEquals($queue->size($queueName), 0);
    }

    protected function getQueue()
    {
        return new QlessQueue(
            new Client([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
            ]),
            [
                'queue' => 'test_qless_queue'
            ]
        );
    }

    protected function getApplicationProviders($app)
    {
        $app->bind(JobHandler::class, DefaultHandler::class);

        return $app['config']['app.providers'];
    }
}
