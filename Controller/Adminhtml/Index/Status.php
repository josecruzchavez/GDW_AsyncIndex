<?php
namespace GDW\AsyncIndex\Controller\Adminhtml\Index;

use GDW\AsyncIndex\Model\JobFactory;
use GDW\AsyncIndex\Model\ResourceModel\Job as JobResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Status extends Action
{
    public const ADMIN_RESOURCE = 'GDW_AsyncIndex::index';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly JobFactory $jobFactory,
        private readonly JobResource $jobResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $job = $this->jobFactory->create();
        $this->jobResource->load($job, (int)$this->getRequest()->getParam('job_id'));

        if (!$job->getId()) {
            return $result->setHttpResponseCode(404)->setData([
                'success' => false,
                'message' => __('The reindexing job does not exist.')->render(),
            ]);
        }

        return $result->setData([
            'success' => true,
            'job_id' => (int)$job->getId(),
            'status' => (string)$job->getData('status'),
            'progress' => (int)$job->getData('progress'),
            'current_indexer' => (string)$job->getData('current_indexer'),
            'message' => (string)$job->getData('message'),
            'error_message' => (string)$job->getData('error_message'),
        ]);
    }
}
