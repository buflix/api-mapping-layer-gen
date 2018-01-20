<?php

namespace ApiMappingLayerGen\Parser;

class JsonParser implements ParserInterface
{
    /**
     * @param string $definition
     * @return array
     */
    public static function parse(string $definition) : array
    {
        return json_decode($definition, true);
    }
}
