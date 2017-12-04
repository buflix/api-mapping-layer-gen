<?php

namespace ApiMappingLayerGen\Mapper\OpenApi;

use ApiMappingLayerGen\Mapper\CyclicDependencyException;
use ApiMappingLayerGen\Parser\YamlParser;

class ReferenceResolver
{
    const REF_DEPENDENCY_LIMIT = 5000;

    protected $definitions = [];
    protected $trackedRefs = [];

    protected $incomplete = true;

    public function resolveAllReferences(array $definition, string $currentFile) : array
    {
        while ($this->incomplete) {
            $this->incomplete = false;
            $this->trackedRefs = [];
            $definition = $this->resolveAllReferencesRec($definition, $currentFile);
            $this->definitions[$this->getCleanFilename($currentFile)] = $definition;
        }

        return $definition;
    }

    public function resolveRefString(string $refString, string $currentFile) : ?Reference
    {
        $sharpPos = strpos($refString, '#');
        if ($sharpPos !== false) {
            $file = substr($refString, 0, $sharpPos);
            if (empty($file)) {
                $file = $currentFile;
            }
            $ref = substr($refString, $sharpPos + 2);
        } else {
            $file = $refString;
            $ref = null;
        }

        return $this->resolveReference($file, $ref);
    }

    public function resolveReference(string $file, ?string $ref = null) : ?Reference
    {
        $file = $this->getCleanFilename($file);

        if (isset($this->definitions[$file])) {
            $definition = $this->definitions[$file];
        } else {
            $fileContent = file_get_contents($file);

            $fileEnding = substr($file, strpos($file, '.') + 1);
            if (
                $fileEnding == 'yml'
                || $fileEnding == 'yaml'
            ) {
                $definition = YamlParser::parse($fileContent);
            } elseif ($fileEnding == 'json') {
                $definition = json_decode($fileContent, true);
            } else {
                $definition = YamlParser::parse($fileContent);
            }

            $this->definitions[$file] = $definition;
        }

        $reference = new Reference();
        $target = $definition;
        if (!empty($ref)) {
            $refSegments = explode('/', $ref);
            foreach ($refSegments as $refSegment) {
                if (!isset($target[$refSegment])) {
                    if (isset($target['definitions']) && isset($target['definitions'][$refSegment])) {
                        $target = $target['definitions'];
                    } else {
                        return null;
                    }
                }
                $target = $target[$refSegment];
            }
            $reference->setRefKey($refSegment);
        }
        $reference->setRefFile($file);
        $reference->setValue($target);

        return $reference;
    }

    protected function resolveAllReferencesRec(array $definition, string $currentFile) : array
    {
        foreach ($definition as $key => $subDef) {
            if (count($definition) == 1 && $key === '$ref') {
                $targetRef = $this->resolveRefString($subDef, $currentFile);
                if (!$targetRef instanceof Reference) {
                    $this->incomplete = true;
                    continue;
                }
                $targetRefKey = $targetRef->getRefKey();

                if (!empty($targetRefKey)) {
                    $this->trackRef($targetRefKey);    //detect cyclic dependencies
                }

                $definition = array_replace_recursive($definition, $targetRef->getValue());
                foreach ($definition as $subKey => $subSubDef) {
                    if (is_array($subSubDef)) {
                        $definition[$subKey] = $this->resolveAllReferencesRec($subSubDef, $currentFile);
                    }
                }
            } elseif (is_array($definition[$key])) {
                $definition[$key] = $this->resolveAllReferencesRec($subDef, $currentFile);
            }
        }
        return $definition;
    }

    protected function trackRef(string $targetRefKey)
    {
        if (isset($this->trackedRefs[$targetRefKey])) {
            $this->trackedRefs[$targetRefKey] += 1;
            if ($this->trackedRefs[$targetRefKey] > self::REF_DEPENDENCY_LIMIT) {
                $responsibleDefinitions = [];
                foreach ($this->trackedRefs as $name => $count) {
                    if ($count > self::REF_DEPENDENCY_LIMIT / 2) {
                        $responsibleDefinitions[] = $name;
                    }
                }
                throw new CyclicDependencyException('Detected cyclic dependency between the following models in your definition: ' . implode(', ', $responsibleDefinitions));
            }
        } else {
            $this->trackedRefs[$targetRefKey] = 1;
        }
    }

    protected function getCleanFilename(string $file) : string
    {
        return realpath(ltrim($file, '/'));
    }
}