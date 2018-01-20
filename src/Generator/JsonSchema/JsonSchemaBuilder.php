<?php

namespace ApiMappingLayerGen\Generator\JsonSchema;

use ApiMappingLayerGen\Generator\Common\TargetDirectory;
use ApiMappingLayerGen\Generator\JsonSchema\OpenApi\JsonSchemaGenerator;

class JsonSchemaBuilder
{
    /**
     * @var JsonSchemaGenerator
     */
    protected $schemaGenerator;

    /**
     * @param JsonSchemaGenerator $schemaGenerator
     */
    public function __construct(JsonSchemaGenerator $schemaGenerator)
    {
        $this->schemaGenerator = $schemaGenerator;
    }

    /**
     * Create all schema.json files in $targetDirectory (recursive directory structure)
     *
     * @param array $definition
     * @param string $targetDirectory
     * @throws \Exception
     */
    public function buildSchemas(array $definition, string $targetDirectory)
    {
        $targetDirectory = TargetDirectory::getCanonicalTargetDirectory($targetDirectory);

        foreach ($definition['paths'] as $path => $pathDefinition) {
            $schemaContainers = $this->schemaGenerator->processPath($path, $pathDefinition);
            /* @var $schemaContainer SchemaContainer */
            foreach ($schemaContainers as $schemaContainer) {
                $directory = $targetDirectory . $schemaContainer->getPath() . '/';
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                $file = $directory . 'schema.json';
                file_put_contents($file, $schemaContainer->getSchemaAsJson());
            }
        }
    }
}
