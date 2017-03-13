<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Utils;

class Related extends AbstractGenerator
{
    /**
     *
     */
    public function parsePropertyAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = $annotation->getArguments();

            if ($name == "BelongsTo") {
                $meta["related"] = [
                    "type" => "BelongsTo",
                    "arguments" => Utils::resolveArguments($arguments, [
                        "model" => ["required" => true],
                        "field" => ["required" => true],
                        "foreignKey" => [
                            "required" => false,
                            "default" => true
                        ],
                        "alias" => [
                            "required" => false,
                            "default" => false
                        ]
                    ])
                ];
            } elseif ($name == "HasMany") {
                $meta["related"] = [
                    "type" => "HasMany",
                    "arguments" => Utils::resolveArguments($arguments, [
                        "name" => ["required" => true],
                        "model" => ["required" => true],
                        "field" => ["required" => true],
                        "foreignKey" => [
                            "required" => false,
                            "default" => true
                        ]
                    ])
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
        $invokes = [];
        $methods = [];

        // Create list of invokes.
        foreach ($meta["properties"] as $propertyName => $propertyMeta) {
            $propertyName = var_export($propertyName, true);

            if (isset($propertyMeta["related"])) {
                $type = $propertyMeta["related"]["type"];
                $arguments = $propertyMeta["related"]["arguments"];

                if ($type == "BelongsTo") {
                    $model = var_export($arguments["model"], true);
                    $field = var_export($arguments["field"], true);

                    if ($arguments["alias"] !== false) {
                        $alias = var_export($arguments["alias"], true);
                    } else {
                        $alias = "''";
                    }

                    if (is_array($arguments["foreignKey"])) {
                        $foreignKey = var_export((array) $arguments["foreignKey"], true);
                    } else {
                        $foreignKey = var_export((bool) $arguments["foreignKey"], true);
                    }

                    $invokes[] = "\$this->belongsTo($propertyName, $model, $field, [ \"foreignKey\" => $foreignKey, \"alias\" => $alias ]);";
                } elseif ($type == "HasMany") {
                    $name = ucfirst($arguments["name"]);
                    $model = var_export($arguments["model"], true);
                    $field = var_export($arguments["field"], true);

                    if (is_array($arguments["foreignKey"])) {
                        $foreignKey = var_export((array) $arguments["foreignKey"], true);
                    } else {
                        $foreignKey = var_export((bool) $arguments["foreignKey"], true);
                    }

                    $invokes[] = "\$this->hasMany($propertyName, $model, $field, [ \"foreignKey\" => $foreignKey ]);";
                    $methods[] = Utils::classMethodUnique(<<<EOD
                        public function get{$name}(\$parameters = null)
                        {
                            return \$this->getRelated({$model}, \$parameters);
                        }
EOD
                    );
                }
            }
        }

        // Insert method that will invoke the other methods.
        if ($invokes) {
            $invokes = implode("\n", $invokes);

            $methods[] = Utils::classMethod(<<<EOD
                public function initialize()
                {
                    {$invokes}
                }
EOD
            );
        }

        return $methods;
    }
}
