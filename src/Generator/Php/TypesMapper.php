<?php

namespace ApiMappingLayerGen\Generator\Php;

/**
 * Map types to php types
 */
class TypesMapper
{
    const TYPES_MAP = [
        'integer' => 'int',
        'number' => 'float',
        'boolean' => 'bool'
    ];

    /**
     * @param string $type
     * @return mixed|string
     */
    public static function mapType(string $type)
    {
        return self::TYPES_MAP[$type] ?? $type;
    }
}