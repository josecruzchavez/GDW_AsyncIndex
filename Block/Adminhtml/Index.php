<?php
namespace GDW\AsyncIndex\Block\Adminhtml;

use GDW\AsyncIndex\Model\Job;
use GDW\AsyncIndex\Model\JobManager;
use GDW\AsyncIndex\Model\ResourceModel\Job\CollectionFactory;
use Magento\Backend\Block\Template;

class Index extends Template
{
    private JobManager $jobManager;
    private CollectionFactory $collectionFactory;
    public function __construct(
        Template\Context $context,
        JobManager $jobManager,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jobManager = $jobManager;
        $this->collectionFactory = $collectionFactory;
    }

    public function getIndexerData(): array
    {
        return $this->jobManager->getIndexerData();
    }

    public function getRecentJobs(int $pageSize = 10, int $currentPage = 1)
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'DESC')
            ->setPageSize($pageSize)
            ->setCurPage($currentPage);
    }

    public function getStatusLabel(string $status): string
    {
        return Job::getStatusLabel($status);
    }

    public function getActionLabel(string $action): string
    {
        return Job::getActionLabel($action);
    }

    public function getSummary(): array
    {
        return $this->jobManager->getSummary();
    }

}
