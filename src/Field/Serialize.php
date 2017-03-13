<?php

namespace BasilFX\ModelGen\Field;

use BasilFX\ModelGen\Utils;
use BasilFX\ModelGen\AbstractField;

class Serialize extends AbstractField
{
    public function getAnnotations($propertyName, $arguments)
    {
        ($propertyName);

        return [
            Utils::annotation("Column", array_merge($arguments, ["type" => "text"]))
        ];
    }

    public function getClassMethods($propertyName, $arguments)
    {
        ($arguments);

        return Utils::classMethods(<<<EOD
            public function beforeSave()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = serialize(\$this->{$propertyName});
                }
            }
            public function afterSave()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = unserialize(\$this->{$propertyName});
                }
            }
            public function afterFetch()
            {
                if (\$this->{$propertyName} !== null) {
                    \$this->{$propertyName} = unserialize(\$this->{$propertyName});
                }
            }
EOD
        );
    }
}
