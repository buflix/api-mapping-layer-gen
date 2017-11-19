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
    'addDocblockDescriptions' => true,
    'useFluentSetters' => true
]));

$entityBuilder->buildEntities($patterns, 'App', 'src/App');
```

### Supported output formats

* PHP
    * Native
        * creates `GeneratedEntites` which contain all generated functionality expected of a mapping layer
        * creates `Entities` which extend the `GeneratedEntities` and can be used to store additional functionality
        * uses native PHP arrays rather than collection classes like

## License

MIT © Cyberrebell
