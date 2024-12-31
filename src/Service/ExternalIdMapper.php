<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ExternalIdMapper
{
    public function __construct(private EntityRepository $productRepository, private EntityRepository $customerRepository)
    {
        
    }

    public function mapProductIds(array $productIds)
    {
        $products = $this->productRepository->search(
            new Criteria($productIds),
            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        $mappedProducts = [];

        /** @var ProductEntity $product */
        foreach($products->getElements() as $product){
            $mappedProducts[$product->getUniqueIdentifier()] = $product->getProductNumber();
        }

        return $mappedProducts;        
    }

    public function mapCustomerId(string $customerIds)
    {
        $customers = $this->customerRepository->search(
            new Criteria([$customerIds]),
            \Shopware\Core\Framework\Context::createDefaultContext()
        );

        $mappedCustomers = [];

        /** @var CustomerEntity $customer */
        foreach($customers->getElements() as $customer){
            $mappedCustomers[$customer->getUniqueIdentifier()] = $customer->getCustomerNumber();
        }

        return $mappedCustomers;        
    }

}
