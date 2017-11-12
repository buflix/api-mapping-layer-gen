<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

interface EntityGeneratorInterface
{
    const NAMESPACE_GENERATED_ENTITIES = 'GeneratedEntities';
    const NAMESPACE_ENTITIES = 'Entities';
    const NAMESPACE_COLLECTIONS = 'Collections';

    public function processPatterns(array $patterns, string $targetNamespace);
    public function getGeneratedEntities() : array;
    public function getEntities() : array;
    public function getCollections() : array;
}
