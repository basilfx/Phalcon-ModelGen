<?php

namespace BasilFX\ModelGen\Behavior;

use BasilFX\ModelGen\Utils;
use BasilFX\ModelGen\AbstractBehavior;
use PhpParser;

class ModificationTime extends AbstractBehavior
{
    public function getClassMethods($arguments)
    {
        $arguments = Utils::resolveArguments($arguments, [
            "field" => ["required" => true]
        ]);

        return Utils::classMethods(<<<EOD
            public function beforeValidationOnUpdate()
            {
                \$this->{$arguments["field"]} = new \DateTime();
            }
            public function isModified()
            {
                return \$this->{$arguments["field"]} !== null;
            }
EOD
        );
    }
}
