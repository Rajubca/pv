<?php
namespace Shatchi\ProductVariant\Model\Config\Structure\Element\Group;

use Magento\Config\Model\Config\Structure\Element\Group\CloneModel;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;

class AttributeSetClone extends CloneModel
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function getPrefixes()
    {
        $prefixes = [
            [
                'field' => '0',
                'label' => __('-- Global / All Attribute Sets --')
            ]
        ];

        // Entity type ID 4 is for catalog_product
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('entity_type_id', 4)
            ->setOrder('attribute_set_name', 'ASC');

        foreach ($collection as $item) {
            $prefixes[] = [
                'field' => $item->getId(),
                'label' => $item->getAttributeSetName()
            ];
        }

        return $prefixes;
    }
}
