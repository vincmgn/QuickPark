<?php

namespace App\DBAL\Types;

use App\Entity\Traits\DataStatus;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class DataStatusType extends StringType
{
    const DATASTATUS = 'data_status';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {


        return DataStatus::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value->value;
    }

    public function getName(): string
    {
        return self::DATASTATUS;
    }
}
