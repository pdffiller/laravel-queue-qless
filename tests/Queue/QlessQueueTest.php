<?php

namespace LaravelQless\Tests\Queue;

use LaravelQless\Job\QlessJob;
use LaravelQless\Queue\QlessQueue;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Qless\Client;

class QlessQueueTest extends TestCase
{
    public function testShouldImplementQueueInterface()
    {
        $rc = new \ReflectionClass(QlessQueue::class);
        $this->assertTrue($rc->implementsInterface(QueueContract::class));
    }

    public function testShouldBeSubClassOfQueue()
    {
        $rc = new \ReflectionClass(QlessQueue::class);
        $this->assertTrue($rc->isSubclassOf(Queue::class));
    }

    public function testPushPop()
    {
        $queue = $this->getQueue();

        $jobId = $queue->push(Job::class, ['firstKey' => 'firstValue'], 'test_push_pop');

        $job = $queue->pop('test_push_pop');

        $job->fire();

        $this->assertInstanceOf(QlessJob::class, $job);

        $this->assertEquals($jobId, $job->getJobId());

        $this->assertEquals($job->getQueue(), 'test_push_pop');

        $this->assertEquals($job->getName(), Job::class);

        $this->assertEquals($job->getData(), ['firstKey' => 'firstValue']);
    }

    public function testSubscribe()
    {
        $queue = $this->getQueue();

        for ($i = 1; $i<=3; $i++) {
            $queue->push(Job::class, ['firstKey' => 'firstValue'], 'qs_' . $i);
            $job = $queue->pop('qs_' . $i);
            $job->fire();
        }

        $queue->subscribe('developing#', 'qs_1');
        $queue->subscribe('*.test.*', 'qs_2');
        $queue->subscribe('*.*.awesome', 'qs_3');

        $queue->pushToTopic('developing.is.awesome', Job::class, ['key' => 'value']);

        $job1 = $queue->pop('qs_1');
        $job1->fire();
        $job2 = $queue->pop('qs_2');
        $job3 = $queue->pop('qs_3');
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

        for ($i = 1; $i<=2; $i++) {
            $queue->push(Job::class, ['firstKey' => 'firstValue'], 'qu_' . $i);
            $job = $queue->pop('qu_' . $i);
            $job->fire();
        }

        $queue->subscribe('developing.*.*', 'qu_1');
        $queue->subscribe('*.*.awesome', 'qu_2');

        $queue->unSubscribe('*.*.awesome', 'qu_2');

        $queue->pushToTopic('developing.is.awesome', Job::class, ['key' => 'value']);

        $job1 = $queue->pop('qu_1');
        $job1->fire();
        $job2 = $queue->pop('qu_2');

        $this->assertEquals($job1->getName(), Job::class);
        $this->assertEquals($job2, null);
    }
    
    public function testSize()
    {
        $queue = $this->getQueue();

        $this->assertEquals($queue->size('test_size'), 0);

        $queue->push(Job::class, ['firstKey' => 'firstValue'], 'test_size');

        $this->assertEquals($queue->size('test_size'), 1);

        $job = $queue->pop('test_size');

        $job->delete();

        $this->assertEquals($queue->size('test_size'), 0);
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
}
