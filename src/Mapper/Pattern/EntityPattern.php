<?php

namespace ApiMappingLayerGen\Mapper\Pattern;

class EntityPattern extends PropertyPattern
{
    /**
     * @var string
     */
    protected $className;
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @param string $className
     */
    public function setClassName(string $className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName() : string
    {
        return $this->className;
    }

    /**
     * @param PropertyPattern $property
     */
    public function addProperty(PropertyPattern $property)
    {
        $this->properties[] = $property;
    }

    /**
     * @return array
     */
    public function getProperties() : array
    {
        return $this->properties;
    }
}
