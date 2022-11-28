<?php
declare(strict_types=1);

namespace App\DTO;

use App\DTO\Details\ChildSecondDetail;

class ChildSecondRequest extends BaseRequest
{
    public function __construct(string $id, string $type, public readonly ChildSecondDetail $details)
    {
        parent::__construct($id, $type);
    }
}