<?php

namespace BasilFX\ModelGen\Behavior;

use BasilFX\ModelGen\AbstractBehavior;
use BasilFX\ModelGen\Utils;

class SoftDelete extends AbstractBehavior
{
    /**
     * @param $arguments
     */
    public function getClassMethods($arguments)
    {
        $arguments = Utils::resolveArguments($arguments, [
            "field" => ["required" => true]
        ]);

        $methods = Utils::classMethods(<<<EOD
            public function isDeleted()
            {
                return \$this->{$arguments["field"]} !== null;
            }
            public function beforeDelete()
            {
                \$this->skipOperation(true);

                if (!\$this->isDeleted()) {
                    \$clone = clone \$this;
                    \$clone->{$arguments["field"]} = new \DateTime();

                    if (!\$clone->save()) {
                        foreach (\$clone->getMessages() as \$message) {
                            \$this->appendMessage(\$message);
                        }

                        return false;
                    }

                    \$this->{$arguments["field"]} = \$clone->{$arguments["field"]};
                }
            }
            public function undelete()
            {
                \$this->{$arguments["field"]} = null;

                return \$this->save();
            }
EOD
        );

        // The isDeleted method is unique per instance.
        $methods[0]->unique = true;

        return $methods;
    }
}
