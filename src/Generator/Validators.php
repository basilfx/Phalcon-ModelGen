<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Utils;

class Validators extends AbstractGenerator
{
    /**
     *
     */
    public function parseClassMethodAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = function () use ($annotation) {
                return Utils::resolveArguments($annotation->getArguments());
            };

            if ($name == "Validator") {
                $meta["validator"] = $arguments();
            }
        }

        return $meta;
    }

    /**
     *
     */
    public function getClassMethods($meta)
    {
        $invokes = [];

        // Create list of invokes.
        foreach ($meta["methods"] as $methodName => $methodMeta) {
            if (isset($methodMeta["validator"])) {
                $invokes[] = "\$this->{$methodName}(\$validation);";
            }
        }

        // Insert method that will invoke the other methods.
        if ($invokes) {
            $invokes = implode("\n", $invokes);

            return Utils::classMethodsUnique(<<<EOD
                public function validation()
                {
                    \$validation = new \Phalcon\Validation();

                    {$invokes}

                    return \$this->validate(\$validation);
                }
EOD
            );
        }
    }
}
