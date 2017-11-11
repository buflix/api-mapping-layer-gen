<?php

namespace ApiMappingLayerGen\Mapper\Pattern;

class EntityPattern extends PropertyPattern
{
    protected $type = 'object';
    /**
     * @var array
     */
    protected $properties = [];

    public function addProperty(PropertyPattern $property)
    {
        $this->properties[] = $property;
    }

    public function getProperties() : array
    {
        return $this->properties;
    }
}
