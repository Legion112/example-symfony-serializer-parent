<?php
declare(strict_types=1);

namespace App\Normalizers;

use App\Attributes\DiscriminatorDefault;
use ReflectionClass;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * This denormalized will try to convert not defined type to default one specified in attribute annotation
 * @see DiscriminatorDefault
 */
class CustomNormalizer implements DenormalizerInterface
{
    private array $reflectionCache = [];
    public function __construct(
        private readonly ClassMetadataFactoryInterface $metadataFactory,
        private readonly ObjectNormalizer $objectNormalizer
    ) {
    }

    /**
     * @param array $data
     * @inheritDoc
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []):mixed
    {
        $mapping = $this->metadataFactory->getMetadataFor($type);
        $reflectionClass = $mapping->getReflectionClass();
        $discriminator = $mapping->getClassDiscriminatorMapping();
        if ($discriminator === null) {
            return $this->objectNormalizer->denormalize($data, $type, $format, $context);
        }
        if (array_key_exists($data[$discriminator->getTypeProperty()], $discriminator->getTypesMapping()) ){
            return $this->objectNormalizer->denormalize($data, $type, $format, $context);
        }

        $attributes = $reflectionClass->getAttributes(DiscriminatorDefault::class);
        /** @var DiscriminatorDefault $default */
        /** @noinspection NullPointerExceptionInspection */
        $default = array_pop($attributes)->newInstance();
        return $this->objectNormalizer->denormalize($data, $default->class, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        if (!$this->metadataFactory->hasMetadataFor($type)){
            return false;
        }
        if (!$this->metadataFactory->getMetadataFor($type)->getClassDiscriminatorMapping()){
            return false;
        }
        return $this->hasDefaultAttribute($type);
    }

    public function hasDefaultAttribute(string $class):bool
    {
        $reflectionClass = $this->getReflection($class);
        $attributes = $reflectionClass->getAttributes(DiscriminatorDefault::class);
        return !empty($attributes);
    }

    public function getReflection(string $class):ReflectionClass
    {
        return $this->metadataFactory->getMetadataFor($class)->getReflectionClass();
    }
}