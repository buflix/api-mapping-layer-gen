<?php

namespace ApiMappingLayerGen\Mapper\OpenApi;

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
    public function getRefKey()
    {
        return $this->refKey;
    }

    /**
     * @return ?string
     */
    public function getRefFile()
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
