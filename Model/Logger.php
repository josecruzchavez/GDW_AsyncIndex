<?php
namespace GDW\AsyncIndex\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Logger
{
    private DirectoryList $directoryList;
    private ScopeConfigInterface $scopeConfig;
    private const XML_PATH_LOGGING_ENABLED = 'gdw/developer_asyncindex/logging_enabled';

    public function __construct(
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    public function cleanOldLogs(): void
    {
        $directory = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/log/gdw_asyncindex';
        $retentionDays = max(
            1,
            (int)($this->scopeConfig->getValue('gdw/developer_asyncindex/log_retention_days') ?: 10)
        );
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . '/gdw_asyncindex_*.log') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - ($retentionDays * 86400)) {
                unlink($file);
            }
        }
    }

    private function write(string $level, string $message): void
    {
        $loggingEnabled = $this->scopeConfig->getValue(self::XML_PATH_LOGGING_ENABLED);
        if ($loggingEnabled !== null && !(bool)$loggingEnabled) {
            return;
        }

        $directory = $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/log/gdw_asyncindex';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create GDW Async Index log directory.');
        }

        $file = $directory . '/gdw_asyncindex_' . gmdate('Y-m-d') . '.log';
        $line = sprintf("[%s] %s: %s\n", gmdate('Y-m-d H:i:s'), $level, $message);
        if (file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write GDW Async Index log.');
        }
    }
}
