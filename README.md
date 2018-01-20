# api-mapping-layer-gen

## Purpose 

Library to generate an mapping interface based on an given API definition.

Useful for the initial setup of a new consumer application that needs to handle an format mapping
of an already existing API / or an service that shall implement an API based on a definition. 

## Supported input definitions

* [OpenAPI](https://www.openapis.org/)
    * `.yml`
    * `.json`

## PHP

### Install

```sh
$ composer require cyberrebell/api-mapping-layer-gen
```

### Usage

#### Entities
```php
<?php
require 'vendor/autoload.php';

use ApiMappingLayerGen\Mapper\OpenApi\Mapper;
use ApiMappingLayerGen\Generator\Php\EntityGenerator\Native;
use ApiMappingLayerGen\Generator\Php\EntityBuilder;

$file = '/path/to/api.yml';

$mapper = new Mapper($file);
$patterns = $mapper->getPatterns();

$entityBuilder = new EntityBuilder(new Native([
    'addDocblockTypes' => true,
    'addDocblockDescriptions' => false,
    'useFluentSetters' => false,
    'useSetterTypeHints' => true,
    'useGetterTypeHints' => true,
    'hideNullValues' => true
]));

$entityBuilder->buildEntities($patterns, 'App', 'src/App');
```
#### schema.json
```php
<?php
require 'vendor/autoload.php';

use ApiMappingLayerGen\Mapper\OpenApi\Mapper;
use ApiMappingLayerGen\Generator\JsonSchema\JsonSchemaBuilder;
use ApiMappingLayerGen\Generator\JsonSchema\OpenApi\JsonSchemaGenerator;

$file = '/path/to/api.yml';

$mapper = new Mapper($file);
$definition = $mapper->getDefinition();

$jsonBuilder = new JsonSchemaBuilder(new JsonSchemaGenerator());
$jsonBuilder->buildSchemas($definition, __DIR__ . '/../docs/json');
```

### Supported output formats

* PHP
    * Native
        * creates `GeneratedEntites` which contain all generated functionality expected of a mapping layer
        * creates `Entities` which extend the `GeneratedEntities` and can be used to store additional functionality
        * uses native PHP arrays rather than collection classes like
    * CollectionBased
        * Same as Native but uses collection classes and also generates `Collections` which can be extended with specific filter/search features
* json-schema (http://json-schema.org/)
    * Creates schema.json files which can be used for testing or validation purposes 

## License

MIT © Cyberrebell
