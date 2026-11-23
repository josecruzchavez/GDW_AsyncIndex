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
        switch ($priority) {
            case self::PRIORITY_HIGH:
                return (string)__('High');
            case self::PRIORITY_LOW:
                return (string)__('Low');
            default:
                return (string)__('Normal');
        }
    }

    public static function getActionLabel(string $action): string
    {
        switch ($action) {
            case self::ACTION_FULL:
                return (string)__('Reindex Full');
            case self::ACTION_REINDEX:
                return (string)__('Reindex');
            case self::ACTION_PENDING:
                return (string)__('Process Pending Items');
            case self::ACTION_INVALIDATE:
                return (string)__('Invalidate');
            default:
                return $action;
        }
    }

    public static function getStatusLabel(string $status): string
    {
        switch ($status) {
            case self::STATUS_PENDING:
                return (string)__('Pending');
            case self::STATUS_RUNNING:
                return (string)__('Running');
            case self::STATUS_COMPLETED:
                return (string)__('Completed');
            case self::STATUS_FAILED:
                return (string)__('Failed');
            case self::STATUS_CANCELLED:
                return (string)__('Cancelled');
            default:
                return $status;
        }
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
