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
                $generatedEntityGenerator->setExtendedClass(
                    $targetNamespace . '\\' . self::NAMESPACE_GENERATED_ENTITIES . '\\' . self::ABSTRACT_ENTITY_NAME
                );
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
        if ($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
            $innerPattern = $this->getInnerPattern($pattern);
            if ($innerPattern instanceof EntityPattern) {
                $targetClass = '\\' . $this->entitiesNamespace . '\\' . $innerPattern->getClassName();
                $inLoopBeforeValue = 'new ' . $targetClass . '(';
                $inLoopBehindValue = ' ?? [])';
            } else {
                $inLoopBeforeValue = '';
                $inLoopBehindValue = ' ?? null';
            }

            return $this->createRecursiveArrayLoop(
                $pattern,
                '$data[\'' . $pattern->getLowerCamelCaseName() . '\'] ?? []',
                $inLoopBeforeValue,
                $inLoopBehindValue,
                true
            );
        } else {
            return '$this->set' . $pattern->getUpperCamelCaseName() . '(' . $value . ');';
        }
    }

    protected function getInnerPattern(PropertyPattern $pattern)
    {
        while($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
            $pattern = $pattern->getContentProperty();
        }
        return $pattern;
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
        $preparationCalls = '';
        $toArrayComponents = [];
        /* @var $pattern PropertyPattern */
        foreach ($properties as $pattern) {
            if ($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
                $preparationCall = $this->createRecursiveArrayLoop(
                    $pattern,
                    '$this->get' . $pattern->getUpperCamelCaseName() . '() ?? []',
                    '',
                    '->toArray()',
                    false
                );
                $preparationCalls .= $preparationCall . "\n";
            }

            $toArrayComponents[] = '    \'' . $pattern->getName() . '\' => ' . $this->createToArrayCall($pattern);
        }
        if ($this->hideNullValues) {
            return $preparationCalls .
                'return array_filter(' . "\n" .
                '    [' . "\n" .
                '    ' . implode(",\n    ", $toArrayComponents) . "\n" .
                '    ],' . "\n" .
                '    function ($value) {' . "\n" .
                '        return $value !== null;' . "\n" .
                '    }' . "\n" .
                ');';
        } else {
            return $preparationCalls .
                'return [' . "\n" .
                implode(",\n", $toArrayComponents) . "\n" .
                '];';
        }
    }

    protected function createToArrayCall(PropertyPattern $pattern)
    {
        if ($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
            return '$' . $pattern->getName() . 'Collection1';
        } elseif ($pattern instanceof EntityPattern) {
            return '$this->get' . $pattern->getUpperCamelCaseName() . '()->toArray()';
        } else {
            return '$this->get' . $pattern->getUpperCamelCaseName() . '()';
        }
    }

    protected function createRecursiveArrayLoop(
        PropertyPattern $pattern,
        string $loopArray,
        string $inLoopBeforeValue,
        string $inLoopBehindValue,
        bool $setResult,
        int $variablesSuffix = 1
    )
    {
        if ($pattern instanceof ArrayPattern) {
            $loopCollectionName = $pattern->getName() . 'Collection' . $variablesSuffix;
            $loopIteratorName = $pattern->getName() . 'CollectionItem' . $variablesSuffix;

            $subPattern = $pattern->getContentProperty();
            if ($subPattern instanceof ArrayPattern || $subPattern instanceof AssocPattern) {
                $subCall = $this->createRecursiveArrayLoop(
                    $subPattern,
                    '$' . $loopIteratorName,
                    $inLoopBeforeValue,
                    $inLoopBehindValue,
                    $setResult,
                    $variablesSuffix + 1
                );
            } else {
                $subCall = $inLoopBeforeValue . '$' . $loopIteratorName . $inLoopBehindValue;
            }

            $lines = explode("\n", $subCall);
            end($lines);
            $lastElementKey = key($lines);
            $lines[$lastElementKey] = '$' . $loopCollectionName . '[$key' . $variablesSuffix . '] = ' . $lines[$lastElementKey] . ';';

            foreach ($lines as $key => $line) {
                $lines[$key] = '    ' . $line;
            }

            $loopCall = '$' . $loopCollectionName . ' = [];' . "\n";
            $loopCall .= 'foreach (' . $loopArray . ' as $key' . $variablesSuffix . ' => $' . $loopIteratorName . ') {' . "\n";
            $loopCall .= implode("\n", $lines) . "\n";
            $loopCall .= '}' . "\n";
            if ($variablesSuffix > 1) {
                $loopCall .= '$' . $loopCollectionName;
            } elseif ($setResult) {
                $loopCall .= '$this->set' . $pattern->getUpperCamelCaseName() . '($' . $loopCollectionName . ');';
            }
            return $loopCall;
        } elseif ($pattern instanceof AssocPattern) {

        } else {
            return '$this->set' . $pattern->getUpperCamelCaseName() . '(' . $loopArray . ');';
        }
    }
}
