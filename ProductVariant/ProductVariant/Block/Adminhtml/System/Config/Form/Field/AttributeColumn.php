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
        $options = [
            // Standard columns that don't directly map to product attributes or need special handling
            ['value' => 'item_code', 'label' => __('[Base] Item Code (SKU)')],
            ['value' => 'moq_price', 'label' => __('[Base] MOQ Price / Unit Price')],
            ['value' => 'carton_qty', 'label' => __('[Base] Carton Qty')],
            ['value' => 'carton_price', 'label' => __('[Base] Carton Price/PC')],
            ['value' => 'min_qty', 'label' => __('[Base] Min Qty (MOQ)')],
            ['value' => 'qty', 'label' => __('[Base] Qty Input')],
            ['value' => 'subtotal', 'label' => __('[Base] Subtotal')],
        ];

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
