<?php
namespace Perspective\MultisearchIo\Model\Search\SearchEngine;

/**
 * Factory class for @see \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter
 */
class AdapterFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     * @SuppressWarnings("php:S116")
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     * @SuppressWarnings("php:S116")
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Perspective\\MultisearchIo\\Model\\Search\\SearchEngine\\Adapter')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Perspective\MultisearchIo\Model\Search\SearchEngine\Adapter
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
