<?php

namespace SQLI\EzToolboxBundle\Annotations\Attribute;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY"})
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EntityProperty implements SQLIEntityPropertyAttribute
{
    public function __construct(
        public bool $visible = true,
        public bool $readonly = false,
        public string $description = "",
        public ?array $choices = null,
        public ?string $extra_link = null, /* @Enum({"content", "location", "tag"}) */
    ) {
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array|null
     */
    public function getChoices(): ?array
    {
        return $this->choices;
    }

    /**
     * @return string|null
     */
    public function getExtraLink(): ?string
    {
        return $this->extra_link;
    }
}
