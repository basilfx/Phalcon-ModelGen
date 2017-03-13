<?php

namespace BasilFX\ModelGen\Field;

use BasilFX\ModelGen\Utils;
use BasilFX\ModelGen\AbstractField;

class DateTime extends AbstractField
{
    public function getAnnotations($propertyName, $arguments)
    {
        ($propertyName);

        return [
            Utils::annotation("Column", array_merge($arguments, ["type" => "datetime"]))
        ];
    }

    public function getClassMethods($propertyName, $arguments)
    {
        ($arguments);

        return Utils::classMethods(<<<EOD
            public function beforeSave()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = \$this->{$propertyName}->format("Y-m-d H:i:s");
                }
            }
            public function afterSave()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = new \DateTime(\$this->{$propertyName});

                    \$errors = \DateTime::getLastErrors();

                    if (\$errors["warning_count"] > 0 || \$errors["error_count"] > 0) {
                        \$this->{$propertyName} = new \DateTime("1970-01-01 00:00:00");
                    }
                }
            }
            public function afterFetch()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = new \DateTime(\$this->{$propertyName});

                    \$errors = \DateTime::getLastErrors();

                    if (\$errors["warning_count"] > 0 || \$errors["error_count"] > 0) {
                        \$this->{$propertyName} = new \DateTime("1970-01-01 00:00:00");
                    }
                }
            }
EOD
        );
    }
}
