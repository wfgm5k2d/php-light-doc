<?php

namespace Wfgm5k2d\PhpLightDoc\Attributes;

use Attribute;

#[Attribute]
class DocResponseCodes
{
    public function __construct(public array $codes)
    {
    }
}
