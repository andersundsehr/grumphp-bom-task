<?php

declare(strict_types=1);

namespace PLUS\GrumPHPBomTask;

use GrumPHP\Extension\ExtensionInterface;

class ExtensionLoader implements ExtensionInterface
{
    public function imports(): iterable
    {
        yield __DIR__ . '/../Services.yaml';
    }
}
