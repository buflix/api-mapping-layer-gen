<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

use ApiMappingLayerGen\Generator\Php\TypesMapper;
use ApiMappingLayerGen\Mapper\Pattern\ArrayPattern;
use ApiMappingLayerGen\Mapper\Pattern\AssocPattern;
use ApiMappingLayerGen\Mapper\Pattern\EntityPattern;
use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;
use SimpleCollection\ArrayCollection;
use SimpleCollection\AssocCollection;
use SimpleCollection\Entity\AbstractEntity;
use SimpleCollection\Entity\EntityArrayCollection;
use SimpleCollection\Entity\EntityAssocCollection;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;

/**
 * The created entities will use collection classes instead of arrays.
 *
 * Collections can give your entities a clear defined structure and can be extended with extra features which could help
 *  you to have a clean code structure.
 * But collections will also have some overhead rising with the size of your data.
 */
class CollectionBased extends AbstractEntityGenerator implements EntityGeneratorInterface
{
    protected $addCustomCollections;

    protected $entitiesNamespace;
    protected $collectionsNamespace;

    /**
     * Create the generator with the given settings
     *
     * @see AbstractEntityGenerator
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);
        $this->addCustomCollections = $settings['addCustomCollections'] ?? true;
    }

    /**
     * @param array $patterns
     * @param string $targetNamespace
     */
    public function processPatterns(array $patterns, string $targetNamespace)
    {
        $this->entitiesNamespace = $targetNamespace . '\\' . self::NAMESPACE_ENTITIES;
        $this->collectionsNamespace = $targetNamespace . '\\' . self::NAMESPACE_COLLECTIONS;

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
                    } elseif ($property instanceof ArrayPattern || $property instanceof AssocPattern) {
                        $type = $this->getCollectionType($property);
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

    /**
     * Decides which collection class will be used in which case
     *
     * @param PropertyPattern $property
     * @return string
     */
    protected function getCollectionType(PropertyPattern $property)
    {
        $contentProperty = $property->getContentProperty();
        if (
            $contentProperty instanceof EntityPattern ||
            $contentProperty instanceof ArrayPattern ||
            $contentProperty instanceof AssocPattern
        ) {
            if ($property instanceof ArrayPattern) {
                $type = '\\' . EntityArrayCollection::class;
            } elseif ($property instanceof AssocPattern) {
                $type = '\\' . AssocCollection::class;
            }
            if ($contentProperty instanceof EntityPattern && $this->addCustomCollections) {
                $type = $this->createCustomCollection($property, $type);
            }
        } else {
            if ($property instanceof ArrayPattern) {
                $type = '\\' . ArrayCollection::class;
            } elseif ($property instanceof AssocPattern) {
                $type = '\\' . AssocCollection::class;
            }
        }
        return $type;
    }

    /**
     * Creates a custom collection class extending the collection class which matches for the case
     *
     * @param PropertyPattern $pattern
     * @param string $type
     * @return string
     */
    protected function createCustomCollection(PropertyPattern $pattern, string $type) : string
    {
        $collectionName = $this->getCollectionClass($pattern);
        if (!isset($this->collections[$collectionName])) {
            $collectionGenerator = new ClassGenerator();
            $collectionGenerator->setName($collectionName);
            $collectionGenerator->setNamespaceName($this->collectionsNamespace);
            $collectionGenerator->setExtendedClass($type);
            $this->collections[$collectionName] = "<?php\n\n" . $collectionGenerator->generate();
        }
        return '\\' . $this->collectionsNamespace . '\\' . $collectionName;
    }

    /**
     * Creates the class name for the custom collection class
     *
     * @param PropertyPattern $pattern
     * @return string
     */
    protected function getCollectionClass(PropertyPattern $pattern)
    {
        $contentProperty = $pattern->getContentProperty();
        if ($contentProperty instanceof EntityPattern) {
            return $contentProperty->getClassName() . 'Collection';
        } else {
            return $pattern->getUpperCamelCaseName() . 'Collection';
        }
    }

    /**
     * Create a method that populates the entity
     *
     * @param array $properties
     * @return MethodGenerator
     */
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

    /**
     * Creates the body of the populate method
     *
     * @param array $properties
     * @return string
     */
    protected function createPopulateBody(array $properties) : string
    {
        $populations = [];
        /* @var $pattern PropertyPattern */
        foreach ($properties as $pattern) {
            $populations[] = $this->createPopulationCall($pattern);
        }

        return implode("\n\n", $populations);
    }

    /**
     * Creates the code to populate one property of the entity
     *
     * @param PropertyPattern $pattern
     * @return string
     */
    protected function createPopulationCall(PropertyPattern $pattern)
    {
        $value = $this->createPopulationCallValue($pattern);
        if ($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
            $collectionClass = $this->getCollectionType($pattern);

            $contentProperty = $pattern->getContentProperty();
            $assocSet = false;
            if ($contentProperty instanceof EntityPattern) {
                $contentClass = '\\' . $this->entitiesNamespace . '\\' . $contentProperty->getClassName();
                $setValue = 'new ' . $contentClass . '($item)';
                if ($pattern instanceof AssocPattern) {
                    $assocSet = true;
                }
            } elseif ($contentProperty instanceof ArrayPattern) {
                $setValue = 'new ' . '\\' . ArrayCollection::class . '($item)';
            } elseif ($contentProperty instanceof AssocPattern) {
                $setValue = 'new ' . '\\' . AssocCollection::class . '($item)';
                $assocSet = true;
            } else {
                $setValue = '$item';
                if ($pattern instanceof AssocPattern) {
                    $assocSet = true;
                }
            }

            if ($assocSet) {
                $loophead = 'foreach (' . $value . ' as $key => $item) {' . "\n" .
                    '    $collection->offsetSet($key, ' . $setValue . ')' . ';' . "\n";
            } else {
                $loophead = 'foreach (' . $value . ' as $item) {' . "\n" .
                    '    $collection->add(' . $setValue . ')' . ';' . "\n";
            }

            return
                '$collection = new ' . $collectionClass . '();' . "\n" .
                $loophead .
                '}' . "\n" .
                '$this->set' . $pattern->getUpperCamelCaseName() . '($collection);'
                ;
        } elseif ($pattern instanceof EntityPattern) {
            if ($pattern->isRequired()) {
                return '$this->set' . $pattern->getUpperCamelCaseName() . '(' . $value . ');';
            } else {
                return
                    'if (isset($data[\'' . $pattern->getName() . '\']) && !empty($data[\'' . $pattern->getName() . '\'])) {' . "\n" .
                    '    $this->set' . $pattern->getUpperCamelCaseName() . '(' . $value . ');' . "\n" .
                    '}';
            }
        } else {
            return '$this->set' . $pattern->getUpperCamelCaseName() . '(' . $value . ');';
        }
    }

    /**
     * Creates the code that produces the value that will be set to a property on population
     *
     * @param PropertyPattern $pattern
     * @return string
     */
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

    /**
     * Creates the toArray() method
     *
     * @param array $properties
     * @return MethodGenerator
     */
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

    /**
     * Creates the body of the toArray() method
     *
     * @param array $properties
     * @return string
     */
    protected function createToArrayBody(array $properties) : string
    {
        $toArrayComponents = [];
        /* @var $pattern PropertyPattern */
        foreach ($properties as $pattern) {
            $toArrayComponents[] = '    \'' . $pattern->getName() . '\' => ' . $this->createToArrayCall($pattern);
        }
        return 'return [' . "\n" . implode(",\n", $toArrayComponents) . "\n" . '];';
    }

    /**
     * Creates the value of one property when calling toArray()
     *
     * @param PropertyPattern $pattern
     * @return string
     */
    protected function createToArrayCall(PropertyPattern $pattern)
    {
        if ($pattern instanceof ArrayPattern || $pattern instanceof AssocPattern) {
            $contentProperty = $pattern->getContentProperty();
            if (
                $pattern instanceof ArrayPattern
                && (
                    $contentProperty instanceof EntityPattern ||
                    $contentProperty instanceof ArrayPattern ||
                    $contentProperty instanceof AssocPattern
                )
            ) {
                return '$this->get' . $pattern->getUpperCamelCaseName() . '()->toArray()';
            } else {
                return '$this->get' . $pattern->getUpperCamelCaseName() . '()->getAll()';
            }
        } elseif ($pattern instanceof EntityPattern) {
            if ($pattern->isRequired()) {
                return '$this->get' . $pattern->getUpperCamelCaseName() . '()->toArray()';
            } else {
                return '$this->get' . $pattern->getUpperCamelCaseName() . '() ? $this->get' . $pattern->getUpperCamelCaseName() . '()->toArray() : null';
            }
        } else {
            return '$this->get' . $pattern->getUpperCamelCaseName() . '()';
        }
    }

    /**
     * Overrides the parent implementation to skip toArray() and jsonSerialize() as the are implemented in the
     * abstract entity class of the collection
     *
     * @param string $targetNamespace
     */
    protected function addAbstractGeneratedEntity(string $targetNamespace)
    {
        $generator = new ClassGenerator();
        $generator->setName(self::ABSTRACT_ENTITY_NAME);
        $generator->setNamespaceName($targetNamespace . '\\' . self::NAMESPACE_GENERATED_ENTITIES);
        $generator->addFlag(ClassGenerator::FLAG_ABSTRACT);
        $generator->setImplementedInterfaces(['\JsonSerializable']);
        $generator->setExtendedClass(AbstractEntity::class);

        //add __construct
        $construct = new MethodGenerator();
        $construct->setName('__construct');
        $construct->setParameters([
            [
                'name' => 'data',
                'type' => 'array',
                'defaultvalue' => []
            ]
        ]);
        $construct->setBody('$this->populate($data);');
        $generator->addMethodFromGenerator($construct);

        //add populate
        $populate = new MethodGenerator();
        $populate->setName('populate');
        $populate->setParameters([
            [
                'name' => 'data',
                'type' => 'array'
            ]
        ]);
        $populate->addFlag(MethodGenerator::FLAG_ABSTRACT);
        $generator->addMethodFromGenerator($populate);

        //addDocblocks
        if ($this->addDocblockTypes || $this->addDocblockDescriptions) {
            $constructDocblock = '';
            $populateDocblock = '';
            if ($this->addDocblockDescriptions) {
                $constructDocblock .= 'Create the entity using a population array';
                $populateDocblock .= 'Populate the entity';
            }
            if ($this->addDocblockTypes) {
                if (!empty($populateDocblock) && !empty($toArrayDocblock) && !empty($jsonSerializeDocblock)) {
                    $constructDocblock .= "\n\n";
                    $populateDocblock .= "\n\n";
                }
                $constructDocblock .= '@param array $data';
                $populateDocblock .= '@param array $data';
            }
            $construct->setDocBlock($constructDocblock);
            $populate->setDocBlock($populateDocblock);
        }

        $this->generatedEntities[$generator->getName()] = "<?php\n\n" . $generator->generate();
    }
}
