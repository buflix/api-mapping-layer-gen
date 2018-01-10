<?php

namespace ApiMappingLayerGen\Mapper\OpenApi;

use ApiMappingLayerGen\Mapper\MapperInterface;
use ApiMappingLayerGen\Mapper\Pattern\ArrayPattern;
use ApiMappingLayerGen\Mapper\Pattern\AssocPattern;
use ApiMappingLayerGen\Mapper\Pattern\EntityPattern;
use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;

class Mapper implements MapperInterface
{
    protected $definitionFile;
    protected $referenceResolver;
    protected $allOfResolver;
    protected $patterns = [];

    public function __construct(string $definitionFile)
    {
        $this->definitionFile = $definitionFile;
        $this->referenceResolver = new ReferenceResolver();
        $this->allOfResolver = new AllOfResolver();
        $this->process();
    }

    public function getPatterns() : array
    {
        return $this->patterns;
    }

    protected function process()
    {
        $baseDefinition = $this->referenceResolver->resolveReference($this->definitionFile);
        $baseDefinitions = $baseDefinition->getValue();
        $baseDefinitions = $this->referenceResolver->resolveAllReferences(
            $baseDefinitions,
            $this->definitionFile
        );
        $baseDefinitions = $this->allOfResolver->resolveKeywordAllOf($baseDefinitions);

        $models = null;
        if (isset($baseDefinitions['swagger']) && version_compare($baseDefinitions['swagger'], '3.0') < 0) {
            if (!isset($baseDefinitions['definitions'])) {
                throw new \Exception('Your definition file has no definitions!');
            }
            $models = $baseDefinitions['definitions'];
        } elseif (isset($baseDefinitions['openapi']) && version_compare($baseDefinitions['openapi'], '3.0') >= 0) {
            if (!isset($baseDefinitions['components']) || !isset($baseDefinitions['components']['schemas'])) {
                throw new \Exception('Your definition file has no components -> schemas!');
            }
            $models = $baseDefinitions['components']['schemas'];
        }
        foreach ($models as $name => $definition) {
            $this->patterns[] = $this->createDefinitionPattern($name, $definition, true);
        }
    }

    protected function createDefinitionPattern(string $name, array $definition, bool $isEntity)
    {
        if ($definition['type'] === 'object') {
            if ($isEntity) {
                $pattern = new EntityPattern();
                $pattern->setName($name);
                if (isset($definition['$ref'])) {
                    $className = substr($definition['$ref'], strrpos($definition['$ref'], '/') + 1);
                    $pattern->setClassName($className);
                }
                foreach ($definition['properties'] ?? [] as $propertyName => $propertyDef) {
                    $propertyPattern = $this->createDefinitionPattern($propertyName, $propertyDef, isset($propertyDef['$ref']));
                    $pattern->addProperty($propertyPattern);
                }
                if (isset($definition['required'])) {
                    /* @var $property PropertyPattern */
                    foreach ($pattern->getProperties() as $property) {
                        if (in_array($property->getName(), $definition['required'], true)) {
                            $property->setRequired(true);
                        }
                    }
                }
            } else {
                $pattern = new AssocPattern();
                $pattern->setName($name);
                foreach ($definition['properties'] ?? [] as $propertyName => $propertyDef) {
                    $propertyPattern = $this->createDefinitionPattern($propertyName, $propertyDef, isset($propertyDef['$ref']));
                    $pattern->setContentProperty($propertyPattern);
                    break;  //pick first property of assoc as content type
                }
            }
        } elseif ($definition['type'] === 'array') {
            $pattern = new ArrayPattern();
            $itemPattern = $this->createDefinitionPattern('items', $definition['items'], isset($definition['items']['$ref']));
            $pattern->setContentProperty($itemPattern);
            $pattern->setName($name);
        } else {
            $pattern = new PropertyPattern();
            $pattern->setName($name);
        }
        $pattern->setType($definition['type']);
        if (isset($definition['description'])) {
            $pattern->setDescription($definition['description']);
        }
        return $pattern;
    }
}
