<?php

namespace ApiMappingLayerGen\Parser;

interface ParserInterface
{
	/**
	 * Method to parse a given api definition (e.g. imported from a file) and return it in
	 * a standartized native PHP array so it an be further processed
	 * @param string $definition
	 * @return array
	 */
    public static function parse(string $definition): array;
}
