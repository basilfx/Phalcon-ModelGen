<?php

namespace BasilFX\ModelGen\Field;

use BasilFX\ModelGen\Utils;
use BasilFX\ModelGen\AbstractField;

/**
 * Database integer field with explicit casting to/from string.
 */
class Identifier extends AbstractField
{
    public function getAnnotations($propertyName, $arguments)
    {
        ($propertyName);

        return [
            Utils::annotation("Column", array_merge($arguments, ["type" => "integer"]))
        ];
    }

    public function getClassMethods($propertyName, $arguments)
    {
        ($arguments);

        return Utils::classMethods(<<<EOD
            public function beforeSave()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = intval(\$this->{$propertyName});
                }
            }
            public function afterSave()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = strval(\$this->{$propertyName});
                }
            }
            public function afterFetch()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = strval(\$this->{$propertyName});
                }
            }
EOD
        );
    }
}
