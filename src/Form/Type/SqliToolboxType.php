<?php

declare(strict_types=1);

namespace SQLI\EzToolboxBundle\Form\Type;

use SQLI\EzToolboxBundle\FieldType\SqliToolbox\Value;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SqliToolboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('classname', TextType::class);
        $builder->add('pkkey', TextType::class);
        $builder->add('pkvalue', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Value::class
            ]
        );
    }

}