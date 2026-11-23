<?php
namespace GDW\AsyncIndex\Model\ResourceModel\Job;

use GDW\AsyncIndex\Model\Job as JobModel;
use GDW\AsyncIndex\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(JobModel::class, JobResource::class);
    }
}
