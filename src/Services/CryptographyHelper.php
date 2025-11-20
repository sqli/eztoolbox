<?php

namespace SQLI\EzToolboxBundle\Services;

use SQLI\EzToolboxBundle\Exceptions\Cryptography\DecryptFailedException;
use SQLI\EzToolboxBundle\Exceptions\Cryptography\DecryptSessionExpiredException;
use SQLI\EzToolboxBundle\Exceptions\Cryptography\EncryptFailedException;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Structure of crypted datas :
 * Array(
 *   'session' => session_id(),
 *   'data' => serialize(data)
 * )
 * Class CryptographyHelper.
 */
class CryptographyHelper
{
    private const CIPHER = 'AES-256-CBC';
    public const GUID_LENGTH = 60;
    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param mixed $data Datas will be serialized before encryption
     * @param bool $urlencode Prevent slashes in returned string to use it in URL
     * @param bool $encryptWithSession With 'true', returned string will be different in other PHP session. If 'false',
     * decryption $checkSessionExpiration parameter MUST be 'false'
     *
     * @return string
     *
     * @throws EncryptFailedException
     */
    public function encrypt($data, bool $urlencode = true, bool $encryptWithSession = true): string
    {
        try {
            $dataToEncrypt = [
                'session' => $encryptWithSession ? session_id() : null,
                'data'    => serialize($data),
            ];
            $encryptedText = openssl_encrypt(
                serialize($dataToEncrypt),
                self::CIPHER,
                $this->parameterBag->get('kernel.secret'),
                OPENSSL_RAW_DATA,
                $this->getOpenSSLInitializationVector()
            );
            $encryptedText = base64_encode($encryptedText);
            if ($urlencode) {
                $encryptedText = strtr($encryptedText, '+/=', '._-');
            }

            return $encryptedText;
        } catch (Exception $exception) {
            throw new EncryptFailedException($data, $exception);
        }
    }

    /**
     * @param string $encryptedData Data to decrypt
     * @param bool $urldecode 'true' if given data retrieved from URL
     * @param bool $checkSessionExpiration 'true' to verify if data has been encrypted in current session
     *
     * @return false|string
     *
     * @throws DecryptFailedException
     * @throws DecryptSessionExpiredException
     */
    public function decrypt(string $encryptedData, bool $urldecode = true, bool $checkSessionExpiration = true)
    {
        try {
            if ($urldecode) {
                $encryptedData = strtr($encryptedData, '._-', '+/=');
            }
            $encryptedData = base64_decode($encryptedData);

            $serializedDatas = openssl_decrypt(
                $encryptedData,
                self::CIPHER,
                $this->parameterBag->get('kernel.secret'),
                OPENSSL_RAW_DATA,
                $this->getOpenSSLInitializationVector()
            );
            $decryptedDatas  = unserialize($serializedDatas);
            if ($checkSessionExpiration) {
                if ($decryptedDatas['session'] !== session_id()) {
                    throw new DecryptSessionExpiredException();
                }
            }

            return unserialize($decryptedDatas['data']);
        } catch (Exception $exception) {
            throw new DecryptFailedException($encryptedData, $exception);
        }
    }

    public function generateRandomGuid(int $length = self::GUID_LENGTH): string
    {
        try {
            return bin2hex(random_bytes((int)$length / 2));
        } catch (Exception $exception) {
            return bin2hex(openssl_random_pseudo_bytes(self::GUID_LENGTH / 2));
        }
    }

    private function getOpenSSLInitializationVector(): string
    {
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv    = $this->parameterBag->get('kernel.secret');

        return mb_substr($iv, 0, $ivlen);
    }
}
