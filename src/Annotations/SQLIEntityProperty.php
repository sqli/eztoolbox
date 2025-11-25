<?php

namespace SQLI\EzToolboxBundle\Annotations;

interface SQLIEntityProperty {
    public function isVisible(): bool;
    public function isReadonly(): bool;
    public function getDescription(): string;
    public function getChoices(): ?array;
    public function getExtraLink(): ?string;
}
