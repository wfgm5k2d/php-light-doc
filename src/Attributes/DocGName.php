<?php

namespace Piratecode\PhpLightDoc\Attributes;

use Attribute;

#[Attribute]
class DocGName
{
    /**
     * @param  string  $name Название подгруппы (например, "Пользователь API")
     * @param  string|null  $group Название основной группы (например, "Пользователи"). Если null, используется $name.
     */
    public function __construct(
        public string $name,
        public ?string $group = null
    ) {
    }
}
