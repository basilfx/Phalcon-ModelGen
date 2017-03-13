<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Utils;

class GettersSetters extends AbstractGenerator
{
    /**
     *
     */
    public function parsePropertyAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = function () use ($annotation, $meta) {
                return Utils::resolveArguments($annotation->getArguments(), [
                    "get" => ["required" => false],
                    "set" => ["required" => false]
                ]);
            };

            if ($name == "GetSet") {
                $meta["getter"] = $arguments();
                $meta["setter"] = $arguments();
            } elseif ($name == "Get") {
                $meta["getter"] = $arguments();
            } elseif ($name == "Set") {
                $meta["setter"] = $arguments();
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

        // Generate getter and setter methods.
        foreach ($meta["properties"] as $propertyMeta) {
            $propertyName = $propertyMeta["name"];
            $camelCasedPropertyName = ucfirst($propertyName);

            if (array_key_exists("getter", $propertyMeta)) {
                if (isset($propertyMeta["getter"]["get"])) {
                    $getter = $propertyMeta["getter"]["get"];
                } else {
                    $getter = "get{$camelCasedPropertyName}";
                }

                $methods[] = Utils::classMethodUnique(<<<EOD
                    public function {$getter}()
                    {
                        return \$this->{$propertyName};
                    }
EOD
                );
            }

            if (array_key_exists("setter", $propertyMeta)) {
                if (isset($propertyMeta["setter"]["set"])) {
                    $setter = $propertyMeta["setter"]["set"];
                } else {
                    $setter = "set{$camelCasedPropertyName}";
                }

                $methods[] = Utils::classMethodUnique(<<<EOD
                    public function {$setter}(\${$propertyName})
                    {
                        \$this->{$propertyName} = \${$propertyName};
                    }
EOD
                );
            }
        }

        return $methods;
    }
}
