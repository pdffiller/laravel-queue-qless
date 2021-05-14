<?php

namespace LaravelQless\Tests\Job;

use Illuminate\Container\Container;
use LaravelQless\Contracts\JobHandler;
use LaravelQless\Queue\QlessQueue;
use LaravelQless\Tests\Helpers\ModifierTrait;
use Orchestra\Testbench\TestCase;
use LaravelQless\Job\QlessJob;
use Qless\Jobs\BaseJob;
use Qless\Jobs\JobData;

class QlessJobTest extends TestCase
{
    public function testGetJobId(): void
    {
        $jobMock = $this->getJob();
        $jobMock->method('getJid')
            ->willReturn('my-test-jid');

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $jobMock, ''));

        self::assertEquals($job->getJobId(), 'my-test-jid');
    }

    public function testGetData(): void
    {
        $jobMock = $this->getJob();
        $jobMock->method('getData')
            ->willReturn(new JobData(['key' => 'value']));

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $jobMock, ''));

        self::assertEquals($job->getData(), ['key' => 'value']);
    }

    public function testPayload(): void
    {
        $payload = '{"key": "value"}';

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $this->getJob(), $payload));

        self::assertEquals($job->payload(), ['key' => 'value']);
    }

    public function testFireSuccess(): void
    {
        self::markTestSkipped('Refactor class to set `failed` property');
    }

    public function testFireFailed(): void
    {
        self::markTestSkipped('Refactor class to set `failed` property');
    }

    public function testRelease(): void
    {
        $queue = $this->getQueue();
        $queue->expects(self::once())
            ->method('later')
            ->willReturn('job-id');

        $jobMock = $this->getJob();
        $jobMock->method('getData')
            ->willReturn(new JobData(['key' => 'value']));

        $job = (new QlessJob($this->getContainer(), $queue, $this->getJobHandler(), $jobMock, ''));

        self::assertFalse($job->isReleased());

        self::assertEquals($job->release(), 'job-id');

        self::assertTrue($job->isReleased());
    }

    public function testDelete(): void
    {
        $job = $this->getJob();
        $job->expects(self::once())
            ->method('cancel');

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $job, ''));

        $job->delete();
    }

    public function testAttempts(): void
    {
        $job = $this->getJob();
        $job->expects(self::once())
            ->method('getRemaining')
            ->willReturn(3);

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $job, ''));

        self::assertEquals($job->attempts(), 3);
    }

    public function testMaxTries(): void
    {
        $job = $this->getJob();
        $job->expects(self::once())
            ->method('getRetries')
            ->willReturn(10);

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $job, ''));

        self::assertEquals($job->maxTries(), 10);
    }

    public function testTimeout(): void
    {
        $job = $this->getJob();
        $job->expects(self::once())
            ->method('ttl')
            ->willReturn(123.0);

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $job, ''));

        self::assertEquals($job->timeout(), 123);
    }

    /**
     * @depends testTimeout
     */
    public function testTimeoutAt(): void
    {
        self::markTestSkipped('It is needed to refactor the class');
    }

    public function testGetName(): void
    {
        $job = $this->getJob();
        $job->expects(self::once())
            ->method('getKlass')
            ->willReturn('jobClass');

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $job, ''));

        self::assertEquals($job->getName(), 'jobClass');
    }

    public function testGetQueue(): void
    {
        $job = $this->getJob();
        $job->expects(self::once())
            ->method('getQueue')
            ->willReturn('jobName');

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $job, ''));

        self::assertEquals($job->getQueue(), 'jobName');
    }

    public function testGetRawBody(): void
    {
        $payload = 'raw body';

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $this->getJob(), $payload));

        self::assertEquals($job->getRawBody(), 'raw body');
    }

    /**
     * @return Container|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getContainer()
    {
        return $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return QlessQueue|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQueue()
    {
        return $this->getMockBuilder(QlessQueue::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return JobHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getJobHandler()
    {
        return $this->getMockBuilder(JobHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return BaseJob|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getJob()
    {
        return $this->getMockBuilder(BaseJob::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

}
