<?php
namespace GDW\AsyncIndex\Cron;

use GDW\AsyncIndex\Model\JobManager;
use GDW\AsyncIndex\Model\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ProcessJobs
{
    public function __construct(
        private readonly JobManager $jobManager,
        private readonly Logger $logger,
        private readonly ScopeConfigInterface $scopeConfig
    )
    {
    }

    public function execute(): void
    {
        $this->jobManager->cleanHistory();
        $this->logger->cleanOldLogs();
        $jobsPerRun = max(
            1,
            (int)($this->scopeConfig->getValue('gdw/developer_asyncindex/jobs_per_cron_run') ?: 1)
        );
        for ($position = 0; $position < $jobsPerRun; $position++) {
            if (!$this->jobManager->processNext()) {
                break;
            }
        }
    }
}
