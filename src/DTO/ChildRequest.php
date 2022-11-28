<?php
declare(strict_types=1);

namespace App\DTO;

use App\DTO\Details\ChildDetails;

class ChildRequest extends BaseRequest
{
    public function __construct(string $id, string $type, public readonly ChildDetails $details)
    {
        parent::__construct($id, $type);
    }
}