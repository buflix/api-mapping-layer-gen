<?php

namespace ApiMappingLayerGen\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserInterface
{
    /**
     * @param string $definition
     * @return array
     */
    public static function parse(string $definition) : array
    {
        return Yaml::parse($definition);
    }
}
