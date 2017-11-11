<?php

namespace ApiMappingLayerGen\Mapper\Pattern;

class PropertyPattern
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $type;

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }
}
