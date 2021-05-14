<?php

namespace LaravelQless\Tests\Job;

use Illuminate\Container\Container;
use LaravelQless\Contracts\JobHandler;
use LaravelQless\Queue\QlessQueue;
use Orchestra\Testbench\TestCase;
use LaravelQless\Job\QlessJob;
use Qless\Jobs\BaseJob;
use Qless\Jobs\JobData;

class QlessJobTest extends TestCase
{
    public function testGetJobId(): void
    {
        $jobMock = $this->getMockBuilder(BaseJob::class)
            ->disableOriginalConstructor()
            ->getMock();
        $jobMock->method('getJid')->willReturn('my-test-jid');

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $jobMock, ''));

        self::assertEquals($job->getJobId(), 'my-test-jid');
    }

    public function testGetData(): void
    {
        $jobMock = $this->getMockBuilder(BaseJob::class)
            ->disableOriginalConstructor()
            ->getMock();
        $jobMock->method('getData')->willReturn(new JobData(['key' => 'value']));

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $jobMock, ''));

        self::assertEquals($job->getData(), ['key' => 'value']);
    }

    public function testPayload(): void
    {
        $payload = '{"key": "value"}';

        $job = (new QlessJob($this->getContainer(), $this->getQueue(), $this->getJobHandler(), $this->getJob(), $payload));

        self::assertEquals($job->payload(), ['key' => 'value']);
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
