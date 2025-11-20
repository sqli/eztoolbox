<?php

declare(strict_types=1);

namespace  SQLI\EzToolboxBundle\FieldType\SqliToolbox;

use SQLI\EzToolboxBundle\Form\Type\SqliToolboxType;
use Ibexa\Contracts\Core\FieldType\Generic\Type as GenericType;
use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Ibexa\Contracts\ContentForms\FieldType\FieldValueFormMapperInterface;
use Symfony\Component\Form\FormInterface;

class Type extends GenericType implements FieldValueFormMapperInterface, Indexable
{
    public function getFieldTypeIdentifier(): string
    {
        return 'sqlitoolbox';
    }

    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data)
    {
        $definition = $data->fieldDefinition;
        $fieldForm->add(
            'value',
            SqliToolboxType::class,
            [
                'required' => $definition->isRequired,
                'label' => $definition->getName()
            ]
        );
    }


    /**
     * Get index data for field for search backend.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDefinition
     *
     * @return \Ibexa\SPI\Search\Field[]
     */
    public function getIndexData(Field $field, FieldDefinition $fieldDefinition)
    {
        return [];
    }

    /**
     * Get index field types for search backend.
     *
     * @return \Ibexa\SPI\Search\FieldType[]
     */
    public function getIndexDefinition()
    {
        return [];
    }

    /**
     * Get name of the default field to be used for matching.
     *
     * As field types can index multiple fields (see MapLocation field type's
     * implementation of this interface), this method is used to define default
     * field for matching. Default field is typically used by Field criterion.
     *
     * @return string
     */
    public function getDefaultMatchField()
    {
        return null;
    }

    /**
     * Get name of the default field to be used for sorting.
     *
     * As field types can index multiple fields (see MapLocation field type's
     * implementation of this interface), this method is used to define default
     * field for sorting. Default field is typically used by Field sort clause.
     *
     * @return string
     */
    public function getDefaultSortField()
    {
        return null;
    }
}