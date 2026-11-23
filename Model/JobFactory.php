<?php
namespace GDW\AsyncIndex\Model;

class JobFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Job
    {
        return $this->objectManager->create(Job::class, ['data' => $data]);
    }
}
