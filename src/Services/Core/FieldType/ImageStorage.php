<?php

namespace SQLI\EzToolboxBundle\Services\Core\FieldType;

use Ibexa\Core\FieldType\Image\ImageStorage as BaseImageStorage;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

class ImageStorage extends BaseImageStorage
{
    public function storeFieldData(VersionInfo $versionInfo, Field $field): bool
    {
        // Original filename, convert characters when it's possible (or remove them)
        $fileName = $field->value->externalData['fileName'];
        $fileName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fileName);
        // Replace spaces
        $fileName = preg_replace('/\s/', '_', $fileName);
        // Force lower case
        $fileName = mb_convert_case($fileName, MB_CASE_LOWER);
        // Cleaned filename can be used in original process
        $field->value->externalData['fileName'] = $fileName;

        return parent::storeFieldData($versionInfo, $field);
    }
}
