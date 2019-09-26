<?php
/**
 * @package  Divante\PimcoreIntegration
 * @author Bartosz Herba <bherba@divante.pl>
 * @copyright 2018 Divante Sp. z o.o.
 * @license See LICENSE_DIVANTE.txt for license details.
 */

namespace Divante\PimcoreIntegration\Listeners;

use Magento\Framework\Event\Observer;

/**
 * Class RelatedProductsLinkerListener
 */
class RelatedProductsLinkerListener extends AbstractLinkerListener
{

    const PIMCORE_FIELDNAME_RELATED = 'related_products';
    const MAGENTO_PRODUCT_LINKTYPE_RELATED = 'related';


    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $pimcoreProduct = $observer->getData('pimcore');
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getData('product');

        $this->setNewProductLinks($pimcoreProduct, $product,
            self::MAGENTO_PRODUCT_LINKTYPE_RELATED,
            self::PIMCORE_FIELDNAME_RELATED);
    }

}
