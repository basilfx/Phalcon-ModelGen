<?php

namespace BasilFX\ModelGen\Field;

use BasilFX\ModelGen\Utils;
use BasilFX\ModelGen\AbstractField;

class Enum extends AbstractField
{
    public function getAnnotations($propertyName, $arguments)
    {
        ($propertyName);

        return [
            Utils::annotation("Column", array_merge($arguments, ["type" => "string"]))
        ];
    }

    public function getClassMethods($propertyName, $arguments)
    {
        if (!is_array($arguments["choices"])) {
            throw new Exception("Enum choices must be an array.");
        }

        $choices = $arguments["choices"];
        $camelCasedPropertyName = ucfirst($propertyName);

        // Add null as a choice, if field allows null values.
        if (array_get($arguments, "nullable")) {
            $choices[] = null;
        }

        $choices = var_export($choices, true);

        return Utils::classMethods(<<<EOD
            /**
             * @Validator
             */
            public function validate{$camelCasedPropertyName}(\$validator)
            {
                \$validator->add(
                    "{$propertyName}",
                    new \Phalcon\Validation\Validator\InclusionIn([
                        "model" => \$this,
                        "domain" => {$choices}
                    ])
                );
            }
EOD
        );
    }
}
