<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Behavior;
use BasilFX\ModelGen\Exception;

class Behaviors extends AbstractGenerator
{
    /**
     * @var array Mapping of annotation type to behavior class.
     */
    private $behaviors = [];

    public function __construct()
    {
        $this->behaviors = [
            "CreationTimeBehavior" => new Behavior\CreationTime(),
            "ModificationTimeBehavior" => new Behavior\ModificationTime(),
            "SoftDeleteBehavior" => new Behavior\SoftDelete()
        ];
    }

    /**
     *
     */
    public function parseClassAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = $annotation->getArguments();

            if (substr_endswith($name, "Behavior")) {
                if (!isset($this->behaviors[$name])) {
                    throw new Exception("Unknown behavior '$name'.");
                }

                $meta["behaviors"][] = [
                    "type" => $name,
                    "arguments" => (array) $arguments
                ];
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

        if (isset($meta["behaviors"])) {
            foreach ($meta["behaviors"] as $behavior) {
                $generator = $this->behaviors[$behavior["type"]];

                $result = $generator->getClassMethods($behavior["arguments"]);

                if ($result) {
                    $methods = array_merge($methods, $result);
                }
            }
        }

        return $methods;
    }
}
