<?php

namespace SQLI\EzToolboxBundle\Form\EntityManager;

use SQLI\EzToolboxBundle\Classes\Filter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilterType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $classInformations = $options['class_informations'];

        $builder->add(
            "column_name",
            ChoiceType::class,
            [
                'choices' => array_combine(
                    array_keys($classInformations['properties']),
                    array_keys($classInformations['properties'])
                ),
                'attr' =>
                    [
                        'class' => "form-control",
                    ],
            ]
        );

        $builder->add(
            "operand",
            ChoiceType::class,
            [
                'choices' => Filter::OPERANDS_MAPPING,
                'attr' =>
                    [
                        'class' => "form-control",
                    ],
            ]
        );
        $builder->add(
            "value",
            TextType::class,
            [
                'attr' =>
                    [
                        'class' => "form-control",
                        'placeholder' => $this->translator->trans(
                            "entity.field.placeholder.value",
                            [],
                            "sqli_admin"
                        )
                    ],
            ]
        );

        $builder->add(
            'filter',
            SubmitType::class,
            [
                "label" => $this->translator->trans(
                    "entity.button.label.filter",
                    [],
                    "sqli_admin"
                ),
                'attr' =>
                    [
                        'class' => "btn-primary btn",
                    ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('class_informations');
    }
}
