<?php

namespace Wfgm5k2d\PhpLightDoc\Attributes;

use Attribute;

#[Attribute]
class DocMiddleware
{
    public function __construct(public string $name, public string $value)
    {
    }
}
