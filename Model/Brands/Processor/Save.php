<?php

declare(strict_types=1);

namespace MageSuite\BrandManagement\Model\Brands\Processor;

class Save
{
    protected \MageSuite\BrandManagement\Api\BrandsRepositoryInterface $brandsRepository;
    protected \MageSuite\BrandManagement\Model\BrandsFactory $brandsFactory;
    protected \Magento\Framework\Event\Manager $eventManager;
    protected \Magento\Framework\DataObjectFactory $dataObjectFactory;
    protected \MageSuite\BrandManagement\Model\ResourceModel\Brands $brandResource;
    protected \Magento\Framework\Filter\FilterManager $filter;
    protected \MageSuite\BrandManagement\Model\UrlVerifier $urlVerifier;

    public function __construct(
        \MageSuite\BrandManagement\Model\BrandsFactory $brandsFactory,
        \MageSuite\BrandManagement\Api\BrandsRepositoryInterface $brandsRepository,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \MageSuite\BrandManagement\Model\ResourceModel\Brands $brandResource,
        \Magento\Framework\Filter\FilterManager $filter,
        \MageSuite\BrandManagement\Model\UrlVerifier $urlVerifier
    ) {
        $this->brandsFactory = $brandsFactory;
        $this->brandsRepository = $brandsRepository;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->brandResource = $brandResource;
        $this->filter = $filter;
        $this->urlVerifier = $urlVerifier;
    }

    public function processSave($params): \MageSuite\BrandManagement\Api\Data\BrandsInterface
    {
        $originalParams = $params;

        $isNew = (!isset($params['entity_id'])) || ($params['entity_id'] == "");

        if (isset($params['brand_icon']) && is_array($params['brand_icon'])) {
            $params['brand_icon'] = $params['brand_icon'][0]['name'];
        }

        if (isset($params['brand_additional_icon']) && is_array($params['brand_additional_icon'])) {
            $params['brand_additional_icon'] = $params['brand_additional_icon'][0]['name'];
        }

        if ($isNew) {
            if (!isset($params['store_id'])) {
                $params['store_id'] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }

            $brand = $this->brandsFactory->create();
            $brand->setData($params->getData());
        } else {
            if (!$params['is_api']) {
                $params = $this->matchParams($params);
            }

            $brand = $this->brandsRepository->getById($params['entity_id'], $params['store_id']);
            $brand->setData($params->getData());
        }

        $this->validateParameters($brand);

        $urlKey = (string)$brand->getUrlKey();

        if ($urlKey && !$this->urlVerifier->isExternalUrl($urlKey) && substr($urlKey, 0, 1) !== '/') {
            $brand->setUrlKey($this->formatUrlKey($urlKey));
        }

        $this->eventManager->dispatch('brand_prepare_save', ['brand' => $brand, 'params' => $originalParams]);

        $brand = $this->brandsRepository->save($brand);

        return $brand;
    }

    public function formatUrlKey(string $str): string
    {
        return $this->filter->translitUrl($str);
    }

    protected function validateParameters($brand)
    {
        if ($this->brandResource->existsBrandWithSpecificAttributeValue('brand_name', $brand)) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Brand with %1 name already exist!', $brand->getBrandName()));
        }

        if ($this->brandResource->existsBrandWithSpecificAttributeValue('brand_url_key', $brand)) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Brand with %1 url_key already exist!', $brand->getBrandUrlKey()));
        }
    }

    protected function matchChangedFields($config)
    {
        $matchedFields = [];

        foreach ($config as $field => $value) {
            if ($value == 'false') {
                $matchedFields[] = $field;
            }
        }

        return $matchedFields;
    }

    protected function matchParams($params)
    {
        $changedFields = $this->matchChangedFields($params['use_config']);

        $matchedParams = [
            'entity_id' => $params['entity_id'],
            'store_id' => $params['store_id']
        ];

        foreach ($changedFields as $field) {
            if (!isset($params[$field])) {
                continue;
            }

            $matchedParams[$field] = $params[$field];
        }

        return $this->dataObjectFactory->create()->setData($matchedParams);
    }
}
