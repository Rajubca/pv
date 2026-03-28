<?php
namespace Shatchi\ProductVariant\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class GridColumns extends AbstractFieldArray
{
    /**
     * @var AttributeSetColumn
     */
    private $attributeSetRenderer;

    /**
     * @var AttributeColumn
     */
    private $attributeRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('attribute_set', [
            'label' => __('Attribute Set'),
            'renderer' => $this->getAttributeSetRenderer(),
        ]);

        $this->addColumn('column_code', [
            'label' => __('Column'),
            'renderer' => $this->getAttributeRenderer(),
        ]);

        $this->addColumn('custom_header', [
            'label' => __('Custom Header'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('sort_order', [
            'label' => __('Sort Order'),
            'class' => 'required-entry validate-number',
            'style' => 'width: 60px;',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Column');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $attributeSet = $row->getAttributeSet();
        if ($attributeSet !== null) {
            $options['option_' . $this->getAttributeSetRenderer()->calcOptionHash($attributeSet)] = 'selected="selected"';
        }

        $columnCode = $row->getColumnCode();
        if ($columnCode !== null) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($columnCode)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return AttributeSetColumn
     * @throws LocalizedException
     */
    private function getAttributeSetRenderer()
    {
        if (!$this->attributeSetRenderer) {
            $this->attributeSetRenderer = $this->getLayout()->createBlock(
                AttributeSetColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeSetRenderer;
    }

    /**
     * @return AttributeColumn
     * @throws LocalizedException
     */
    private function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                AttributeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributeRenderer;
    }
}
