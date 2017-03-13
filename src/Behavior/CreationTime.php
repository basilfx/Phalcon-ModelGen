<?php

namespace BasilFX\ModelGen\Behavior;

use BasilFX\ModelGen\AbstractBehavior;
use BasilFX\ModelGen\Exception;
use BasilFX\ModelGen\Utils;

class CreationTime extends AbstractBehavior
{
    /**
     * @param $arguments
     */
    public function getClassMethods($arguments)
    {
        $arguments = Utils::resolveArguments($arguments, [
            "field" => ["required" => true]
        ]);

        return Utils::classMethods(<<<EOD
            public function beforeValidationOnCreate()
            {
                \$this->{$arguments["field"]} = new \DateTime();
            }
            public function isCreated()
            {
                return \$this->{$arguments["field"]} !== null;
            }
EOD
        );
    }
}
