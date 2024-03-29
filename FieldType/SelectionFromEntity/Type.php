<?php
declare(strict_types=1);

namespace  SQLI\EzToolboxBundle\FieldType\SelectionFromEntity;



use Ibexa\AdminUi\Form\Data\FieldDefinitionData;
use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Ibexa\Contracts\ContentForms\FieldType\FieldValueFormMapperInterface;
use Ibexa\Contracts\Core\FieldType\Generic\Type as GenericType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use SQLI\EzToolboxBundle\Form\Type\SelectionFromEntity;
use SQLI\EzToolboxBundle\Form\Type\SelectionFromEntitySettingsType;
use Symfony\Component\Form\FormInterface;
use Ibexa\AdminUi\FieldType\FieldDefinitionFormMapperInterface;
use Ibexa\Contracts\Core\FieldType\Indexable;


//final class Type extends GenericType implements FieldValueFormMapperInterface, FieldDefinitionFormMapperInterface, Indexable
//{
//
//    public function getFieldTypeIdentifier(): string
//    {
//        return 'selection_from_entity';
//    }
//
//    public function getSettingsSchema(): array
//    {
//
//        return [
//            'className' => [
//                'type' => 'string',
//                'default' => 'App\Entity\Doctrine',
//            ],
//            'labelAttribute' => [
//                'type' => 'string',
//            ],
//            'valueAttribute' => [
//                'type' => 'string',
//            ],
//            'orderResult' => [
//                'type' => 'boolean',
//            ]
//        ];
//
//    }
//
//    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data)
//    {
//
//        $definition = $data->fieldDefinition;
//        $fieldForm->add('value', SelectionFromEntity::class, [
//            'required' => $definition->isRequired,
//            'label' => $definition->getName(),
//            'data' => $data->fieldDefinition->getFieldSettings(),
//
//        ]);
//
//    }
final class Type extends GenericType implements FieldValueFormMapperInterface, FieldDefinitionFormMapperInterface,Indexable
{
    public function getFieldTypeIdentifier(): string
    {
        return 'selection_from_entity';
    }
    public function getSettingsSchema(): array
    {

        return [
            'className' => [
                'type' => 'string',
                'default' => 'App\Entity\Doctrine',
            ],
            'labelAttribute' => [
                'type' => 'string',
            ],
            'valueAttribute' => [
                'type' => 'string',
            ]
//            'orderResult' => [
//                'type' => 'boolean',
//            ]
        ];

    }
    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data)
    {
        $definition = $data->fieldDefinition;

        $fieldForm->add(
            'value',
            SelectionFromEntity::class,
            [
                'required' => $definition->isRequired,
                'label' => $definition->getName(),
                'data' => $data->fieldDefinition->getFieldSettings(),
            ]
        );
    }
    public function mapFieldDefinitionForm(FormInterface $fieldDefinitionForm, FieldDefinitionData $data): void
    {

        $fieldDefinitionForm->add('fieldSettings', SelectionFromEntitySettingsType::class, [
            'label' => false,
        ]);
    }

    public function getIndexData(Field $field, FieldDefinition $fieldDefinition)
    {
        return [];
    }

    public function getIndexDefinition()
    {
        return [];
    }

    public function getDefaultMatchField()
    {
        return null;
    }

    public function getDefaultSortField()
    {
        return null;
    }
}
