<?php
declare(strict_types=1);

namespace MageSuite\BrandManagement\Block;

class All extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \MageSuite\BrandManagement\Model\BrandsRepository
     */
    protected $brandsRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageSuite\BrandManagement\Model\BrandsRepository $brandsRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->brandsRepository = $brandsRepository;
    }

    protected function _prepareLayout(): All
    {
        $result = parent::_prepareLayout();

        $title = __('All Brands');
        $this->pageConfig->getTitle()->set($title);

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'search',
                ['label' => $title, 'title' => $title]
            );
        }

        return $result;
    }

    public function getAllBrands(): array
    {
        $brands = $this->brandsRepository->getAllBrands();

        $data = [];

        if(empty($brands)) {
            return $data;
        }

        foreach($brands as $brand) {
            if(!$brand->getEnabled()) {
                continue;
            }

            if(empty($brand->getBrandUrl())) {
                continue;
            }

            $data[] = $brand;
        }

        return $data;
    }

    public function getBrandsGroupedByFirstLetter(): array
    {
        $brandsByFirstLetter = [];

        /** @var \MageSuite\BrandManagement\Api\Data\BrandsInterface $brand */
        foreach($this->getAllBrands() as $brand) {
            $firstLetter = $this->getFirstLetter($brand->getBrandName());

            if(!isset($brandsByFirstLetter[$firstLetter])) {
                $brandsByFirstLetter[$firstLetter] = [];
            }

            $brandsByFirstLetter[$firstLetter][] = $brand;
        }

        return $brandsByFirstLetter;
    }

    public function getFirstLetter(string $string): string
    {
        return strtolower(mb_substr($string, 0, 1, 'UTF-8'));
    }
}
