<?php
namespace GDW\AsyncIndex\Model;

class JobFactory
{
    public function __construct(private readonly \Magento\Framework\ObjectManagerInterface $objectManager)
    {
    }

    public function create(array $data = []): Job
    {
        return $this->objectManager->create(Job::class, ['data' => $data]);
    }
}
