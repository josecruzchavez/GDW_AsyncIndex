<?php
declare(strict_types=1);

namespace GDW\AsyncIndex\Test\Unit\Model;

use GDW\AsyncIndex\Model\Job;
use GDW\AsyncIndex\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    /**
     * @dataProvider actionLabelProvider
     */
    public function testActionLabels(string $action, string $expected): void
    {
        self::assertSame($expected, Job::getActionLabel($action));
    }

    public function actionLabelProvider(): array
    {
        return [
            'full reindex' => [Job::ACTION_FULL, 'Reindex Full'],
            'selected reindex' => [Job::ACTION_REINDEX, 'Reindex'],
            'pending items' => [Job::ACTION_PENDING, 'Process Pending Items'],
            'invalidate' => [Job::ACTION_INVALIDATE, 'Invalidate'],
            'unknown action' => ['custom', 'custom'],
        ];
    }

    /**
     * @dataProvider statusLabelProvider
     */
    public function testStatusLabels(string $status, string $expected): void
    {
        self::assertSame($expected, Job::getStatusLabel($status));
    }

    public function statusLabelProvider(): array
    {
        return [
            'pending' => [Job::STATUS_PENDING, 'Pending'],
            'running' => [Job::STATUS_RUNNING, 'Running'],
            'completed' => [Job::STATUS_COMPLETED, 'Completed'],
            'failed' => [Job::STATUS_FAILED, 'Failed'],
            'cancelled' => [Job::STATUS_CANCELLED, 'Cancelled'],
            'unknown status' => ['custom', 'custom'],
        ];
    }

    public function testIndexerIdsAreSerializedAndReadBackAsStrings(): void
    {
        $job = $this->createJob();
        $job->setIndexerIds(['catalog_product', 'catalog_category']);

        self::assertSame(['catalog_product', 'catalog_category'], $job->getIndexerIds());
    }

    public function testInvalidIndexerIdsPayloadReturnsEmptyArray(): void
    {
        $job = $this->createJob();
        $job->setData('indexer_ids', '{"invalid":true}');

        self::assertSame([], $job->getIndexerIds());
    }

    private function createJob(): Job
    {
        $resource = $this->createMock(JobResource::class);
        $resource->method('getIdFieldName')->willReturn('job_id');

        return new Job(
            $this->createMock(Context::class),
            $this->createMock(Registry::class),
            $resource
        );
    }
}
