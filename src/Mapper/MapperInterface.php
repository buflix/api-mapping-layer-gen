<?php

namespace ApiMappingLayerGen\Mapper;

interface MapperInterface
{
	/**
	 * Method to retrieve pattern definitions from an api definition
	 * set during the processing of the definitions
	 * @return array
	 */
    public function getPatterns(): array;
}