<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

use ApiMappingLayerGen\Generator\Php\TypesMapper;
use ApiMappingLayerGen\Mapper\Pattern\ArrayPattern;
use ApiMappingLayerGen\Mapper\Pattern\AssocPattern;
use ApiMappingLayerGen\Mapper\Pattern\EntityPattern;
use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;

class Native extends AbstractEntityGenerator implements EntityGeneratorInterface
{
    protected $entitiesNamespace;

    public function processPatterns(array $patterns, string $targetNamespace)
    {
        $this->entitiesNamespace = $targetNamespace . '\\' . self::NAMESPACE_ENTITIES;

        $this->addAbstractGeneratedEntity($targetNamespace);
        /* @var $pattern PropertyPattern */
        foreach ($patterns as $pattern) {
            if ($pattern instanceof EntityPattern) {
                $className = 'Generated' . $pattern->getName();
                $generatedEntityGenerator = new ClassGenerator();
                $generatedEntityGenerator->setName($className);
                $generatedEntityGenerator->setNamespaceName($targetNamespace . '\\' . self::NAMESPACE_GENERATED_ENTITIES);
                $generatedEntityGenerator->addFlag(ClassGenerator::FLAG_ABSTRACT);
                $generatedEntityGenerator->setExtendedClass(self::ABSTRACT_ENTITY_NAME);
                /* @var $property PropertyPattern */
                foreach ($pattern->getProperties() as $property) {
                    $type = TypesMapper::mapType($property->getType());
                    if ($property instanceof EntityPattern) {
                        $type = '\\' . $this->entitiesNamespace . '\\' . $property->getClassName();
                    }

                    $this->addProperty($generatedEntityGenerator, $property, $type);
                }
                $generatedEntityGenerator->addMethodFromGenerator($this->createPopulate($pattern->getProperties()));
                $generatedEntityGenerator->addMethodFromGenerator($this->createToArray($pattern->getProperties()));
                $this->generatedEntities[$className] = "<?php\n\n" . $generatedEntityGenerator->generate();

                $this->addChildEntity($generatedEntityGenerator, $pattern->getName(), $this->entitiesNamespace);
            }
        }
    }

    protected function createPopulate(array $properties) : MethodGenerator
    {
        $generator = new MethodGenerator();
        $generator->setName('populate');
        $generator->setParameters([
            [
                'name' => 'data',
                'type' => 'array'
            ]
        ]);
        $generator->setBody($this->createPopulateBody($properties));

        //addDocblocks
        if ($this->addDocblockTypes || $this->addDocblockDescriptions) {
            $docblock = '';
            if ($this->addDocblockDescriptions) {
                $docblock .= 'Populate the entity';
            }
            if ($this->addDocblockTypes) {
                if (!empty($docblock)) {
                    $docblock .= "\n\n";
                }
                $docblock .= '@param array $data';
            }
            $generator->setDocBlock($docblock);
        }
        return $generator;
    }

    protected function createPopulateBody(array $properties) : string
    {
        $populations = [];
        /* @var $pattern PropertyPattern */
        foreach ($properties as $pattern) {
            $populations[] = $this->createPopulationCall($pattern);
        }

        return implode("\n\n", $populations);
    }

    protected function createPopulationCall(PropertyPattern $pattern, int $variablesSuffix = 1)
    {
        $value = $this->createPopulationCallValue($pattern);
        if ($pattern instanceof ArrayPattern) {
            $loopCollectionName = 'collection' . $variablesSuffix;
            $loopIteratorName = 'collectionItem' . $variablesSuffix;

            $subPattern = $pattern->getContentProperty();
            if ($subPattern instanceof ArrayPattern || $subPattern instanceof AssocPattern) {
                $subPopulationCall = $this->createPopulationCall($subPattern, $variablesSuffix + 1);
            } else {
                $subPopulationCall = $this->createPopulationCallValue($subPattern);
            }

            $contentPropertyCallValue = str_replace(
                '$data[\'items\']',
                '$' . $loopIteratorName,
                $subPopulationCall
            );

            $lines = [];
            foreach (explode("\n", $contentPropertyCallValue) as $line) {
                $lines[] = '    ' . $line;
            }

            end($lines);
            $lastElementKey = key($lines);
            $lastLineValue = substr($lines[$lastElementKey], 4);
            if (count($lines) > 1) {
                if (substr($lastLineValue, 0, 16) === '$this->setItems(') {
                    $lastLineValue = substr($lastLineValue, 16, strlen($lastLineValue) - 18);
                }
            }
            $lines[$lastElementKey] = '    $' . $loopCollectionName . '[] = ' . $lastLineValue . ';';

            $populationCall = '$' . $loopCollectionName . ' = [];' . "\n";
            $populationCall .= 'foreach (' . $value . ' as $' . $loopIteratorName . ') {' . "\n";
            $populationCall .= implode("\n", $lines) . "\n";
            $populationCall .= '}' . "\n";
            $populationCall .= '$this->set' . $pattern->getUpperCamelCaseName() . '($' . $loopCollectionName . ');';
            return $populationCall;
        } elseif ($pattern instanceof AssocPattern) {

        } else {
            return '$this->set' . $pattern->getUpperCamelCaseName() . '(' . $value . ');';
        }
    }

    protected function createPopulationCallValue(PropertyPattern $pattern) : string
    {
        if ($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
            return '$data[\'' . $pattern->getName() . '\'] ?? []';
        } elseif ($pattern instanceof EntityPattern) {
            $targetClass = '\\' . $this->entitiesNamespace . '\\' . $pattern->getClassName();
            return 'new ' . $targetClass . '($data[\'' . $pattern->getName() . '\'] ?? [])';
        } else {
            return '$data[\'' . $pattern->getName() . '\'] ?? null';
        }
    }

    protected function createToArray(array $properties) : MethodGenerator
    {
        $generator = new MethodGenerator();
        $generator->setName('toArray');
        $generator->setReturnType('array');
        $generator->setBody($this->createToArrayBody($properties));

        //addDocblocks
        if ($this->addDocblockTypes || $this->addDocblockDescriptions) {
            $docblock = '';
            if ($this->addDocblockDescriptions) {
                $docblock .= 'Get entity data as array';
            }
            if ($this->addDocblockTypes) {
                if (!empty($docblock)) {
                    $docblock .= "\n\n";
                }
                $docblock .= '@return array';
            }
            $generator->setDocBlock($docblock);
        }
        return $generator;
    }

    protected function createToArrayBody(array $properties) : string
    {
        $toArrayComponents = [];
        /* @var $pattern PropertyPattern */
        foreach ($properties as $pattern) {
            $toArrayComponents[] = '    \'' . $pattern->getName() . '\' => ' . $this->createToArrayCall($pattern);
        }
        return 'return [' . "\n" . implode(",\n", $toArrayComponents) . "\n" . '];';
    }

    protected function createToArrayCall(PropertyPattern $pattern)
    {
        if ($pattern instanceof ArrayPattern) {
            return 'null';
        } elseif ($pattern instanceof AssocPattern) {
            return 'null';
        } elseif ($pattern instanceof EntityPattern) {
            return '$this->get' . $pattern->getUpperCamelCaseName() . '()->toArray()';
        } else {
            return '$this->get' . $pattern->getUpperCamelCaseName() . '()';
        }
    }
}
