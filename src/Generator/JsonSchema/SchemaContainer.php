<?php

namespace ApiMappingLayerGen\Generator\JsonSchema;

class SchemaContainer
{
    /**
     * @var string
     */
    protected $path;
    /**
     * @var array
     */
    protected $schema;


    /**
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @param array $schema
     */
    public function setSchema(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return array
     */
    public function getSchema() : array
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function getSchemaAsJson() : string
    {
        return json_encode($this->schema, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }
}
