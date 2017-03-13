<?php

namespace BasilFX\ModelGen\Field;

use BasilFX\ModelGen\Utils;
use BasilFX\ModelGen\AbstractField;

// @codingStandardsIgnoreStart
class String_ extends AbstractField
// @codingStandardsIgnoreEnd
{
    public function getAnnotations($propertyName, $arguments)
    {
        ($propertyName);

        return [
            Utils::annotation("Column", array_merge($arguments, ["type" => "text"]))
        ];
    }
}
