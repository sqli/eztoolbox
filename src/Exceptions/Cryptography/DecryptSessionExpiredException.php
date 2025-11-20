<?php

namespace SQLI\EzToolboxBundle\Exceptions\Cryptography;

class DecryptSessionExpiredException extends SqliCryptographyException
{
    public function __construct($data = null, $exception = null)
    {
        parent::__construct('Encrypted message expired', $data, 520, $exception);
    }
}
