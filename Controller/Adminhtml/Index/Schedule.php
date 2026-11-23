<?php
namespace GDW\AsyncIndex\Controller\Adminhtml\Index;

use GDW\AsyncIndex\Model\Job;
use GDW\AsyncIndex\Model\JobManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Schedule extends Action
{
    private JsonFactory $jsonFactory;
    private JobManager $jobManager;
    private Session $authSession;
    private ScopeConfigInterface $scopeConfig;
    public const ADMIN_RESOURCE = 'GDW_AsyncIndex::index';

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        JobManager $jobManager,
        Session $authSession,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->jobManager = $jobManager;
        $this->authSession = $authSession;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $indexerIds = (array)$this->getRequest()->getParam('indexers', []);
        $actionType = (string)$this->getRequest()->getParam('action_type', Job::ACTION_REINDEX);
        $priority = (int)$this->getRequest()->getParam('priority', Job::PRIORITY_LOW);
        $allowedActions = [
            Job::ACTION_FULL,
            Job::ACTION_REINDEX,
            Job::ACTION_PENDING,
            Job::ACTION_INVALIDATE,
        ];
        $actionConfigPaths = [
            Job::ACTION_FULL => 'gdw/developer_asyncindex/allow_full_reindex',
            Job::ACTION_REINDEX => 'gdw/developer_asyncindex/allow_full_reindex',
            Job::ACTION_PENDING => 'gdw/developer_asyncindex/allow_pending_items',
            Job::ACTION_INVALIDATE => 'gdw/developer_asyncindex/allow_invalidate',
        ];

        try {
            $basicActions = [
                Job::ACTION_FULL,
                Job::ACTION_REINDEX,
                Job::ACTION_PENDING,
                Job::ACTION_INVALIDATE,
            ];
            if (!isset($actionConfigPaths[$actionType])
                || !in_array($actionType, $basicActions, true)
                || (!$this->scopeConfig->getValue($actionConfigPaths[$actionType])
                    && $this->scopeConfig->getValue($actionConfigPaths[$actionType]) !== null)
            ) {
                throw new \Magento\Framework\Exception\LocalizedException(__('This action is disabled.'));
            }
            $job = $this->jobManager->create(
                $indexerIds,
                in_array($actionType, $allowedActions, true) ? $actionType : Job::ACTION_REINDEX,
                (int)$this->authSession->getUser()->getId(),
                $priority
            );

            return $result->setData([
                'success' => true,
                'job_id' => (int)$job->getId(),
                'message' => __('Reindexing queued.')->render(),
            ]);
        } catch (\Throwable $exception) {
            return $result->setHttpResponseCode(400)->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
