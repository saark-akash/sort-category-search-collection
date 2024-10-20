<?php
namespace Akash\SearchSortAlgo\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const PRODUCT_PER_PAGE_GRID_VAULE_DEFAULT = 'catalog/frontend/grid_per_page';
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
	 * @var $request
	 * */
	protected $request;
    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }
    /**
     * Get system configuration
     * @param string $configPath
     * @return mixed||string
     */
    public function getConfigurationValue($configPath, $storeId = null){
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    /**
     * Get product per page default
     * 
     * @return int
     * */
    public function getProductPerPageDefault()
    {
        $configPath = self::PRODUCT_PER_PAGE_GRID_VAULE_DEFAULT;
        return $this->getConfigurationValue($configPath, null);
    }
    /**
     * Get route name
     * */
    public function getRouteName()
	{
		return $this->request->getRouteName();
	}
}