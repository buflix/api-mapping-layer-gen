<?php

namespace ApiMappingLayerGen\Mapper\Pattern;

class AssocPattern extends PropertyPattern
{
    /**
     * @var PropertyPattern
     */
    protected $contentProperty;

    /**
     * @return PropertyPattern
     */
    public function getContentProperty() : ?PropertyPattern
    {
        return $this->contentProperty;
    }

    /**
     * @param PropertyPattern $contentProperty
     */
    public function setContentProperty(PropertyPattern $contentProperty)
    {
        $this->contentProperty = $contentProperty;
    }
}
