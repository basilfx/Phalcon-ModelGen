<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Utils;

class Table extends AbstractGenerator
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
                    "source" => ["required" => false]
                ]);
            };

            if ($name == "Table") {
                $meta["table"] = $arguments();
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

        if (isset($meta["table"])) {
            if (isset($meta["table"]["source"])) {
                $source = var_export($meta["table"]["source"], true);

                $methods[] = Utils::classMethod(<<<EOD
                    public function initialize()
                    {
                        \$this->setSource({$source});
                    }
EOD
                );
            }
        }

        return $methods;
    }
}
