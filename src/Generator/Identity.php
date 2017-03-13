<?php

namespace BasilFX\ModelGen\Generator;

use BasilFX\ModelGen\AbstractGenerator;
use BasilFX\ModelGen\Utils;

class Identity extends AbstractGenerator
{
    /**
     *
     */
    public function parsePropertyAnnotations($annotations, $meta)
    {
        foreach ($annotations as $annotation) {
            $name = $annotation->getName();

            if ($name == "Identity") {
                $meta["identity"] = true;
            }
        }

        return $meta;
    }
}
