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
        $baseDefinitions = $this->referenceResolver->resolveAllReferences(
            $baseDefinition->getValue(),
            $this->definitionFile
        );
        $baseDefinitions = $this->allOfResolver->resolveKeywordAllOf($baseDefinitions);

        foreach ($baseDefinitions['definitions'] as $name => $definition) {
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
            } else {
                $pattern = new AssocPattern();
                $pattern->setName($name);
            }
        } elseif ($definition['type'] === 'array') {
            $pattern = new ArrayPattern();
            $pattern->setName($name);
        } else {
            $pattern = new PropertyPattern();
            $pattern->setName($name);
            $pattern->setType($definition['type']);
        }
        return $pattern;
    }
}
