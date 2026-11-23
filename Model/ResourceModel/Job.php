<?php
namespace GDW\AsyncIndex\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Job extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('gdw_async_index_job', 'job_id');
    }
}
