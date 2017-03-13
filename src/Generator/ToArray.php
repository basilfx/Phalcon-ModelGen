<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Utils;

class ToArray extends AbstractGenerator
{
    /**
     *
     */
    public function parsePropertyAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = $annotation->getArguments();

            if ($name == "ToArray") {
                $meta["toArray"] = Utils::resolveArguments($arguments, [
                    "groups" => ["required" => true]
                ]);
            }
        }

        return $meta;
    }

    /**
     * Index all properties that have a ToArray annotation and generate a
     * {toArrayX}, {infoArrayX} and {metaArrayX} method.
     */
    public function getClassMethods($meta)
    {
        $toArrayMethods = [];

        foreach ($meta["properties"] as $propertyName => $propertyMeta) {
            if (isset($propertyMeta["toArray"])) {
                $groups = $propertyMeta["toArray"]["groups"];

                if (!is_array($groups)) {
                    $groups = [$groups];
                }

                foreach ($groups as $group) {
                    if (!isset($toArrayMethods[$group])) {
                        $toArrayMethods[$group] = [];
                    }

                    $toArrayMethods[$group][$propertyName] = $propertyMeta;
                }
            }
        }

        if ($toArrayMethods) {
            $methods = [];

            foreach ($toArrayMethods as $group => $values) {
                $camelCasedGroup = ucfirst($group);

                $properties = var_export(array_keys($values), true);
                $meta = var_export($values, true);

                $methods[] = Utils::classMethodUnique(<<<EOD
                    public function toArray{$camelCasedGroup}()
                    {
                        return \$this->toArray({$properties});
                    }
EOD
                );
                $methods[] = Utils::classMethodUnique(<<<EOD
                    public static function infoArray{$camelCasedGroup}()
                    {
                        return {$properties};
                    }
EOD
                );
                $methods[] = Utils::classMethodUnique(<<<EOD
                    public static function metaArray{$camelCasedGroup}()
                    {
                        return {$meta};
                    }
EOD
                );
            }

            return $methods;
        }
    }
}
