<?php
namespace Shatchi\ProductVariant\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class AttributeColumn extends Select
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @param Context $context
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributeCollectionFactory $attributeCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributeCollectionFactory = $attributeCollectionFactory;
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
        // Removed base columns so they cannot be selected from the dynamic columns list
        $options = [];

        // Fetch catalog attributes
        $collection = $this->attributeCollectionFactory->create()
            ->addVisibleFilter()
            ->setOrder('frontend_label', 'ASC');

        $attributeOptions = [];
        foreach ($collection as $item) {
            $code = $item->getAttributeCode();
            $label = $item->getFrontendLabel();

            if ($label && $code) {
                $attributeOptions[] = [
                    'value' => 'attr_' . $code,
                    'label' => '[Attribute] ' . $label . ' (' . $code . ')'
                ];
            }
        }

        return array_merge($options, $attributeOptions);
    }
}
