<?php

namespace MageSuite\BrandManagement\Model;

class BrandsRepository implements \MageSuite\BrandManagement\Api\BrandsRepositoryInterface
{
    protected array $allBrands = [];

    /**
     * @var \MageSuite\BrandManagement\Model\ResourceModel\Brands
     */
    protected $brandsResource;

    /**
     * @var \MageSuite\BrandManagement\Model\BrandsFactory
     */
    protected $brandsFactory;

    /**
     * @var \MageSuite\BrandManagement\Model\ResourceModel\Brands\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \MageSuite\BrandManagement\Model\Brands[]
     */
    protected $instances = [];

    /**
     * @var \MageSuite\BrandManagement\Model\Brands\Processor\SaveFactory
     */
    protected $saveFactory;

    /**
     * @var \MageSuite\BrandManagement\Validator\BrandParams
     */
    protected $brandParamsValidator;

    /**
     * @var \MageSuite\BrandManagement\Model\Brands\Processor\UploadFactory
     */
    protected $uploadFactory;

    protected array $brandAttributes = [];

    public function __construct(
        \MageSuite\BrandManagement\Model\ResourceModel\Brands $brandsResource,
        \MageSuite\BrandManagement\Model\BrandsFactory $brandsFactory,
        \MageSuite\BrandManagement\Model\ResourceModel\Brands\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MageSuite\BrandManagement\Model\Brands\Processor\SaveFactory $saveFactory,
        \MageSuite\BrandManagement\Validator\BrandParams $brandParamsValidator,
        \MageSuite\BrandManagement\Model\Brands\Processor\UploadFactory $uploadFactory,
        array $brandAttributes = []
    ) {
        $this->brandsFactory = $brandsFactory;
        $this->brandsResource = $brandsResource;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->saveFactory = $saveFactory;
        $this->brandParamsValidator = $brandParamsValidator;
        $this->uploadFactory = $uploadFactory;
        $this->brandAttributes = $brandAttributes;
    }

    public function getById($id, $storeId = null)
    {
        if ($storeId && isset($this->allBrands[$storeId][$id])) {
            return $this->allBrands[$storeId][$id];
        }

        /** @var Brands $brand */
        $brand = $this->brandsFactory->create();
        if (null !== $storeId) {
            $brand->setData('store_id', $storeId);
        }

        $brand->getResource()->setDefaultStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $brand->load($id);

        if (!$brand->getEntityId()) {
            return null;
        }

        return $brand;
    }

    public function save(\MageSuite\BrandManagement\Api\Data\BrandsInterface $brand)
    {
        try {
            $isExists = ($this->getById($brand['entity_id'])) ? true : false;
            if (!$isExists) {
                $this->brandsResource->save($brand);
            }

            $attributesToRemove = $this->brandAttributes;
            foreach ($brand->getData() as $key => $value) {
                $attr = $this->brandsResource->getAttribute($key);
                $attributeIndex = array_search($key, $attributesToRemove);

                if (false !== $attributeIndex) {
                    unset($attributesToRemove[$attributeIndex]);
                }

                if (!$attr) {
                    continue;
                }

                $this->brandsResource->updateAttribute($brand, $attr, $value, $brand->getStoreId());
            }

            $this->brandsResource->removeAttribute($brand, $attributesToRemove);
            $brand->afterSave();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __(
                    'Could not save brand: %1',
                    $e->getMessage()
                ),
                $e
            );
        }
        return $brand;
    }

    public function delete(\MageSuite\BrandManagement\Api\Data\BrandsInterface $brand)
    {
        $brandFactory = $this->brandsFactory->create();
        $brandFactory->setId($brand->getEntityId());

        try {
            $this->brandsResource->delete($brandFactory);
            return true;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __(
                    'Could not delete brand: %1',
                    $e->getMessage()
                ),
                $e
            );
        }
    }

    public function getAllBrands($storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        if (isset($this->allBrands[$storeId])) {
            return $this->allBrands[$storeId];
        }

        $brandCollection = $this->collectionFactory->create();
        $brandCollection->setStoreId($storeId);
        $brandCollection->addSortByName();
        $brandCollection->addAttributeToSelect('*');

        $brandDataArray = [];
        foreach ($brandCollection as $brand) {
            $brandDataArray[$brand->getEntityId()] = $brand;
            $this->allBrands[$storeId][$brand->getEntityId()] = $brand;
        }

        return $brandDataArray;
    }

    public function create(\MageSuite\BrandManagement\Api\Data\BrandsInterface $brand)
    {
        try {
            $brand['is_api'] = true;
            $uploader = $this->uploadFactory->create();

            if ($brand->getBrandIconEncodedData()) {
                $brand->setBrandIcon($uploader->processUpload($brand->getBrandIconEncodedData()));
            }

            if ($brand->getBrandAdditionalIconEncodedData()) {
                $brand->setBrandAdditionalIcon($uploader->processUpload($brand->getBrandAdditionalIconEncodedData()));
            }

            $this->brandParamsValidator->validateParams($brand);

            $brand = $this->saveFactory->create()->processSave($brand);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Could not save brand.', $e->getMessage()), $e);
        }
        return $brand;
    }

    public function update(\MageSuite\BrandManagement\Api\Data\BrandsInterface $brand)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $brandEntity = $this->getById($brand->getEntityId(), $storeId);
        $brandEntity->addData($brand->getData());

        return $this->create($brandEntity);
    }

    public function deleteById($id)
    {
        $brand = $this->getById($id);
        $this->delete($brand);
    }

    public function getBrandByUrlKey($brandUrlKey, $storeId)
    {
        return $this->getBrandByAttributeValue('brand_url_key', $brandUrlKey, $storeId);
    }

    public function getBrandByName($brandName, $storeId)
    {
        return $this->getBrandByAttributeValue('brand_name', $brandName, $storeId);
    }

    private function getBrandByAttributeValue($attributeCode, $attributeValue, $storeId)
    {
        if ($storeId == null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $brandCollection = $this->collectionFactory->create();
        $brandCollection->setStoreId($storeId);
        $brandCollection->addAttributeToSelect('*');
        $brandCollection->addAttributeToFilter($attributeCode, ['eq' => $attributeValue]);

        if (empty($brandCollection->getItems())) {
            return null;
        }

        return $brandCollection->getFirstItem();
    }
}
