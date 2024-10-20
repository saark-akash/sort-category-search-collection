<?php
declare(strict_types=1);

namespace Akash\SearchSortAlgo\Plugin\Model\ResourceModel\Fulltext\Collection;

use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplier;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection;

/**
 * Class SearchResultApplierPlugin marking apply flag
 */
class SearchResultApplierPlugin extends SearchResultApplier
{
    /**
     * @var Collection|\Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection
     */
    private $collection;

    /**
     * @var SearchResultInterface
     */
    private $searchResult;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var $currHelper
     * */
    protected $currHelper;
    /**
     * @param Collection $collection
     * @param SearchResultInterface $searchResult
     * @param int $size
     * @param int $currentPage
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Akash\SearchSortAlgo\Helper\Data $currHelper
     */
    public function __construct(
        Collection $collection,
        SearchResultInterface $searchResult,
        int $size,
        int $currentPage,
        \Magento\Framework\App\RequestInterface $request,
        \Akash\SearchSortAlgo\Helper\Data $currHelper
    ) {
        parent::__construct(
            $collection,
            $searchResult,
            $size,
            $currentPage
        );
        $this->currHelper = $currHelper;
        $this->request = $request;
        $this->collection = $collection;
        $this->searchResult = $searchResult;
        $this->size = $size;
        $this->currentPage = $currentPage;
    }

    /**
     * Get post values
     * 
     * */
    public function getPost()
    {
        return $this->request->getParams();
    }
    /**
     * Primary apply function from elastic search
     * @return void
     */
    public function mainApply(): void
    {
        if (empty($this->searchResult->getItems())) {
            $this->collection->getSelect()->where('NULL');
            return;
        }

        $ids = [];
        $items = $this->sliceItems($this->searchResult->getItems(), $this->size, $this->currentPage);
        foreach ($items as $item) {
            $ids[] = (int)$item->getId();
        }

        $orderList = implode(',', $ids);
        
        $this->collection->getSelect()->where('e.entity_id IN (?)', $ids);
        $this->collection->getSelect()->reset(\Magento\Framework\DB\Select::ORDER)
            ->order(new \Zend_Db_Expr("FIELD(e.entity_id,$orderList)"));
    }
    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        if ($this->currHelper->getRouteName() == "catalogsearch" || $this->currHelper->getRouteName() == "catalog") {
            $page = 1;
            $params = $this->getPost();
            if (isset($params['p'])) {
                $page = (int) $params['p'];
            }
            $pageSize = $this->currHelper->getProductPerPageDefault();

            if (empty($this->searchResult->getItems())) {
                $this->collection->getSelect()->where('NULL');
                return;
            }

            $ids = [];
            $items = $this->sliceItems($this->searchResult->getItems(), $this->size, $this->currentPage);
            foreach ($items as $item) {
                $ids[] = (int) $item->getId();
            }

            $this->collection->addAttributeToFilter("name", [
                ['nlike' => ''],
                ['null' => true]
            ]);

            $this->collection->addAttributeToFilter("price", [
                ['gteq' => 0],
                ['null' => true]
            ]);

            $this->collection->getSelect()->order(array(
                'NULLIF(at_name_default.value LIKE "Configurable Product%", at_name_default.value)DESC'
            ));

            $this->sortSearchCollection($this->collection, $params);

            $this->collection->getSelect()->where('e.entity_id IN (?)', $ids);
            
            $this->collection->getSelect()->limitPage($page, $pageSize);
        } else {
            $this->mainApply();
        }
    }
    /**
     * Sort search collection
     * 
     * @param object $collection
     * @param array $params
     * 
     * */
    public function sortSearchCollection($collection, $params)
    {
        $dir = isset($params['product_list_dir']) ? "DESC" : "ASC";
        if ($dir == 'ASC' || $dir == 'asc') {
            $dir = 'ASC';
        }
        if ($dir == 'DESC' || $dir == 'desc') {
            $dir = 'DESC';
        }
        if (isset($params['product_list_order']) && $params['product_list_order'] == 'price') {
            $collection->getSelect()->order(
                array('final_price ' . $dir)
            );
        } elseif (isset($params['product_list_order']) && ($params['product_list_order'] == 'position')) {
            // Write code of your choice
        } else {
            $collection->getSelect()->order(
                array('name ' . $dir)
            );
        }
        return $collection;
    }
    /**
     * Slice current items
     *
     * @param array $items
     * @param int $size
     * @param int $currentPage
     * @return array
     */
    private function sliceItems(array $items, int $size, int $currentPage): array
    {
        if ($size !== 0) {
            // Check that current page is in a range of allowed page numbers, based on items count and items per page,
            // than calculate offset for slicing items array.
            $itemsCount = count($items);
            $maxAllowedPageNumber = ceil($itemsCount / $size);
            if ($currentPage < 1) {
                $currentPage = 1;
            }
            if ($currentPage > $maxAllowedPageNumber) {
                $currentPage = $maxAllowedPageNumber;
            }

            $offset = $this->getOffset((int) $currentPage, $size);
            $items = array_slice($items, $offset, $size);
        }

        return $items;
    }

    /**
     * Get offset for given page.
     *
     * @param int $pageNumber
     * @param int $pageSize
     * @return int
     */
    private function getOffset(int $pageNumber, int $pageSize): int
    {
        return ($pageNumber - 1) * $pageSize;
    }
}

