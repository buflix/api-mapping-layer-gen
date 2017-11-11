<?php

namespace ApiMappingLayerGen\Parser;

class JsonParser implements ParserInterface
{
    public static function parse(string $definition) : array
    {
        return json_decode($definition, true);
    }
}
