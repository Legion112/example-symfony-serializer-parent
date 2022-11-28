<?php
declare(strict_types=1);

namespace App\DTO;

use App\Attributes\DiscriminatorDefault;
use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[DiscriminatorDefault(class: DefaultStructure::class)]
#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'second' => ChildSecondRequest::class,
    'child' => ChildRequest::class,
])]
abstract class BaseRequest
{
    public function __construct(
        public readonly string $id,
        public readonly string $type
    )
    {
    }
}