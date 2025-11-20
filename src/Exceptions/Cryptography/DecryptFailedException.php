<?php

namespace SQLI\EzToolboxBundle\Exceptions\Cryptography;

class DecryptFailedException extends SqliCryptographyException
{
    public function __construct($data = null, $exception = null)
    {
        parent::__construct('Unable to decrypt datas', $data, 520, $exception);
    }
}
