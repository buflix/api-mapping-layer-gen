<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

interface EntityGeneratorInterface
{
    const NAMESPACE_GENERATED_ENTITIES = 'GeneratedEntities';
    const NAMESPACE_ENTITIES = 'Entities';
    const NAMESPACE_COLLECTIONS = 'Collections';

    /**
     * Method to process pattern defintions and create an entity
     * structure based on processed patterns
     * @param array $patterns
     * @param string $targetNamespace
     * @return null
     */
    public function processPatterns(array $patterns, string $targetNamespace);

    /**
     * Method to retrieve created mapping entity structure
     * @return array
     */
    public function getGeneratedEntities() : array;

    /**
     * Method to retrieve created entity structure of
     * entites for additional functionality
     * @return array
     */
    public function getEntities() : array;

    public function getCollections() : array;
}
