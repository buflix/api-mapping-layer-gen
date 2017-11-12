<?php

namespace ApiMappingLayerGen\Generator\Php;

use ApiMappingLayerGen\Generator\Php\EntityGenerator\EntityGeneratorInterface;

class EntityBuilder
{
    protected $entityGenerator;

    public function __construct(EntityGeneratorInterface $entityGenerator)
    {
        $this->entityGenerator = $entityGenerator;
    }

    public function buildEntities(array $patterns, string $targetNamespace, string $targetDirectory = null)
    {
        $this->entityGenerator->processPatterns($patterns, $targetNamespace);

        foreach ($this->entityGenerator->getGeneratedEntities() as $className => $generatedEntity) {
            $file = $targetDirectory . EntityGeneratorInterface::NAMESPACE_GENERATED_ENTITIES . '/' . $className . '.php';
            file_put_contents($file, $generatedEntity);
        }

        foreach ($this->entityGenerator->getEntities() as $className => $entity) {
            $file = $targetDirectory . EntityGeneratorInterface::NAMESPACE_ENTITIES . '/' . $className . '.php';
            if (!is_file($file)) {
                file_put_contents($file, $entity);
            }
        }

        foreach ($this->entityGenerator->getCollections() as $className => $collection) {
            $file = $targetDirectory . EntityGeneratorInterface::NAMESPACE_COLLECTIONS . '/' . $className . '.php';
            if (!is_file($file)) {
                file_put_contents($file, $collection);
            }
        }
    }
}
