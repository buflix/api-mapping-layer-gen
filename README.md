# api-mapping-layer-gen
The library to generate the mapping layer for your API

## Supported input formats
* OpenApi
    * yml
    * json

## Supported output formats
* PHP
    * Native
        * produces "GeneratedEntites" which contains all generated funktionality
        * produces "Entities" which extends the GeneratedEntites and can be modified with custom functionalitiy
        * uses native php arrays rather than collection classes

## How to use
```shell
$ composer require cyberrebell/api-mapping-layer-gen
```

```php
<?php
require 'vendor/autoload.php';

use ApiMappingLayerGen\Mapper\OpenApi\Mapper;
use ApiMappingLayerGen\Generator\Php\EntityGenerator\Native;
use ApiMappingLayerGen\Generator\Php\EntityBuilder;

$file = 'api.yml';

$mapper = new Mapper($file);
$patterns = $mapper->getPatterns();

$entityBuilder = new EntityBuilder(new Native([
    'addDocblockDescriptions' => true,
    'useFluentSetters' => true
]));
$entityBuilder->buildEntities($patterns, 'App', 'src/App');
```
