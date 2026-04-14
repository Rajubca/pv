<?php
namespace Shatchi\ProductVariant\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;

class AttributeSetColumn extends Select
{
    /**
     * @var AttributeSetCollectionFactory
     */
    protected $attributeSetCollectionFactory;

    public function __construct(
        Context $context,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function setInputId($value)
    {
        return $this->setId($value);
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        // Mark it with a special class for our JS logic
        $this->setClass('shatchi-attr-set-dropdown');
        return parent::_toHtml();
    }

    private function getSourceOptions()
    {
        $options = [
            ['value' => '0', 'label' => __('-- Global / All Attribute Sets --')]
        ];

        $collection = $this->attributeSetCollectionFactory->create()
            ->addFieldToFilter('entity_type_id', 4)
            ->setOrder('attribute_set_name', 'ASC');

        foreach ($collection as $item) {
            $options[] = [
                'value' => $item->getId(),
                'label' => $item->getAttributeSetName()
            ];
        }

        return $options;
    }
}
