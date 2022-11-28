<?php
declare(strict_types=1);

namespace App\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DiscriminatorDefault
{
    public function __construct(public readonly string $class)
    {
    }
}