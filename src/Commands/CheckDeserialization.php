<?php
declare(strict_types=1);

namespace App\Commands;

use App\DTO\BaseRequest;
use App\DTO\Root;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
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
        $exceptions = [];
        $requests = $this->serializer->deserialize(<<<JSON
{"requests": [
    {"id":"sdafafpAUFS323","type":"child","details": {"operation": "some operation"}},
    {"id":"sdafafpAUFS323","type":"somethingElse"},
    {"id":"sdafafpAUFS323","type":"second", "details": {"something": "some operation"}}
]}
JSON,
            Root::class,
            'json',
        );
        dd($requests, $exceptions);


        $childRequest = $this->serializer->deserialize(<<<JSON
{"id":"sdafafpAUFS323","type":"child","details": {"operation": "some operation"}}
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


    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $this->validateCallbackContext($context);

        if (null === $data && isset($context['value_type']) && $context['value_type'] instanceof Type && $context['value_type']->isNullable()) {
            return null;
        }

        $allowedAttributes = $this->getAllowedAttributes($type, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);
        $extraAttributes = [];

        $reflectionClass = new \ReflectionClass($type);
        $object = $this->instantiateObject($normalizedData, $type, $context, $reflectionClass, $allowedAttributes, $format);
        $resolvedClass = $this->objectClassResolver ? ($this->objectClassResolver)($object) : \get_class($object);

        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute, $resolvedClass, $format, $context);
            }

            $attributeContext = $this->getAttributeDenormalizationContext($resolvedClass, $attribute, $context);

            if ((false !== $allowedAttributes && !\in_array($attribute, $allowedAttributes)) || !$this->isAllowedAttribute($resolvedClass, $attribute, $format, $context)) {
                if (!($context[self::ALLOW_EXTRA_ATTRIBUTES] ?? $this->defaultContext[self::ALLOW_EXTRA_ATTRIBUTES])) {
                    $extraAttributes[] = $attribute;
                }

                continue;
            }

            if ($attributeContext[self::DEEP_OBJECT_TO_POPULATE] ?? $this->defaultContext[self::DEEP_OBJECT_TO_POPULATE] ?? false) {
                try {
                    $attributeContext[self::OBJECT_TO_POPULATE] = $this->getAttributeValue($object, $attribute, $format, $attributeContext);
                } catch (NoSuchPropertyException) {
                }
            }

            $types = $this->getTypes($resolvedClass, $attribute);

            if (null !== $types) {
                try {
                    $value = $this->validateAndDenormalize($types, $resolvedClass, $attribute, $value, $format, $attributeContext);
                } catch (NotNormalizableValueException $exception) {
                    if (isset($context['not_normalizable_value_exceptions'])) {
                        $context['not_normalizable_value_exceptions'][] = $exception;
                        continue;
                    }
                    throw $exception;
                }
            }

            $value = $this->applyCallbacks($value, $resolvedClass, $attribute, $format, $attributeContext);

            try {
                $this->setAttributeValue($object, $attribute, $value, $format, $attributeContext);
            } catch (InvalidArgumentException $e) {
                $exception = NotNormalizableValueException::createForUnexpectedDataType(
                    sprintf('Failed to denormalize attribute "%s" value for class "%s": '.$e->getMessage(), $attribute, $type),
                    $data,
                    ['unknown'],
                    $context['deserialization_path'] ?? null,
                    false,
                    $e->getCode(),
                    $e
                );
                if (isset($context['not_normalizable_value_exceptions'])) {
                    $context['not_normalizable_value_exceptions'][] = $exception;
                    continue;
                }
                throw $exception;
            }
        }

        if ($extraAttributes) {
            throw new ExtraAttributesException($extraAttributes);
        }

        return $object;
    }

}