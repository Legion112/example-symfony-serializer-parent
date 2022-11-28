<?php
declare(strict_types=1);

namespace App\DTO\Details;

class ChildDetails
{
    public function __construct(public readonly string $operation)
    {
    }
}