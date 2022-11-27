<?php
declare(strict_types=1);

namespace App\Normalizers;

use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ObjectNormalizerExtended extends ObjectNormalizer
{
    private array $discriminatorCache = [];
    /**
     * {@inheritdoc}
     */
    protected function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass, array|bool $allowedAttributes, string $format = null):object
    {
        if ($this->classDiscriminatorResolver && $mapping = $this->classDiscriminatorResolver->getMappingForClass($class)) {

            if (!isset($data[$mapping->getTypeProperty()])) {
                throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('Type property "%s" not found for the abstract object "%s".', $mapping->getTypeProperty(), $class), null, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), false);
            }

            $type = $data[$mapping->getTypeProperty()];
            if (null === ($mappedClass = $mapping->getClassForType($type))) {
                // We do not throw exception here instead return base class if it is not abstract
                if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
                    throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type "%s" is not a valid value.', $type), $type, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'] . '.' . $mapping->getTypeProperty() : $mapping->getTypeProperty(), true);
                }
            } else if ($mappedClass !== $class) {
                return $this->instantiateObject($data, $mappedClass, $context, new \ReflectionClass($mappedClass), $allowedAttributes, $format);
            }
        }

        return AbstractNormalizer::instantiateObject($data, $class, $context, $reflectionClass, $allowedAttributes, $format);
    }


    /**
     * {@inheritdoc}
     */
    protected function getAttributeValue(object $object, string $attribute, string $format = null, array $context = []): mixed
    {
        $cacheKey = \get_class($object);
        if (!\array_key_exists($cacheKey, $this->discriminatorCache)) {
            $this->discriminatorCache[$cacheKey] = null;
            if (null !== $this->classDiscriminatorResolver) {
                $mapping = $this->classDiscriminatorResolver->getMappingForMappedObject($object);
                $this->discriminatorCache[$cacheKey] = $mapping?->getTypeProperty();
            }
        }

        return $attribute === $this->discriminatorCache[$cacheKey]
            ? $this->classDiscriminatorResolver->getTypeForMappedObject($object) ?? $this->propertyAccessor->getValue($object, $attribute)
            : $this->propertyAccessor->getValue($object, $attribute);
    }
}