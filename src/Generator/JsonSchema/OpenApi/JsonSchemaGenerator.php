<?php

namespace ApiMappingLayerGen\Generator\JsonSchema\OpenApi;

use ApiMappingLayerGen\Generator\JsonSchema\SchemaContainer;

class JsonSchemaGenerator
{
    /**
     * The created json schema draft
     *
     * @var string
     */
    const JSON_SCHEMA_DRAFT = 'http://json-schema.org/draft-07/schema#';
    /**
     * Only request or response bodys with a content type in this list will be used
     *
     * @var array
     */
    const SUPPORTED_CONTENT_TYPES = [
        'application/json'
    ];

    /**
     * Method to process path defintion and create the json schema as array
     *
     * @param string $path
     * @param array $pathDefinition
     * @return array
     */
    public function processPath(string $path, array $pathDefinition) : array
    {
        $resourceName = $this->getResourceName($path);
        $schemaContainers = [];

        foreach ($pathDefinition as $httpMethod => $requestDefinition) {
            $schemaContainers = $this->processBody(
                $schemaContainers,
                $requestDefinition['requestBody']['content'] ?? [],
                $resourceName . '/' . $httpMethod . '/request'
            );

            foreach ($requestDefinition['responses'] ?? [] as $responseCode => $contentDefinition) {
                $schemaContainers = $this->processBody(
                    $schemaContainers,
                    $contentDefinition['content'] ?? [],
                    $resourceName . '/' . $httpMethod . '/response/' . $responseCode
                );
            }
        }

        return $schemaContainers;
    }

    /**
     * Process a body (request or response)
     * Creates all SchemaContainer objects needed and adds the to $schemaContainers
     *  returns the updated SchemaContainers
     *
     * @param array $schemaContainers
     * @param array $contentDefinition
     * @param string $path
     * @return array
     */
    protected function processBody(array $schemaContainers, array $contentDefinition, string $path)
    {
        foreach ($contentDefinition as $contentTypeName => $contentType) {
            if (!in_array($contentTypeName, self::SUPPORTED_CONTENT_TYPES, true)) {
                continue;   //skip non-supported content types
            }

            $bodyDefinition = $contentType['schema'] ?? null;
            if ($bodyDefinition !== null) {
                $schemaContainer = $this->buildSchemaContainer($bodyDefinition);
                $schemaContainer->setPath($path);
                $schemaContainers[] = $schemaContainer;
            }
        }
        return $schemaContainers;
    }

    /**
     * Get the clean resource name without route params
     *
     * @param string $path
     * @return string
     */
    protected function getResourceName(string $path) : string
    {
        $validLiterals = [];
        foreach (explode('/', $path) as $segment) {
            if (
                empty($segment)
                || preg_match('/^\{.*\}$/', $segment)
            ) {
                continue;   //skip any {route-params}
            }
            $validLiterals[] = $segment;
        }
        return implode('/', $validLiterals);
    }

    /**
     * Creates a SchemaContainer object
     *  adds metadata
     *  resolves the $definition into the container
     *
     * @param array $definition
     * @return SchemaContainer
     */
    protected function buildSchemaContainer(array $definition) : SchemaContainer
    {
        $schemaContainer = new SchemaContainer();

        $schemaContainer->setSchema(
            array_merge(
                [
                    '$schema' => self::JSON_SCHEMA_DRAFT
                ],
                $this->resolveSchema($definition)
            )
        );

        return $schemaContainer;
    }

    /**
     * Resolves a schema or sub-schema
     *
     * @param array $definition
     * @return array
     */
    protected function resolveSchema(array $definition) : array
    {
        $type = $definition['type'] ?? null;
        switch ($type) {
            case 'object':
                $schema = $this->resolveObject($definition);
                break;
            case 'array':
                $schema = $this->resolveArray($definition);
                break;
            case 'string':
                $schema = $this->resolveString($definition);
                break;
            case 'integer':
                $schema = $this->resolveInteger($definition);
                break;
            case 'number':
                $schema = $this->resolveNumber($definition);
                break;
            case 'boolean':
                $schema = $this->resolveBoolean($definition);
                break;
            default:
                $schema = [];
                break;
        }

        $schema = $this->addDescription($schema, $definition);
        $schema = $this->addExamples($schema, $definition);
        return $schema;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function resolveObject(array $definition) : array
    {
        $schema = [
            'type' => $definition['type'],
            'description' => $definition['description'] ?? '',
            'required' => $definition['required'] ?? [],
            'properties' => []
        ];
        $properties = $definition['properties'] ?? [];
        foreach ($properties as $propertyName => $property) {
            $schema['properties'][$propertyName] = $this->resolveSchema($property);
        }

        return $schema;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function resolveArray(array $definition) : array
    {
        $schema = [
            'type' => $definition['type'],
            'items' => $this->resolveSchema($definition['items'] ?? [])
        ];

        return $schema;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function resolveString(array $definition) : array
    {
        $schema = [
            'type' => $definition['type']
        ];
        $schema = $this->addEnum($schema, $definition);
        $schema = $this->addLengthMinMax($schema, $definition);
        $schema = $this->addPattern($schema, $definition);

        return $schema;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function resolveInteger(array $definition) : array
    {
        $schema = [
            'type' => $definition['type']
        ];
        $schema = $this->addEnum($schema, $definition);
        $schema = $this->addMinMax($schema, $definition);

        return $schema;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function resolveNumber(array $definition) : array
    {
        $schema = [
            'type' => $definition['type']
        ];
        $schema = $this->addEnum($schema, $definition);
        $schema = $this->addMinMax($schema, $definition);

        return $schema;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function resolveBoolean(array $definition) : array
    {
        $schema = [
            'type' => $definition['type']
        ];

        return $schema;
    }

    /**
     * Adds the description to the schema if is set in the definition
     * returns the updated schema
     *
     * @param array $schema
     * @param array $definition
     * @return array
     */
    protected function addDescription(array $schema, array $definition) : array
    {
        if (isset($definition['description'])) {
            $schema['description'] = $definition['description'];
        }
        return $schema;
    }

    /**
     * Adds examples to the schema if they are set in the definition
     * returns the updated schema
     *
     * @param array $schema
     * @param array $definition
     * @return array
     */
    protected function addExamples(array $schema, array $definition) : array
    {
        if (isset($definition['example'])) {
            //OpenApi single example case
            $schema['examples'] = [$definition['example']];
        }
        return $schema;
    }

    /**
     * Adds enum to the schema if it is set in the definition
     * returns the updated schema
     *
     * @param array $schema
     * @param array $definition
     * @return array
     */
    protected function addEnum(array $schema, array $definition) : array
    {
        if (isset($definition['enum'])) {
            $schema['enum'] = $definition['enum'];
        }
        return $schema;
    }

    /**
     * Adds minimum and maximum to the schema if it is set in the definition
     * returns the updated schema
     *
     * @param array $schema
     * @param array $definition
     * @return array
     */
    protected function addMinMax(array $schema, array $definition) : array
    {
        if (isset($definition['minimum'])) {
            $schema['minimum'] = $definition['minimum'];
        }
        if (isset($definition['maximum'])) {
            $schema['maximum'] = $definition['maximum'];
        }
        return $schema;
    }

    /**
     * Adds minLength and maxLength to the schema if it is set in the definition
     * returns the updated schema
     *
     * @param array $schema
     * @param array $definition
     * @return array
     */
    protected function addLengthMinMax(array $schema, array $definition) : array
    {
        if (isset($definition['minLength'])) {
            $schema['minLength'] = $definition['minLength'];
        }
        if (isset($definition['maxLength'])) {
            $schema['maxLength'] = $definition['maxLength'];
        }
        return $schema;
    }

    /**
     * Adds pattern to the schema if it is set in the definition
     * returns the updated schema
     *
     * @param array $schema
     * @param array $definition
     * @return array
     */
    protected function addPattern(array $schema, array $definition) : array
    {
        if (isset($definition['pattern'])) {
            $schema['pattern'] = $definition['pattern'];
        }
        return $schema;
    }
}
