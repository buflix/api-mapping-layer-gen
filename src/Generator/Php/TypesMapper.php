<?php

namespace ApiMappingLayerGen\Generator\Php;

class TypesMapper
{
    const TYPES_MAP = [
        'integer' => 'int',
        'number' => 'float',
        'boolean' => 'bool'
    ];

    public static function mapType(string $type)
    {
        if (isset(self::TYPES_MAP[$type])) {
            return self::TYPES_MAP[$type];
        } else {
            return $type;
        }
    }
}