<?php

namespace ApiMappingLayerGen\Parser;

interface ParserInterface
{
    public static function parse(string $definition) : array;
}
