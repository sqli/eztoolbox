<?php
declare(strict_types=1);

namespace SQLI\EzToolboxBundle\Serializer\SqliToolbox;

use SQLI\EzToolboxBundle\FieldType\SqliToolbox\Value;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ValueDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = []): mixed
    {
        if (is_array($data) && count($data)==3) {
            return new $type($data['className'], $data['pkKey'], $data['pkValue']);
        }

        return $data;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === Value::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Value::class => true,
        ];
    }
}
