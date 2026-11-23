<?php
declare(strict_types=1);

namespace GDW\AsyncIndex\Test\Unit\Model;

use GDW\AsyncIndex\Model\Job;
use GDW\AsyncIndex\Model\JobFactory;
use GDW\AsyncIndex\Model\JobManager;
use GDW\AsyncIndex\Model\Logger;
use GDW\AsyncIndex\Model\ResourceModel\Job as JobResource;
use GDW\AsyncIndex\Model\ResourceModel\Job\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;
use PHPUnit\Framework\TestCase;

class JobManagerTest extends TestCase
{
    public function testRetryResetsExecutionStateAndIncrementsRetryCount(): void
    {
        $job = $this->createJob(Job::STATUS_FAILED);
        $job->setData('retry_count', 2);
        $resource = $this->createMock(JobResource::class);
        $resource->expects(self::once())->method('load')->with($job, 42);
        $resource->expects(self::once())->method('save')->with($job);

        $manager = $this->createManager($job, $resource);
        $result = $manager->retry(42);

        self::assertSame($job, $result);
        self::assertSame(Job::STATUS_PENDING, $job->getData('status'));
        self::assertSame(0, (int)$job->getData('progress'));
        self::assertSame(3, (int)$job->getData('retry_count'));
        self::assertNull($job->getData('error_message'));
        self::assertNull($job->getData('finished_at'));
    }

    public function testCancelMarksPendingJobAsCancelled(): void
    {
        $job = $this->createJob(Job::STATUS_PENDING);
        $resource = $this->createMock(JobResource::class);
        $resource->expects(self::once())->method('load')->with($job, 42);
        $resource->expects(self::once())->method('save')->with($job);

        $manager = $this->createManager($job, $resource);
        $manager->cancel(42);

        self::assertSame(Job::STATUS_CANCELLED, $job->getData('status'));
        self::assertSame('Cancelled by administrator.', $job->getData('message'));
        self::assertNotNull($job->getData('finished_at'));
    }

    public function testRetryRejectsCompletedJob(): void
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Only failed or cancelled jobs can be retried.');

        $job = $this->createJob(Job::STATUS_COMPLETED);
        $resource = $this->createMock(JobResource::class);
        $resource->expects(self::once())->method('load')->with($job, 42);

        $this->createManager($job, $resource)->retry(42);
    }

    private function createJob(string $status): Job
    {
        return (new Job())
            ->setId(42)
            ->setData('status', $status)
            ->setData('message', 'Previous message')
            ->setData('error_message', 'Previous error')
            ->setData('finished_at', '2026-11-20 00:00:00')
            ->setData('progress', 80);
    }

    private function createManager(Job $job, JobResource $resource): JobManager
    {
        $jobFactory = $this->createMock(JobFactory::class);
        $jobFactory->method('create')->willReturn($job);
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtDate')->willReturn('2026-11-20 07:00:00');
        $logger = $this->createMock(Logger::class);

        return new JobManager(
            $jobFactory,
            $resource,
            $this->createMock(CollectionFactory::class),
            $this->createMock(IndexerRegistry::class),
            $this->createMock(IndexerCollectionFactory::class),
            $this->createMock(LockManagerInterface::class),
            $this->createMock(ResourceConnection::class),
            $dateTime,
            $logger,
            $this->createMock(ScopeConfigInterface::class)
        );
    }
}
