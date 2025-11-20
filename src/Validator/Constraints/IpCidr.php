<?php

namespace SQLI\EzToolboxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class IpCidr extends Constraint
{
    public const INVALID_MASK = '9d459b65f2090feb1c9c89652e18d43a';
    public const INVALID_IP = '2828df84b112c4bb456ef64e2a5bfefb';
    public const NOT_IN_MASK = 'f96ade9a9e82b6253d680b39518f9f16';
    protected static $errorNames = [
        self::INVALID_MASK => 'INVALID_MASK',
        self::INVALID_IP => 'INVALID_IP',
        self::NOT_IN_MASK => 'NOT_IN_MASK',
    ];
    public $message = "{{ value }} not validated with CIDR mask {{ cidr }}";
    public $cidr;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    /**
     * Returns the name of the default option.
     * Override this method to define a default option.
     *
     * @return string|null
     * @see __construct()
     */
    public function getDefaultOption(): ?string
    {
        return 'cidr';
    }

    /**
     * Returns the name of the required options.
     * Override this method if you want to define required options.
     *
     * @return array
     * @see __construct()
     */
    public function getRequiredOptions(): array
    {
        return ['cidr'];
    }
}
