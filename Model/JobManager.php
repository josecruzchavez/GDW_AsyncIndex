<?php
namespace GDW\AsyncIndex\Model;

use GDW\AsyncIndex\Model\ResourceModel\Job as JobResource;
use GDW\AsyncIndex\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Exception\LocalizedException;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;

class JobManager
{
    private JobFactory $jobFactory;
    private JobResource $jobResource;
    private JobCollectionFactory $jobCollectionFactory;
    private IndexerRegistry $indexerRegistry;
    private IndexerCollectionFactory $indexerCollectionFactory;
    private LockManagerInterface $lockManager;
    private ResourceConnection $resourceConnection;
    private DateTime $dateTime;
    private Logger $logger;
    private ScopeConfigInterface $scopeConfig;
    private const LOCK_NAME = 'gdw_async_index_process';
    private const DEFAULT_HISTORY_DAYS = 10;
    private const DEFAULT_STALE_AFTER_SECONDS = 7200;
    private const DEFAULT_MAX_PENDING_JOBS = 10;
    private const DEFAULT_MAX_RETRIES = 3;
    private const DEFAULT_DUPLICATE_POLICY = 'identical';
    private const XML_PATH_ENABLED = 'gdw/developer_asyncindex/enabled';
    private const XML_PATH_MAX_PENDING_JOBS = 'gdw/developer_asyncindex/maximum_pending_jobs';
    private const XML_PATH_DUPLICATE_POLICY = 'gdw/developer_asyncindex/duplicate_job_policy';
    private const XML_PATH_AUTOMATIC_RETRY = 'gdw/developer_asyncindex/automatic_retry';
    private const XML_PATH_MAX_RETRIES = 'gdw/developer_asyncindex/maximum_retries';
    private const XML_PATH_HISTORY_DAYS = 'gdw/developer_asyncindex/history_days';
    private const XML_PATH_STALE_AFTER_SECONDS = 'gdw/developer_asyncindex/stale_after_seconds';

    public function __construct(
        JobFactory $jobFactory,
        JobResource $jobResource,
        JobCollectionFactory $jobCollectionFactory,
        IndexerRegistry $indexerRegistry,
        IndexerCollectionFactory $indexerCollectionFactory,
        LockManagerInterface $lockManager,
        ResourceConnection $resourceConnection,
        DateTime $dateTime,
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->jobFactory = $jobFactory;
        $this->jobResource = $jobResource;
        $this->jobCollectionFactory = $jobCollectionFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->lockManager = $lockManager;
        $this->resourceConnection = $resourceConnection;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function create(
        array $indexerIds,
        string $actionType,
        ?int $adminUserId = null,
        int $priority = Job::PRIORITY_LOW,
        string $source = Job::SOURCE_MANUAL,
        ?int $scheduleId = null
    ): Job
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)) {
            throw new LocalizedException(__('Asynchronous index processing is disabled.'));
        }

        if (!$indexerIds) {
            throw new LocalizedException(__('Select at least one indexer.'));
        }

        foreach ($indexerIds as $indexerId) {
            $this->indexerRegistry->get($indexerId);
        }

        $indexerIds = array_values(array_unique(array_map('strval', $indexerIds)));
        sort($indexerIds);
        $pendingJobs = $this->jobCollectionFactory->create()
            ->addFieldToFilter('status', Job::STATUS_PENDING);
        if ($pendingJobs->getSize() >= $this->getMaximumPendingJobs()) {
            throw new LocalizedException(__('The maximum number of pending jobs has been reached.'));
        }

        $activeJobs = $this->jobCollectionFactory->create()
            ->addFieldToFilter('status', ['in' => [Job::STATUS_PENDING, Job::STATUS_RUNNING]]);
        $duplicatePolicy = (string)($this->scopeConfig->getValue(self::XML_PATH_DUPLICATE_POLICY)
            ?: self::DEFAULT_DUPLICATE_POLICY);
        foreach ($activeJobs as $activeJob) {
            if ($duplicatePolicy === 'allow') {
                break;
            }
            $existingIds = $activeJob->getIndexerIds();
            sort($existingIds);
            if (($duplicatePolicy === 'overlap' && array_intersect($existingIds, $indexerIds))
                || ($duplicatePolicy !== 'overlap' && $existingIds === $indexerIds)
            ) {
                throw new LocalizedException(__('An identical job is already pending or running.'));
            }
        }

        $job = $this->jobFactory->create();
        $job->setIndexerIds($indexerIds)
            ->setData('action_type', $actionType)
            ->setData('source', $source === Job::SOURCE_SCHEDULED ? Job::SOURCE_SCHEDULED : Job::SOURCE_MANUAL)
            ->setData('schedule_id', $scheduleId)
            ->setData('priority', in_array($priority, [
                Job::PRIORITY_LOW,
                Job::PRIORITY_NORMAL,
                Job::PRIORITY_HIGH,
            ], true) ? $priority : Job::PRIORITY_LOW)
            ->setData('status', Job::STATUS_PENDING)
            ->setData('message', __('Waiting for cron.')->render())
            ->setData('created_by', $adminUserId)
            ->setData('retry_count', 0)
            ->setData('created_at', $this->dateTime->gmtDate());
        $this->jobResource->save($job);
        $this->logger->info(sprintf('Queued job #%d (%s).', (int)$job->getId(), $actionType));

        return $job;
    }

    public function processNext(): ?Job
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)) {
            return null;
        }

        $this->markStuckJobs();
        if (!$this->lockManager->lock(self::LOCK_NAME, 0)) {
            return null;
        }

        try {
            $job = $this->jobCollectionFactory->create()
                ->addFieldToFilter('status', Job::STATUS_PENDING)
                ->setOrder('priority', 'DESC')
                ->setOrder('created_at', 'ASC')
                ->setPageSize(1)
                ->getFirstItem();

            if (!$job->getId()) {
                return null;
            }

            $job->setData('status', Job::STATUS_RUNNING)
                ->setData('started_at', $this->dateTime->gmtDate())
                ->setData('message', __('Reindexing started.')->render())
                ->setData('progress', 0)
                ->setData('last_activity_at', $this->dateTime->gmtDate());
            $this->jobResource->save($job);
            $this->logger->info(sprintf('Started job #%d.', (int)$job->getId()));

            $indexerIds = $job->getIndexerIds();
            $total = count($indexerIds);
            $metrics = [];
            foreach ($indexerIds as $position => $indexerId) {
                $indexerLockName = 'gdw_async_indexer_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $indexerId);
                if (!$this->lockManager->lock($indexerLockName, 0)) {
                    throw new LocalizedException(__('Indexer %1 is already being processed.', $indexerId));
                }

                $job->setData('current_indexer', $indexerId)
                    ->setData('message', __('Processing %1.', $indexerId)->render())
                    ->setData('progress', (int)floor(($position / max(1, $total)) * 100))
                    ->setData('last_activity_at', $this->dateTime->gmtDate());
                $this->jobResource->save($job);

                try {
                    $startedAt = microtime(true);
                    $indexer = $this->indexerRegistry->get($indexerId);
                    if ($job->getData('action_type') === Job::ACTION_PENDING) {
                        $indexer->getView()->update();
                    } elseif ($job->getData('action_type') === Job::ACTION_INVALIDATE) {
                        $indexer->invalidate();
                    } else {
                        $indexer->reindexAll();
                    }
                    $metrics[$indexerId] = [
                        'duration' => round(microtime(true) - $startedAt, 3),
                        'status' => 'completed',
                    ];
                    $job->setMetrics($metrics);
                } finally {
                    $this->lockManager->unlock($indexerLockName);
                }

                $job->setData('progress', (int)floor((($position + 1) / max(1, $total)) * 100));
                $job->setData('last_activity_at', $this->dateTime->gmtDate());
                $this->jobResource->save($job);
            }

            $job->setData('status', Job::STATUS_COMPLETED)
                ->setData('message', __('Reindexing completed.')->render())
                ->setData('current_indexer', null)
                ->setData('finished_at', $this->dateTime->gmtDate())
                ->setData('progress', 100)
                ->setData('last_activity_at', $this->dateTime->gmtDate());
            $this->jobResource->save($job);
            $this->logger->info(sprintf('Completed job #%d.', (int)$job->getId()));

            return $job;
        } catch (\Throwable $exception) {
            if (isset($job) && $job->getId()) {
                $canRetry = $this->scopeConfig->isSetFlag(self::XML_PATH_AUTOMATIC_RETRY)
                    && (int)$job->getData('retry_count') < $this->getMaximumRetries();
                $job->setData('status', $canRetry ? Job::STATUS_PENDING : Job::STATUS_FAILED)
                    ->setData('message', $canRetry
                        ? __('Reindexing failed; waiting for automatic retry.')->render()
                        : __('Reindexing failed.')->render())
                    ->setData('error_message', $exception->getMessage())
                    ->setData('finished_at', $canRetry ? null : $this->dateTime->gmtDate())
                    ->setData('last_activity_at', $this->dateTime->gmtDate())
                    ->setData('retry_count', $canRetry
                        ? (int)$job->getData('retry_count') + 1
                        : (int)$job->getData('retry_count'));
                $this->jobResource->save($job);
                $this->logger->error(sprintf('Job #%d failed: %s', (int)$job->getId(), $exception->getMessage()));
            }

            return $job ?? null;
        } finally {
            $this->lockManager->unlock(self::LOCK_NAME);
        }
    }

    public function cleanHistory(): void
    {
        $cutoff = gmdate('Y-m-d H:i:s', strtotime('-' . $this->getHistoryDays() . ' days'));
        $collection = $this->jobCollectionFactory->create()
            ->addFieldToFilter('status', ['in' => [
                Job::STATUS_COMPLETED,
                Job::STATUS_FAILED,
                Job::STATUS_CANCELLED,
            ]])
            ->addFieldToFilter('created_at', ['lt' => $cutoff]);

        foreach ($collection as $job) {
            $this->jobResource->delete($job);
        }
    }

    public function getIndexerData(): array
    {
        $data = [];
        foreach ($this->indexerCollectionFactory->create()->getItems() as $indexer) {
            $data[] = [
                'id' => $indexer->getId(),
                'title' => $indexer->getTitle(),
                'status' => $indexer->getStatus(),
                'pending' => $this->getPendingCount($indexer),
            ];
        }

        return $data;
    }

    public function getSummary(): array
    {
        $counts = [
            Job::STATUS_PENDING => 0,
            Job::STATUS_RUNNING => 0,
            Job::STATUS_COMPLETED => 0,
            Job::STATUS_FAILED => 0,
            Job::STATUS_CANCELLED => 0,
        ];
        $collection = $this->jobCollectionFactory->create();
        foreach ($collection as $job) {
            $status = (string)$job->getData('status');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        $counts['pending_items'] = array_sum(array_column($this->getIndexerData(), 'pending'));

        return $counts;
    }

    public function getJob(int $jobId): ?Job
    {
        $job = $this->jobFactory->create();
        $this->jobResource->load($job, $jobId);

        return $job->getId() ? $job : null;
    }

    public function retry(int $jobId): Job
    {
        $job = $this->getJob($jobId);
        if (!$job || !in_array($job->getData('status'), [Job::STATUS_FAILED, Job::STATUS_CANCELLED], true)) {
            throw new LocalizedException(__('Only failed or cancelled jobs can be retried.'));
        }

        $job->setData('status', Job::STATUS_PENDING)
            ->setData('message', __('Waiting for cron.')->render())
            ->setData('error_message', null)
            ->setData('finished_at', null)
            ->setData('last_activity_at', null)
            ->setData('current_indexer', null)
            ->setData('progress', 0)
            ->setData('retry_count', (int)$job->getData('retry_count') + 1);
        $this->jobResource->save($job);
        $this->logger->info(sprintf('Retried job #%d.', $jobId));

        return $job;
    }

    public function cancel(int $jobId): Job
    {
        $job = $this->getJob($jobId);
        if (!$job || $job->getData('status') !== Job::STATUS_PENDING) {
            throw new LocalizedException(__('Only pending jobs can be cancelled.'));
        }

        $job->setData('status', Job::STATUS_CANCELLED)
            ->setData('message', __('Cancelled by administrator.')->render())
            ->setData('finished_at', $this->dateTime->gmtDate());
        $this->jobResource->save($job);
        $this->logger->info(sprintf('Cancelled job #%d.', $jobId));

        return $job;
    }

    public function markStuckJobs(): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - $this->getStaleAfterSeconds());
        $jobs = $this->jobCollectionFactory->create()
            ->addFieldToFilter('status', Job::STATUS_RUNNING)
            ->addFieldToFilter('last_activity_at', ['lt' => $cutoff]);
        $count = 0;
        foreach ($jobs as $job) {
            $job->setData('status', Job::STATUS_FAILED)
                ->setData('message', __('Job marked as failed because it stopped responding.')->render())
                ->setData('error_message', __('No activity was recorded for more than two hours.')->render())
                ->setData('finished_at', $this->dateTime->gmtDate());
            $this->jobResource->save($job);
            $this->logger->error(sprintf('Marked stuck job #%d as failed.', (int)$job->getId()));
            $count++;
        }

        return $count;
    }

    private function getHistoryDays(): int
    {
        return max(
            1,
            (int)($this->scopeConfig->getValue(self::XML_PATH_HISTORY_DAYS) ?: self::DEFAULT_HISTORY_DAYS)
        );
    }

    private function getStaleAfterSeconds(): int
    {
        return max(
            60,
            (int)($this->scopeConfig->getValue(self::XML_PATH_STALE_AFTER_SECONDS)
                ?: self::DEFAULT_STALE_AFTER_SECONDS)
        );
    }

    private function getMaximumPendingJobs(): int
    {
        return max(
            1,
            (int)($this->scopeConfig->getValue(self::XML_PATH_MAX_PENDING_JOBS)
                ?: self::DEFAULT_MAX_PENDING_JOBS)
        );
    }

    private function getMaximumRetries(): int
    {
        return max(
            0,
            (int)($this->scopeConfig->getValue(self::XML_PATH_MAX_RETRIES) ?: self::DEFAULT_MAX_RETRIES)
        );
    }

    private function getPendingCount(\Magento\Framework\Indexer\IndexerInterface $indexer): int
    {
        try {
            $changelog = $indexer->getView()->getChangelog();
            $table = $changelog->getName();
            $connection = $this->resourceConnection->getConnection();
            $stateVersion = (int)$indexer->getView()->getState()->getVersionId();

            return (int)$connection->fetchOne(
                $connection->select()
                    ->from($table, ['count' => 'COUNT(*)'])
                    ->where('version_id > ?', $stateVersion)
            );
        } catch (\Throwable $exception) {
            return 0;
        }
    }
}
