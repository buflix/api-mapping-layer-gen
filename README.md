# api-mapping-layer-gen

> A library to generate a mapping layer for your API definition

## Purpose 

> todo

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
use ApiMappingLayerGen\Generator\Php\EntityGenerator\Plain;
use ApiMappingLayerGen\Generator\Php\EntityBuilder;

$file = '/path/to/api.yml';

$mapper = new Mapper($file);
$patterns = $mapper->getPatterns();

$entityBuilder = new EntityBuilder(new Plain([
    'addDocblockDescriptions' => true,
    'useFluentSetters' => true
]));

$entityBuilder->buildEntities($patterns, 'App', 'src/App');
```

### Supported output formats

* Plain
    * creates `GeneratedEntites` which contain all generated functionality expected of a mapping layer
    * creates `Entities` which extend the `GeneratedEntities` and can be used to store additional functionality
    * uses native PHP arrays rather than collection classes like

## License

MIT © Cyberrebell
