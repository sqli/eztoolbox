<?php

namespace SQLI\EzToolboxBundle\Annotations;

interface SQLIEntity {
    public function isCreate(): bool;
    public function isUpdate(): bool;
    public function isDelete(): bool;
    public function getDescription(): string;
    public function getMaxPerPage(): int;
    public function isCSVExportable(): bool;
    public function getTabname(): string;
}
