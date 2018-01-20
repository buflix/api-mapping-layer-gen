<?php

namespace ApiMappingLayerGen\Mapper\OpenApi;

use ApiMappingLayerGen\Mapper\MapperInterface;
use ApiMappingLayerGen\Mapper\Pattern\ArrayPattern;
use ApiMappingLayerGen\Mapper\Pattern\AssocPattern;
use ApiMappingLayerGen\Mapper\Pattern\EntityPattern;
use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;

/**
 * Class to map definition file to definition or pattern objects
 * These created definition or patterns will be needed by the generators
 */
class Mapper implements MapperInterface
{
    protected $definitionFile;
    protected $referenceResolver;
    protected $allOfResolver;
    protected $patterns = [];
    protected $definition = [];

    /**
     * @param string $definitionFile
     * @throws \Exception
     */
    public function __construct(string $definitionFile)
    {
        $this->definitionFile = $definitionFile;
        $this->referenceResolver = new ReferenceResolver();
        $this->allOfResolver = new AllOfResolver();
        $this->process();
    }

    /**
     * @return array
     */
    public function getPatterns() : array
    {
        return $this->patterns;
    }

    /**
     * @return array
     */
    public function getDefinition() : array
    {
        return $this->definition;
    }

    /**
     * @throws \Exception
     */
    protected function process()
    {
        $baseDefinition = $this->referenceResolver->resolveReference($this->definitionFile);
        $baseDefinition = $baseDefinition->getValue();
        $baseDefinition = $this->referenceResolver->resolveAllReferences(
            $baseDefinition,
            $this->definitionFile
        );
        $this->definition = $this->allOfResolver->resolveKeywordAllOf($baseDefinition);

        $models = null;
        if (isset($this->definition['swagger']) && version_compare($this->definition['swagger'], '3.0') < 0) {
            if (!isset($this->definition['definitions'])) {
                throw new \Exception('Your definition file has no definitions!');
            }
            $models = $this->definition['definitions'];
        } elseif (isset($this->definition['openapi']) && version_compare($this->definition['openapi'], '3.0') >= 0) {
            if (!isset($this->definition['components']) || !isset($this->definition['components']['schemas'])) {
                throw new \Exception('Your definition file has no components -> schemas!');
            }
            $models = $this->definition['components']['schemas'];
        }
        foreach ($models as $name => $definition) {
            $this->patterns[] = $this->createDefinitionPattern($name, $definition, true);
        }
    }

    /**
     * Maps OpenApi model to pattern object
     *
     * @param string $name
     * @param array $definition
     * @param bool $isEntity
     * @return ArrayPattern|AssocPattern|EntityPattern|PropertyPattern
     */
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
