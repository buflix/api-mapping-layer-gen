<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

abstract class AbstractEntityGenerator implements EntityGeneratorInterface
{
    const ABSTRACT_ENTITY_NAME = 'AbstractGeneratedEntity';

    protected $addDocblockTypes;
    protected $addDocblockDescriptions;
    protected $useFluentSetters;

    protected $generatedEntities = [];
    protected $entities = [];
    protected $collections = [];

    public function __construct(array $settings = [])
    {
        $this->addDocblockTypes = $settings['addDocblockTypes'] ?? true;
        $this->addDocblockDescriptions = $settings['addDocblockDescriptions'] ?? false;
        $this->useFluentSetters = $settings['useFluentSetters'] ?? false;
    }

    public function getGeneratedEntities() : array
    {
        return $this->generatedEntities;
    }

    public function getEntities() : array
    {
        return $this->entities;
    }

    public function getCollections() : array
    {
        return $this->collections;
    }

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

    protected function addChildEntity(ClassGenerator $parentGenerator, string $name, string $namespace)
    {
        $entityGenerator = new ClassGenerator();
        $entityGenerator->setName($name);
        $entityGenerator->setNamespaceName($namespace);
        $entityGenerator->setExtendedClass($parentGenerator->getNamespaceName() . '\\' . $parentGenerator->getName());
        $this->entities[$name] = "<?php\n\n" . $entityGenerator->generate();
    }

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
        $setter->setParameters([
            $property->getLowerCamelCaseName() => [
                'name' => $property->getLowerCamelCaseName(),
                'type' => '?' . $type
            ]
        ]);
        $setter->setBody($this->createSetterBody($lowerName));
        $generator->addMethodFromGenerator($setter);

        //add the getter
        $getter = new MethodGenerator();
        $getter->setName('get' . $upperName);
        $getter->setReturnType('?' . $type);
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
                    $setterDocblock .= "\n" . '@return self';
                }
                $getterDocblock .= '@return null|' . $type;
            }
            $setter->setDocBlock($setterDocblock);
            $getter->setDocBlock($getterDocblock);
        }
    }

    protected function createSetterBody(string $propertyName)
    {
        $body = '$this->' . $propertyName . ' = $' . $propertyName . ';';
        if ($this->useFluentSetters) {
            $body .= "\n\n" . 'return $this;';
        }
        return $body;
    }

    protected function createGetterBody(string $propertyName)
    {
        return 'return $this->' . $propertyName . ';';
    }
}
