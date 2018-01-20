<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

abstract class AbstractEntityGenerator implements EntityGeneratorInterface
{
    const ABSTRACT_ENTITY_NAME = 'AbstractGeneratedEntity';

    /**
     * If true @param and @return statements will be added to the entites docblocks to give typehints
     *
     * @var bool
     */
    protected $addDocblockTypes;
    /**
     * If true the methods will get short description texts
     *
     * @var bool
     */
    protected $addDocblockDescriptions;
    /**
     * If true setters will return $this to allow chained calls
     *
     * @var bool
     */
    protected $useFluentSetters;
    /**
     * If true php typehints will be used to force strict types for setters
     *
     * @var bool
     */
    protected $useSetterTypeHints;
    /**
     * If true php typehints will be used to force strict types for getters
     *
     * @var bool
     */
    protected $useGetterTypeHints;
    /**
     * If true the toArray() function or json serialisation will completely leave properties which are null
     *
     * @var bool
     */
    protected $hideNullValues;

    protected $generatedEntities = [];
    protected $entities = [];
    protected $collections = [];

    /**
     * Create the generator with the given settings
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->addDocblockTypes = $settings['addDocblockTypes'] ?? true;
        $this->addDocblockDescriptions = $settings['addDocblockDescriptions'] ?? false;
        $this->useFluentSetters = $settings['useFluentSetters'] ?? false;
        $this->useSetterTypeHints = $settings['useSetterTypeHints'] ?? true;
        $this->useGetterTypeHints = $settings['useGetterTypeHints'] ?? true;
        $this->hideNullValues = $settings['hideNullValues'] ?? true;
    }

    /**
     * @return array
     */
    public function getGeneratedEntities() : array
    {
        return $this->generatedEntities;
    }

    /**
     * @return array
     */
    public function getEntities() : array
    {
        return $this->entities;
    }

    /**
     * @return array
     */
    public function getCollections() : array
    {
        return $this->collections;
    }

    /**
     * Generates the AbstractGeneratedEntity class which is extended by all GeneratedEntity classes
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

        //add toArray
        $toArray = new MethodGenerator();
        $toArray->setName('toArray');
        $toArray->addFlag(MethodGenerator::FLAG_ABSTRACT);
        $toArray->setReturnType('array');
        $generator->addMethodFromGenerator($toArray);

        //add jsonSerialize
        $jsonSerialize = new MethodGenerator();
        $jsonSerialize->setName('jsonSerialize');
        $jsonSerialize->setReturnType('array');
        $jsonSerialize->setBody('return $this->toArray();');
        $generator->addMethodFromGenerator($jsonSerialize);

        //addDocblocks
        if ($this->addDocblockTypes || $this->addDocblockDescriptions) {
            $constructDocblock = '';
            $populateDocblock = '';
            $toArrayDocblock = '';
            $jsonSerializeDocblock = '';
            if ($this->addDocblockDescriptions) {
                $constructDocblock .= 'Create the entity using a population array';
                $populateDocblock .= 'Populate the entity';
                $toArrayDocblock .= 'Get entity data as array';
                $jsonSerializeDocblock .= 'Provide entity data for json serialisation';
            }
            if ($this->addDocblockTypes) {
                if (!empty($populateDocblock) && !empty($toArrayDocblock) && !empty($jsonSerializeDocblock)) {
                    $constructDocblock .= "\n\n";
                    $populateDocblock .= "\n\n";
                    $toArrayDocblock .= "\n\n";
                    $jsonSerializeDocblock .= "\n\n";
                }
                $constructDocblock .= '@param array $data';
                $populateDocblock .= '@param array $data';
                $toArrayDocblock .= '@return array';
                $jsonSerializeDocblock .= '@return array';
            }
            $construct->setDocBlock($constructDocblock);
            $populate->setDocBlock($populateDocblock);
            $toArray->setDocBlock($toArrayDocblock);
            $jsonSerialize->setDocBlock($jsonSerializeDocblock);
        }

        $this->generatedEntities[$generator->getName()] = "<?php\n\n" . $generator->generate();
    }

    /**
     * Created entities will be added through this method to the collection of completed entities
     * Allows to make some settings for all generated entities
     *
     * @param ClassGenerator $parentGenerator
     * @param string $name
     * @param string $namespace
     */
    protected function addChildEntity(ClassGenerator $parentGenerator, string $name, string $namespace)
    {
        $entityGenerator = new ClassGenerator();
        $entityGenerator->setName($name);
        $entityGenerator->setNamespaceName($namespace);
        $entityGenerator->setExtendedClass($parentGenerator->getNamespaceName() . '\\' . $parentGenerator->getName());
        $this->entities[$name] = "<?php\n\n" . $entityGenerator->generate();
    }

    /**
     * Created properties will be added through this method to the entities. Docblocks are added here
     *
     * @param ClassGenerator $generator
     * @param PropertyPattern $property
     * @param string $type
     */
    protected function addProperty(ClassGenerator $generator, PropertyPattern $property, string $type)
    {
        $upperName = $property->getUpperCamelCaseName();
        $lowerName = $property->getLowerCamelCaseName();

        //add the property
        $propertyGenerator = new PropertyGenerator($lowerName, null, PropertyGenerator::FLAG_PROTECTED);
        $propertyDocblock = '@var null|' . $type;
        if ($property->getDescription() !== null) {
            $propertyDocblock = $property->getDescription() . "\n\n" . $propertyDocblock;
        }
        $propertyGenerator->setDocBlock($propertyDocblock);
        $generator->addPropertyFromGenerator($propertyGenerator);


        //add the setter
        $setter = new MethodGenerator();
        $setter->setName('set' . $upperName);
        $setterDefinition = [
            'name' => $property->getLowerCamelCaseName()
        ];
        if ($this->useSetterTypeHints) {
            $setterDefinition['type'] = '?' . $type;
        }
        $setter->setParameters([
            $property->getLowerCamelCaseName() => $setterDefinition
        ]);
        $setter->setBody($this->createSetterBody($lowerName));
        $generator->addMethodFromGenerator($setter);

        //add the getter
        $getter = new MethodGenerator();
        $getter->setName('get' . $upperName);
        if ($this->useGetterTypeHints) {
            $getter->setReturnType('?' . $type);
        }
        $getter->setBody($this->createGetterBody($lowerName));
        $generator->addMethodFromGenerator($getter);

        //addDocblocks
        if ($this->addDocblockTypes || $this->addDocblockDescriptions) {
            $setterDocblock = '';
            $getterDocblock = '';
            if ($this->addDocblockDescriptions) {
                $setterDocblock .= 'Set the ' . $lowerName;
                $getterDocblock .= 'Get the ' . $lowerName;
            }
            if ($this->addDocblockTypes) {
                if (!empty($setterDocblock) && !empty($getterDocblock)) {
                    $setterDocblock .= "\n\n";
                    $getterDocblock .= "\n\n";
                }
                $setterDocblock .= '@param null|' . $type . ' $' . $lowerName;
                if ($this->useFluentSetters) {
                    $setterDocblock .= "\n" . '@return $this';
                }
                $getterDocblock .= '@return null|' . $type;
            }
            $setter->setDocBlock($setterDocblock);
            $getter->setDocBlock($getterDocblock);
        }
    }

    /**
     * Creates the body of a simple setter
     *
     * @param string $propertyName
     * @return string
     */
    protected function createSetterBody(string $propertyName)
    {
        $body = '$this->' . $propertyName . ' = $' . $propertyName . ';';
        if ($this->useFluentSetters) {
            $body .= "\n\n" . 'return $this;';
        }
        return $body;
    }

    /**
     * Creates the body of a simple getter
     *
     * @param string $propertyName
     * @return string
     */
    protected function createGetterBody(string $propertyName)
    {
        return 'return $this->' . $propertyName . ';';
    }
}
