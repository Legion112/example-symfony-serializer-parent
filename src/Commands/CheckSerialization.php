<?php
declare(strict_types=1);

namespace App\Commands;

use App\DTO\BaseRequest;
use App\DTO\ChildRequest;
use App\DTO\ChildSecondRequest;
use App\DTO\DefaultStructure;
use App\DTO\Details\ChildDetails;
use App\DTO\Details\ChildSecondDetail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: "check:serialization")]
class CheckSerialization extends Command
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct(null);
        $this->serializer = $serializer;
    }

    public function execute(InputInterface $input, OutputInterface $output):int
    {
        $childRequest = new ChildSecondRequest('143234', 'child', new ChildSecondDetail('else'));
        $parentRequest = new DefaultStructure('134324', 'otherType');
        $another = new ChildRequest('134324', 'otherType', new ChildDetails('I am '));
        dd(
            $this->serializer->serialize($childRequest, 'json'),
            $this->serializer->serialize($parentRequest, 'json'),
            $this->serializer->serialize($another, 'json'),
        );

        return self::SUCCESS;
    }
}