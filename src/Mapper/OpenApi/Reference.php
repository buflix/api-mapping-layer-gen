<?php

namespace ApiMappingLayerGen\Mapper\OpenApi;

/**
 * Container class that represents an OpenApi $ref
 */
class Reference
{
    protected $refKey;
    protected $refFile;
    protected $value;

    /**
     * @param string $refKey
     */
    public function setRefKey(string $refKey)
    {
        $this->refKey = $refKey;
    }

    /**
     * @param string $refFile
     */
    public function setRefFile(string $refFile)
    {
        $this->refFile = $refFile;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return ?string
     */
    public function getRefKey() : ?string
    {
        return $this->refKey;
    }

    /**
     * @return ?string
     */
    public function getRefFile() : ?string
    {
        return $this->refFile;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
