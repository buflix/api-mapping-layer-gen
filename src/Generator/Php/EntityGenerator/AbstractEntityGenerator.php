<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

class AbstractEntityGenerator
{
    protected $addDocblockTypes;
    protected $addDocblockDescriptions;
    protected $useFluentSetters;

    protected $generatedEntities = [];
    protected $entities = [];
    protected $collections = [];

    public function __construct(array $settings = [])
    {
        $this->addDocblockTypes = $settings['addDocblockTypes'] ?? true;
        $this->addDocblockDescriptions = $settings['addDocblockDescriptions'] ?? false;
        $this->useFluentSetters = $settings['useFluentSetters'] ?? false;
    }

    public function getGeneratedEntities() : array
    {
        return $this->generatedEntities;
    }

    public function getEntities() : array
    {
        return $this->entities;
    }

    public function getCollections() : array
    {
        return $this->collections;
    }
}
