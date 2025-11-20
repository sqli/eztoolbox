<?php

namespace SQLI\EzToolboxBundle\Services\Twig;

use SQLI\EzToolboxBundle\Services\EntityHelper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntityExtension extends AbstractExtension
{
    /** @var ParameterBagInterface */
    protected $parameterBag;
    /** @var EntityHelper */
    private $entityHelper;

    public function __construct(ParameterBagInterface $parameterBag, EntityHelper $entityHelper)
    {
        $this->parameterBag = $parameterBag;
        $this->entityHelper = $entityHelper;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'sqli_admin_attribute',
                [
                    $this,
                    'attributeValue'
                ],
                array('is_safe' => ['all'])
            ),
            new TwigFunction(
                'bundle_exists',
                [
                    $this,
                    'bundleExists'
                ]
            ),
        ];
    }

    /**
     * Get value of a property
     *
     * @param $object
     * @param $property_name
     * @return false|string
     */
    public function attributeValue($object, $property_name)
    {
        try {
            return $this->entityHelper->attributeValue($object, $property_name);
        } catch (\ErrorException $exception) {
            // If property instance of an object which not implements a __toString method it will display an error
            return "<span title='{$exception->getMessage()}' class='alert alert-danger'>ERROR</span>";
        }
    }

    /**
     * Check if a bundle is declared
     *
     * @param $bundleName
     * @return bool
     */
    public function bundleExists($bundleName): bool
    {
        return array_key_exists($bundleName, $this->parameterBag->get('kernel.bundles'));
    }
}
