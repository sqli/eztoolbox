<?php
declare(strict_types=1);

namespace SQLI\EzToolboxBundle\Form\Type;


use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Core\FieldType\Value;
use SQLI\EzToolboxBundle\Entity\Doctrine\GroupMail;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
class SelectionFromEntity extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldSettings = $options['data'];
        $builder->add(
            'selection',
           // EntityType::class,
            ChoiceType::class,
            [
             //  'class' => $fieldSettings['className'],
                'choices'=>$this->entityManager->getRepository($fieldSettings['className'])->findBy([]),
                'choice_value' => $fieldSettings['valueAttribute'],
               'choice_label' => $fieldSettings['labelAttribute'],
                'multiple' => true,
            ]
        );
//        dd();
        $builder->get('selection')
            ->addModelTransformer(new CallbackTransformer(
                function ($groups) use ($fieldSettings): array {
                //dd
                    $result = [];

                    if (null === $groups) {
                        return $result;
                    }
//
//                    $ids = array_map(function ($group) {
//                        return $group['id'];
//                    }, $groups);
                    return $this->entityManager->getRepository($fieldSettings['className'])
                        ->findBy([]);

                },
                function ($group): Value {
                   return $group;
          ;
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {

        $resolver->setDefaults([
//       'data_class' => null,
         'data_class' => Value::class,

        ]);
    }

}