<?php

namespace BasilFX\ModelGen;

abstract class AbstractField
{
    /**
     * @param $propertyName
     * @param $arguments
     */
    public function getAnnotations($propertyName, $arguments)
    {
    }

    /**
     * @param $propertyName
     * @param $arguments
     */
    public function getClassMethods($propertyName, $arguments)
    {
    }
}
