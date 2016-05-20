<?php

namespace App\ApiModel;

use App\ApiModel\Exceptions\TypeMapperException;

class TypeMapper
{
    protected static $mappedTypes = [
        'party'    => Party::class,
        'property' => Property::class,
        'operator' => Operator::class,
    ];

    public static function shouldBeMapped($type)
    {
        return array_key_exists($type, static::$mappedTypes);
    }

    public static function getMappedClass($type)
    {
        if(! static::shouldBeMapped($type))
        {
            throw new TypeMapperException("Could not map $type to any class");
        }

        return static::$mappedTypes[$type];
    }
}