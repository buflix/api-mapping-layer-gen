<?php

namespace ApiMappingLayerGen\Mapper\OpenApi;

/**
 * Class to handle the OpenApi 'allOf' keyword
 */
class AllOfResolver
{
    /**
     * Resolve all 'allOf' occurences and merge them as expected by OpenApi
     *
     * @param array $definition
     * @return array
     */
    public function resolveKeywordAllOf(array $definition)
    {
        foreach ($definition as $key => $subDef) {
            if ($key === 'allOf') {
                $mergeResult = [];
                foreach ($subDef as $mergeComponent) {
                    $mergeComponent = $this->resolveKeywordAllOf($mergeComponent);
                    $mergeResult = array_replace_recursive($mergeResult, $mergeComponent);
                }
                unset($definition[$key]);
                $definition = array_replace_recursive($definition, $mergeResult);
                if (isset($definition['$ref'])) {
                    unset($definition['$ref']);
                }
            } elseif (is_array($definition[$key])) {
                $definition[$key] = $this->resolveKeywordAllOf($subDef);
            }
        }
        return $definition;
    }
}