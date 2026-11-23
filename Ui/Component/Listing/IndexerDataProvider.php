<?php
namespace GDW\AsyncIndex\Ui\Component\Listing;

use GDW\AsyncIndex\Model\JobManager;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DataObject;
use Magento\Ui\DataProvider\AbstractDataProvider;

class IndexerDataProvider extends AbstractDataProvider
{
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        JobManager $jobManager,
        EntityFactory $entityFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = new Collection($entityFactory);

        foreach ($jobManager->getIndexerData() as $indexer) {
            $item = new DataObject($indexer);
            $item->setId($indexer['id']);
            $this->collection->addItem($item);
        }

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
}
