<?php
$brandRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('MageSuite\BrandManagement\Api\BrandsRepositoryInterface');

$brand = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('MageSuite\BrandManagement\Model\Brands');
$brand
    ->setEntityId(600)
    ->setStoreId(1)
    ->setUrlKey('urlkey')
    ->setBrandName('test_brand_name')
    ->setEnabled(1)
    ->setIsFeatured(1)
    ->setBrandIcon('testimage.png')
    ->setBrandAdditionalIcon('testimage_additional.png')
    ->setShowInBrandCarousel(0)
    ->setShortDescription('short description')
    ->setFullDescription('full description')
    ->setMetaTitle('Test meta title')
    ->setMetaDescription('Test meta description')
    ->setMetaRobots('NOINDEX,NOFOLLOW');

$brandRepository->save($brand);

$brand = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('MageSuite\BrandManagement\Model\Brands');
$brand
    ->setEntityId(700)
    ->setStoreId(0)
    ->setUrlKey('urlkey2')
    ->setBrandName('test_brand_name_2')
    ->setEnabled(1)
    ->setIsFeatured(1)
    ->setBrandIcon('testimage.png')
    ->setBrandAdditionalIcon('testimage_additional.png')
    ->setShowInBrandCarousel(0)
    ->setShortDescription('short description 2')
    ->setFullDescription('full description 2')
    ->setMetaTitle('Test meta title 2')
    ->setMetaDescription('Test meta description 2')
    ->setMetaRobots('INDEX,FOLLOW');

$brandRepository->save($brand);

$brand = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('MageSuite\BrandManagement\Model\Brands');
$brand
    ->setEntityId(800)
    ->setStoreId(0)
    ->setUrlKey('urlkey3')
    ->setBrandName('é_test_brand_name_with_special_char_as_first_letter')
    ->setEnabled(1)
    ->setIsFeatured(1)
    ->setBrandIcon('testimage.png')
    ->setBrandAdditionalIcon('testimage_additional.png')
    ->setShowInBrandCarousel(0)
    ->setShortDescription('short description 3')
    ->setFullDescription('full description 3')
    ->setMetaTitle('Test meta title 3')
    ->setMetaDescription('Test meta description 3')
    ->setMetaRobots('INDEX,FOLLOW');

$brandRepository->save($brand);
