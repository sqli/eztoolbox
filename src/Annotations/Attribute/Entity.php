<?php

namespace SQLI\EzToolboxBundle\Annotations\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("CLASS")
 * @template T of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity implements SQLIEntityAttribute
{
    public function __construct(
        public bool $create = false,
        public bool $update = false,
        public bool $delete = false,
        public string $description = "",
        public int $max_per_page = 10,
        public bool $csv_exportable = false,
        public string $tabname = "default",
    ) {
    }

    /**
     * @return bool
     */
    public function isCreate(): bool
    {
        return $this->create;
    }

    /**
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->update;
    }

    /**
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->delete;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getMaxPerPage(): int
    {
        return $this->max_per_page;
    }

    /**
     * @return bool
     */
    public function isCSVExportable(): bool
    {
        return $this->csv_exportable;
    }

    public function getTabname(): string
    {
        return $this->tabname;
    }
}
