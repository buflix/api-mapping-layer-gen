<?php

namespace ApiMappingLayerGen\Generator\Php;

use ApiMappingLayerGen\Generator\Common\TargetDirectory;
use ApiMappingLayerGen\Generator\Php\EntityGenerator\EntityGeneratorInterface;
use Composer\Autoload\ClassLoader;

/**
 * Creates the files the given entity generator produced
 */
class EntityBuilder
{
    /**
     * @var EntityGeneratorInterface
     */
    protected $entityGenerator;

    /**
     * @param EntityGeneratorInterface $entityGenerator
     */
    public function __construct(EntityGeneratorInterface $entityGenerator)
    {
        $this->entityGenerator = $entityGenerator;
    }

    /**
     * Builds the entities and creates the files
     *
     * @param array $patterns
     * @param string $targetNamespace
     * @param string|null $targetDirectory
     * @throws \Exception
     */
    public function buildEntities(array $patterns, string $targetNamespace, string $targetDirectory = null)
    {
        if ($targetDirectory === null) {
            $loader = spl_autoload_functions()[0][0];
            if (!$loader instanceof ClassLoader) {
                throw new \Exception('You need to provide the $targetDirectory for EntityBuilder::buildEntities() as you are not using the default composer autoloader!');
            }
            $namespaces = $loader->getPrefixesPsr4();
            $namespace = $targetNamespace . '\\';
            $targetDirectory = reset($namespaces[$namespace]);
        }
        $targetDirectory = TargetDirectory::getCanonicalTargetDirectory($targetDirectory);

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
