<?php
namespace Perspective\MultisearchIo\Model\Autocomplete\Response;

/**
 * Factory class for @see \Perspective\MultisearchIo\Model\Autocomplete\Response\QueryResponse
 */
class QueryResponseFactory extends \Magento\Framework\Search\Response\QueryResponseFactory
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
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Perspective\\MultisearchIo\\Model\\Autocomplete\\Response\\QueryResponse')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Perspective\MultisearchIo\Model\Autocomplete\Response\QueryResponse
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
