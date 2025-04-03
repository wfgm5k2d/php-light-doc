<?php

namespace Wfgm5k2d\PhpLightDoc\Attributes;

use Attribute;

#[Attribute]
class DocGName
{
    public function __construct(public string $docGName)
    {
    }
}
