<?php
declare(strict_types=1);

namespace App\Commands;

use App\DTO\BaseRequest;
use App\DTO\ChildRequest;
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
        $childRequest = new ChildRequest('143234', 'child');
        $parentRequest = new BaseRequest('134324', 'otherType');
        dd(
            $this->serializer->serialize($childRequest, 'json'),
            $this->serializer->serialize($parentRequest, 'json')
        );

        return self::SUCCESS;
    }
}