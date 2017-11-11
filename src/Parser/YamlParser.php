<?php

namespace ApiMappingLayerGen\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserInterface
{
    public static function parse(string $definition) : array
    {
        return Yaml::parse($definition);
    }
}
