<?php

namespace SQLI\EzToolboxBundle\Services\Formatter;

use Monolog\Formatter\LineFormatter;

class SqliSimpleLogFormatter extends LineFormatter
{
    public const SIMPLE_FORMAT = "[%datetime%] %channel%.%level_name%: %message%\n";

    public function __construct(
        string $format = null,
        string $dateFormat = null,
        bool $allowInlineLineBreaks = false,
        bool $ignoreEmptyContextAndExtra = false
    ) {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
        $this->format = self::SIMPLE_FORMAT;
    }
}
