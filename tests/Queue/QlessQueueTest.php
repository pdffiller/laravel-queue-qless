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

        $jobId = $queue->push(Job::class, ['firstKey' => 'firstValue'], 'test_job');

        $job = $queue->pop('test_job');

        $this->assertInstanceOf(QlessJob::class, $job);

        $this->assertEquals($jobId, $job->getJobId());

        $this->assertEquals($job->getQueue(), 'test_job');

        $this->assertEquals($job->getName(), Job::class);

        $this->assertEquals($job->getData(), ['firstKey' => 'firstValue']);
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
