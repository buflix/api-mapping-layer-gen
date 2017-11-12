<?php

namespace ApiMappingLayerGen\Generator\Php\EntityGenerator;

use ApiMappingLayerGen\Generator\Php\TypesMapper;
use ApiMappingLayerGen\Mapper\Pattern\EntityPattern;
use ApiMappingLayerGen\Mapper\Pattern\PropertyPattern;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

class Native extends AbstractEntityGenerator implements EntityGeneratorInterface
{
    public function processPatterns(array $patterns, string $targetNamespace)
    {
        /* @var $pattern PropertyPattern */
        foreach ($patterns as $pattern) {
            if ($pattern instanceof EntityPattern) {
                $className = 'Generated' . $pattern->getName();
                $generatedEntityGenerator = new ClassGenerator();
                $generatedEntityGenerator->setName($className);
                $generatedEntityGenerator->setNamespaceName($targetNamespace . '\\' . self::NAMESPACE_GENERATED_ENTITIES);
                $generatedEntityGenerator->addFlag(ClassGenerator::FLAG_ABSTRACT);
                /* @var $property PropertyPattern */
                foreach ($pattern->getProperties() as $property) {
                    $this->addProperty($generatedEntityGenerator, $property, $targetNamespace);
                }
                $this->generatedEntities[$className] = "<?php\n\n" . $generatedEntityGenerator->generate();

                $this->addChildEntity($generatedEntityGenerator, $pattern->getName(), $targetNamespace . '\\' . self::NAMESPACE_ENTITIES);
            }
        }
    }

    protected function addChildEntity(ClassGenerator $parentGenerator, string $name, string $namespace)
    {
        $entityGenerator = new ClassGenerator();
        $entityGenerator->setName($name);
        $entityGenerator->setNamespaceName($namespace);
        $entityGenerator->setExtendedClass($parentGenerator->getNamespaceName() . '\\' . $parentGenerator->getName());
        $this->entities[$name] = "<?php\n\n" . $entityGenerator->generate();
    }

    protected function addProperty(ClassGenerator $generator, PropertyPattern $property, string $targetNamespace)
    {
        $upperName = $property->getUpperCamelCaseName();
        $lowerName = $property->getLowerCamelCaseName();
        $type = TypesMapper::mapType($property->getType());
        if ($property instanceof EntityPattern) {
            $type = '\\' . $targetNamespace . '\\' . self::NAMESPACE_ENTITIES . '\\' . $property->getName();
        }

        //add the property
        $generator->addProperty($lowerName, null, PropertyGenerator::FLAG_PROTECTED);

        //add the setter
        $setter = new MethodGenerator();
        $setter->setName('set' . $upperName);
        $setter->setParameters([
            $property->getLowerCamelCaseName() => [
                'name' => $property->getLowerCamelCaseName(),
                'type' => $type
            ]
        ]);
        $setter->setBody($this->createSetterBody($lowerName));
        $generator->addMethodFromGenerator($setter);

        //add the getter
        $getter = new MethodGenerator();
        $getter->setName('get' . $upperName);
        $getter->setReturnType($type);
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
                $setterDocblock .= '@param ' . $type . ' $' . $lowerName;
                if ($this->useFluentSetters) {
                    $setterDocblock .= "\n" . '@return self';
                }
                $getterDocblock .= '@return ' . $type;
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
