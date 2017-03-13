<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Exception;
use BasilFX\ModelGen\Field;
use BasilFX\ModelGen\Utils;

class Fields extends AbstractGenerator
{
    /**
     * @var array Mapping of annotation type to field class.
     */
    private $fields = [];

    public function __construct()
    {
        $this->fields = [
            "BooleanField" => new Field\Boolean(),
            "DateField" => new Field\Date(),
            "DateTimeField" => new Field\DateTime(),
            "DecimalField" => new Field\Decimal(),
            "FloatField" => new Field\Float_(),
            "EnumField" => new Field\Enum(),
            "IdentifierField" => new Field\Identifier(),
            "IntegerField" => new Field\Integer(),
            "JsonField" => new Field\Json(),
            "SerializeField" => new Field\Serialize(),
            "StringField" => new Field\String_()
        ];
    }

    /**
     *
     */
    public function parsePropertyAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();
            $arguments = $annotation->getArguments();

            if (substr_endswith($name, "Field")) {
                if (!isset($this->fields[$name])) {
                    throw new Exception("Unknown field '$name'.");
                }

                $meta["field"] = [
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
    public function getPropertyAnnotations($meta)
    {
        if (isset($meta["field"])) {
            $generator = $this->fields[$meta["field"]["type"]];

            return $generator->getAnnotations(
                $meta["name"],
                $meta["field"]["arguments"]
            );
        }
    }

    /**
     *
     */
    public function getClassMethods($meta)
    {
        $methods = [];

        foreach ($meta["properties"] as $propertyMeta) {
            if (isset($propertyMeta["field"])) {
                // Add field specific methods.
                $generator = $this->fields[$propertyMeta["field"]["type"]];

                $result = $generator->getClassMethods(
                    $propertyMeta["name"],
                    $propertyMeta["field"]["arguments"]
                );

                if ($result) {
                    $methods = array_merge($methods, $result);
                }

                // Add field generic methods.
                if (isset($propertyMeta["field"]["arguments"]["default"])) {
                    $default = var_export($propertyMeta["field"]["arguments"]["default"], true);
                    $result = Utils::classMethods(<<<EOD
                        public function onConstruct()
                        {
                            \$this->{$propertyMeta["name"]} = {$default};
                        }
EOD
                    );

                    if ($result) {
                        $methods = array_merge($methods, $result);
                    }
                }

                if (isset($propertyMeta["field"]["arguments"]["remap"])) {
                    $remap = var_export([
                        $propertyMeta["field"]["arguments"]["remap"] => $propertyMeta["name"]
                    ], true);
                    $result = Utils::classMethods(<<<EOD
                        public function columnMap()
                        {
                            return {$remap};
                        }
EOD
                    );

                    if ($result) {
                        $methods = array_merge($methods, $result);
                    }
                }
            }
        }

        return $methods;
    }
}
