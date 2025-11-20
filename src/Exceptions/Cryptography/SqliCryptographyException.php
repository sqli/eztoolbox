<?php

namespace SQLI\EzToolboxBundle\Exceptions\Cryptography;

use Exception;
use Throwable;

class SqliCryptographyException extends Exception
{
    /**
     * @var mixed
     */
    private $data;

    public function __construct($message = "", $data = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function hasData(): bool
    {
        return !is_null($this->data);
    }

    public function __toString()
    {
        return parent::__toString() . "\nDump: " . $this->dumpData();
    }

    public function dumpData()
    {
        return print_r($this->data, true);
    }
}
