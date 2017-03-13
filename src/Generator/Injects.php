<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Exception;
use BasilFX\ModelGen\Utils;

class Injects extends AbstractGenerator
{
    /**
     *
     */
    public function parseClassAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = function () use ($annotation) {
                return Utils::resolveArguments($annotation->getArguments(), [
                    "class" => ["required" => true]
                ]);
            };

            if ($name == "Inject") {
                $meta["inject"][] = $arguments();
            }
        }

        return $meta;
    }

    /**
     *
     */
    public function getClassMethods($meta)
    {
        $methods = [];

        if (isset($meta["inject"])) {
            foreach ($meta["inject"] as $inject) {
                $generator = $this->getGenerator($inject["class"]);

                $result = $generator->getClassMethods($inject);

                if ($result) {
                    $methods = array_merge($methods, $result);
                }
            }
        }

        return $methods;
    }

    /**
     * Resolve the given class to a generator instance. Ensures that the result
     * is a behaviour generator instance. Otherwise, an exception is thrown.
     *
     * @param string $class The class to load.
     * @return AbstractBehaviour A generator instance.
     */
    private function getGenerator($class)
    {
        $instance = new $class();

        if ($instance instanceof AbstractBehaviour) {
            throw new Exception(
                "Injected class must be an AbstractBehaviour."
            );
        }

        return $instance;
    }
}
