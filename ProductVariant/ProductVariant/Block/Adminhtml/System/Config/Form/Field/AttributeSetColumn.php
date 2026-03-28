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

    /**
     * @param Context $context
     * @param AttributeSetCollectionFactory $attributeSetCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions()
    {
        $options = [
            ['value' => '0', 'label' => __('-- All Attribute Sets --')]
        ];

        // Entity type ID 4 is typically for catalog_product
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
