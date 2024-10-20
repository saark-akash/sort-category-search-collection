<?php
declare(strict_types=1);

namespace Akash\SearchSortAlgo\Plugin\Model\ResourceModel\Product;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;

/**
 * Class CollectionPlugin applying sort order
 */
class CollectionPlugin
{
    /**
	 * @var $request
	 * */
	protected $request;
    /**
     * Data constructor
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
    ) {
        $this->request = $request;
    }
    /**
     * Get route name
     * */
    public function getRouteName()
	{
		return $this->request->getRouteName();
	}
    /**
     * After plugin on get page size
     * 
     * @param Collection $subject
     * @param int $result
     * 
     * */
    public function afterGetPageSize(
        Collection $subject,
        $result
    )
    {
        if ($this->getRouteName() == "catalogsearch" || $this->getRouteName() == "catalog") {
    		if ($result != null && $result > 0){
                return false;
            }
    	}
        return $result;
    }
}
