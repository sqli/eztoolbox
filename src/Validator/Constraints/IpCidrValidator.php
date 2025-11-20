<?php

namespace SQLI\EzToolboxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IpCidrValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IpCidr) {
            throw new UnexpectedTypeException($constraint, IpCidr::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->checkCidrMask($value, $constraint->cidr)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ cidr }}', $constraint->cidr)
                ->setCode(IpCidr::NOT_IN_MASK)
                ->setInvalidValue($value)
                ->addViolation();
        }
    }

    /**
     * Check if $iptocheck is in range defined by $CIDR
     * Ex: 192.168.0.1 in 192.168.0.0/24 => true
     * Ex: 192.168.1.1 in 192.168.0.0/24 => false
     *
     * @param string $iptocheck
     * @param string $CIDR
     * @return bool
     */
    private function checkCidrMask(string $iptocheck, string $CIDR): bool
    {
        /* get the base and the bits from the ban in the database */
        list($base, $bits) = explode('/', $CIDR);

        /* now split it up into it's classes */
        list($a, $b, $c, $d) = explode('.', $base);

        /* now do some bit shfiting/switching to convert to ints */
        $i = ($a << 24) + ($b << 16) + ($c << 8) + $d;
        $mask = $bits == 0 ? 0 : (~0 << (32 - $bits));

        /* here's our lowest int */
        $low = $i & $mask;

        /* here's our highest int */
        $high = $i | (~$mask & 0xFFFFFFFF);

        /* now split the ip were checking against up into classes */
        list($a, $b, $c, $d) = explode('.', $iptocheck);

        /* now convert the ip we're checking against to an int */
        $check = ($a << 24) + ($b << 16) + ($c << 8) + $d;

        /* if the ip is within the range, including
      highest/lowest values, then it's witin the CIDR range */

        return ($check >= $low && $check <= $high);
    }
}
