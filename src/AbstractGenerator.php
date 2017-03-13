<?php

namespace BasilFX\ModelGen;

abstract class AbstractGenerator
{

    /**
     * @param $annotations
     * @param $meta
     */
    public function parseClassAnnotations($annotations, $meta)
    {
    }

    /**
     * @param $annotations
     * @param $meta
     */
    public function parseClassMethodAnnotations($annotations, $meta)
    {
    }

    /**
     * @param $annotations
     * @param $meta
     */
    public function parsePropertyAnnotations($annotations, $meta)
    {
    }

    /**
     * @param $meta
     */
    public function getClassAnnotations($meta)
    {
    }

    /**
     * @param $meta
     */
    public function getClassMethodAnnotations($meta)
    {
    }

    /**
     * @param $meta
     */
    public function getPropertyAnnotations($meta)
    {
    }

    /**
     * @param $meta
     */
    public function getProperties($meta)
    {
    }

    /**
     * @param $meta
     */
    public function getClassMethods($meta)
    {
    }
}
