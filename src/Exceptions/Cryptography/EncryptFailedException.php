<?php

namespace SQLI\EzToolboxBundle\Exceptions\Cryptography;

class EncryptFailedException extends SqliCryptographyException
{
    public function __construct($data = null, $exception = null)
    {
        parent::__construct('Unable to encrypt datas', $data, 400, $exception);
    }
}
