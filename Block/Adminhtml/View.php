<?php
namespace GDW\AsyncIndex\Block\Adminhtml;

use GDW\AsyncIndex\Model\Job;
use GDW\AsyncIndex\Model\JobManager;
use Magento\Backend\Block\Template;

class View extends Template
{
    private JobManager $jobManager;
    public function __construct(
        Template\Context $context,
        JobManager $jobManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jobManager = $jobManager;
    }

    public function getJob(): ?Job
    {
        return $this->jobManager->getJob((int)$this->getRequest()->getParam('job_id'));
    }

    public function getStatusLabel(string $status): string
    {
        return Job::getStatusLabel($status);
    }

    public function getActionLabel(string $action): string
    {
        return Job::getActionLabel($action);
    }
}
