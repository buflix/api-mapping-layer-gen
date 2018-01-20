<?php

namespace ApiMappingLayerGen\Generator\Common;

class TargetDirectory
{
    /**
     * Validate if target directory exists
     * return canonical directory path if exists
     *
     * @param string $targetDirectory
     * @return string
     * @throws \Exception if directory not exists
     */
    public static function getCanonicalTargetDirectory(string $targetDirectory) : string
    {
        $targetDirectoryCanonical = realpath($targetDirectory);
        if (!is_dir($targetDirectoryCanonical)) {
            //no mkdir to avoid generation into unwanted locations because of unintended $targetDirectory
            throw new \Exception('Generator target dir "' . $targetDirectory . '/" does not exist. Create it first!');
        }
        return $targetDirectoryCanonical . '/';
    }
}
