<?php
declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Context;

class Root
{

    public function __construct(
        /** @var BaseRequest[] $requests */
        #[Context(context: ['not_normalizable_value_exceptions' => []])]
        public readonly array $requests)
    {
    }
}