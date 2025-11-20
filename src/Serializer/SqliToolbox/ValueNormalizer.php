<?php

declare(strict_types=1);

namespace SQLI\EzToolboxBundle\Serializer\SqliToolbox;

use SQLI\EzToolboxBundle\FieldType\SqliToolbox\Value;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ValueNormalizer implements NormalizerInterface
{
    public function normalize($data, string $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        return [
            'className' => $data->getClassName(),
            'pkKey' => $data->getPkKey(),
            'pkValue' => $data->getPkValue()
        ];
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Value;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Value::class => true,
        ];
    }
}