<?php

declare(strict_types=1);

namespace MageSuite\BrandManagement\Block;

class All extends \Magento\Framework\View\Element\Template
{
    protected \MageSuite\BrandManagement\Model\BrandsRepository $brandsRepository;
    protected ?array $brands = null;

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
        if ($this->brands !== null) {
            return $this->brands;
        }

        $this->brands = [];
        $allBrands = $this->brandsRepository->getAllBrands();

        if (empty($allBrands)) {
            return $this->brands;
        }

        foreach ($allBrands as $brand) {
            if (!$brand->getEnabled() || empty($brand->getBrandUrl())) {
                continue;
            }

            $this->brands[] = $brand;
        }

        return $this->brands;
    }

    public function getBrandsGroupedByFirstLetter(): array
    {
        $brandsByFirstLetter = [];

        /** @var \MageSuite\BrandManagement\Api\Data\BrandsInterface $brand */
        foreach ($this->getAllBrands() as $brand) {
            $firstLetter = $this->getFirstLetter($brand->getBrandName());

            if (!isset($brandsByFirstLetter[$firstLetter])) {
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
