<?php
declare(strict_types=1);

namespace App\Commands;

use App\DTO\BaseRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: "check:deserialization")]
class CheckDeserialization extends Command
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct(null);
        $this->serializer = $serializer;
    }

    public function execute(InputInterface $input, OutputInterface $output):int
    {
        $childRequest = $this->serializer->deserialize(<<<JSON
{"id":"sdafafpAUFS323","type":"child"}
JSON,
            BaseRequest::class,
            'json'
);
        $parentRequest = $this->serializer->deserialize(<<<JSON
{"id":"sdafafpAUFS323","type":"somethingElse"}
JSON,
            BaseRequest::class,
            'json'
        );

        dd(
            $childRequest,
            $this->serializer->serialize($childRequest, 'json'),
            $parentRequest,
            $this->serializer->serialize($parentRequest, 'json')
        );

        return self::SUCCESS;
    }
}