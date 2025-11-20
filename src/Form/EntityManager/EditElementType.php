<?php

namespace SQLI\EzToolboxBundle\Form\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditElementType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $element = $options['entity'];
        foreach ($element['class']['properties'] as $propertyName => $propertyInfos) {
            // If property can be visible, add it to formbuilder
            if ($propertyInfos['visible']) {
                // FormType parameters
                $params = [];

                if ($propertyInfos['readonly']) {
                    // Readonly attribute for this attribute
                    $params['attr']['readonly'] = true;
                    $params['attr']['class'] = 'bg-transparent';
                }

                // Is a required field ?
                $params['required'] = $propertyInfos['required'];

                // Add attribute step=any if it's a float field
                switch ($propertyInfos['type']) {
                    case "decimal":
                    case "float":
                        $params['attr']['step'] = 'any';
                        break;
                }

                // If a description defined for property, add it in 'title' attribute of field
                if (!empty($propertyInfos['description'])) {
                    $params['attr']['title'] = $propertyInfos['description'];
                }

                $formType = null;
                if (is_array($propertyInfos['choices']) && !empty($propertyInfos['choices'])) {
                    $formType = ChoiceType::class;
                    $params['choices'] = $propertyInfos['choices'];
                } elseif ($propertyInfos['type'] === "object" || $propertyInfos['type'] === "array") {
                    $formType = TextareaType::class;
                    $params['invalid_message'] = $this->translator->trans(
                        'form.edit.type_object.invalid',
                        [],
                        'forms'
                    );
                }

                // If context is defined as view, readonly parameter is added
                if ($options['context'] == 'view') {
                    $params['attr']['readonly'] = true;
                }

                // Add field on Form
                $builder->add($propertyName, $formType, $params);

                // Support display of objects and arrays : serialize them before display
                if ($propertyInfos['type'] === "object" || $propertyInfos['type'] === "array") {
                    $builder->get($propertyName)->addViewTransformer(new CallbackTransformer(
                        function ($toSerialize) {
                            return serialize($toSerialize);
                        },
                        function ($toUnserialize) {
                            try {
                                return unserialize($toUnserialize);
                            } catch (\Exception $e) {
                                throw new TransformationFailedException();
                            }
                        }
                    ));
                }
            }
        }

        // Add submit button if context is defined as edit
        if ($options['context'] == 'edit') {
            $builder
                ->add(
                    'submit',
                    SubmitType::class,
                    [
                        'label' => 'form.button.label.submit',
                        'translation_domain' => 'forms',
                        'attr' => ['class' => 'd-none'],
                    ]
                );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('entity');
        $resolver->setDefault('context', 'edit');
    }
}
