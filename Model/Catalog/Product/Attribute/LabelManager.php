<?php
/**
 * @package  Divante\PimcoreIntegration
 * @author Bartosz Herba <bherba@divante.pl>
 * @copyright 2018 Divante Sp. z o.o.
 * @license See LICENSE_DIVANTE.txt for license details.
 */

namespace Divante\PimcoreIntegration\Model\Catalog\Product\Attribute;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterfaceFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute\FrontendLabel;
use Magento\Eav\Model\Entity\Attribute\FrontendLabelFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ConfigFactory as EavConfigFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

/**
 * Class LabelManager
 */
class LabelManager
{
    /**
     * @var AttributeRepositoryInterfaceFactory
     */
    private $attrRepositoryFactory;

    /**
     * @var Config|EavConfigFactory
     */
    private $configFactory;

    /**
     * @var ResourceConnection
     */
    private $resource;


    private $frontendLabelFactory;

    /**
     * LabelManager constructor.
     *
     * @param AttributeRepositoryInterfaceFactory $attrRepositoryFactory
     * @param EavConfigFactory $configFactory
     * @param ResourceConnection $resource
     */
    public function __construct(
        AttributeRepositoryInterfaceFactory $attrRepositoryFactory,
        EavConfigFactory $configFactory,
        ResourceConnection $resource,
        FrontendLabelFactory $frontend_label_factory
    ) {
        $this->attrRepositoryFactory = $attrRepositoryFactory;
        $this->configFactory = $configFactory;
        $this->resource = $resource;
        $this->frontendLabelFactory = $frontend_label_factory;

    }

    /**
     * @param string $attrCode
     * @param array $labels
     *
     * @throws StateException
     *
     * @return void
     */
    public function saveLabelsForAttribute(string $attrCode, array $labels)
    {
        try {
            $attrRepository = $this->attrRepositoryFactory->create();

            /** @var Config $eavConfig */
            $eavConfig = $this->configFactory->create();
            /** @var AttributeInterface $attribute */
            $attr = $eavConfig->getAttribute(Product::ENTITY, $attrCode);
        } catch (LocalizedException $e) {
            return;
        }

        $frontEndLabels = $attr->getFrontendLabels();

        // if key === 0 the default admin/store is used
        if(key_exists(0, $labels) ){
            $newDefaultLabel = $labels[0];
            if($attr->getDefaultFrontendLabel() !== $newDefaultLabel){
                $attr->setDefaultFrontendLabel($newDefaultLabel);
                $attrRepository->save($attr);
            }
            return;
        }

        $currentLabels = $this->getStoreLabels($attr->getId());
        $labelsToSave = $currentLabels;

        foreach ($labels as $key => $label) {
            $labelsToSave[$key] = $label;
        }

        if (!$this->isLabelsChanged($currentLabels, $labelsToSave)) {
            return;
        }


        /** @var  $attr2 */
        $attr2 = $attrRepository->get(Product::ENTITY, $attrCode);
        $labelsX = $attr2->getFrontEndLabels();
        $newLabels = [];

        foreach ($labels as $key => $newLabelText){

            $isNew = true;
            foreach ($labelsX as $label){
                if ($key === $label->getStoreId()){
                    $label->setLabel($newLabelText);
                    $isNew = false;
                }
            }

            if ($isNew){
                $newLabels[$key] = $newLabelText;
            }
        }

        if (count($newLabels) > 0){
            foreach ($newLabels as $key => $newLabelText){
                /** @var FrontendLabel $newFrontEndLabel */
                $newFrontEndLabel = $this->frontendLabelFactory->create();
                $newFrontEndLabel->setStoreId($key);
                $newFrontEndLabel->setLabel($labels[$key]);

                $labelsX [] = $newFrontEndLabel;
            }
        }

        $attr2->setFrontendLabels($labelsX);
        $attrRepository->save($attr2);
    }

    /**
     * @param $currentLabels
     * @param $labelsToSave
     *
     * @return bool
     */
    private function isLabelsChanged($currentLabels, $labelsToSave): bool
    {
        return ($currentLabels !== $labelsToSave);
    }

    /**
     * @param int $attrId
     * @return array
     */
    private function getStoreLabels(int $attrId)
    {
        $connection = $this->resource->getConnection();
        $query = $connection->select()->from(
            $connection->getTableName('eav_attribute_label'),
            ['value', 'store_id']
        )->where("attribute_id = ?", $attrId);

        $result = $connection->fetchAll($query);
        $labels = [];
        foreach ($result as $labelData) {
            $labels[$labelData['store_id']] = $labelData['value'];
        }

        return $labels;
    }
}
