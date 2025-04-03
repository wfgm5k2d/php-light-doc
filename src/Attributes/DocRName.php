<?php

namespace Wfgm5k2d\PhpLightDoc\Attributes;

use Attribute;

#[Attribute]
class DocRName
{
    public function __construct(public string $docRName)
    {
    }
}
