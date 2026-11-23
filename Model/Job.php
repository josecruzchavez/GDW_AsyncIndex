<?php
namespace GDW\AsyncIndex\Model;

use GDW\AsyncIndex\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Model\AbstractModel;

class Job extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ACTION_FULL = 'full';
    public const ACTION_REINDEX = 'reindex';
    public const ACTION_PENDING = 'pending';
    public const ACTION_INVALIDATE = 'invalidate';
    public const PRIORITY_LOW = 10;
    public const PRIORITY_NORMAL = 20;
    public const PRIORITY_HIGH = 30;
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SCHEDULED = 'scheduled';

    public static function getPriorityLabel(int $priority): string
    {
        return match ($priority) {
            self::PRIORITY_HIGH => (string)__('High'),
            self::PRIORITY_LOW => (string)__('Low'),
            default => (string)__('Normal'),
        };
    }

    public static function getActionLabel(string $action): string
    {
        return match ($action) {
            self::ACTION_FULL => (string)__('Reindex Full'),
            self::ACTION_REINDEX => (string)__('Reindex'),
            self::ACTION_PENDING => (string)__('Process Pending Items'),
            self::ACTION_INVALIDATE => (string)__('Invalidate'),
            default => $action,
        };
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => (string)__('Pending'),
            self::STATUS_RUNNING => (string)__('Running'),
            self::STATUS_COMPLETED => (string)__('Completed'),
            self::STATUS_FAILED => (string)__('Failed'),
            self::STATUS_CANCELLED => (string)__('Cancelled'),
            default => $status,
        };
    }

    protected function _construct()
    {
        $this->_init(JobResource::class);
    }

    public function getIndexerIds(): array
    {
        $value = json_decode((string)$this->getData('indexer_ids'), true);

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    public function setIndexerIds(array $indexerIds): self
    {
        return $this->setData('indexer_ids', json_encode(array_values($indexerIds)));
    }

    public function getMetrics(): array
    {
        $value = json_decode((string)$this->getData('metrics'), true);

        return is_array($value) ? $value : [];
    }

    public function setMetrics(array $metrics): self
    {
        return $this->setData('metrics', json_encode($metrics));
    }
}
