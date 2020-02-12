<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge;

/**
 * Import classes
 */
use pmill\Doctrine\Hydrator\ArrayHydrator as BaseArrayHydrator;
use Symfony\Component\Inflector\Inflector;
use ReflectionObject;

/**
 * Import functions
 */
use function is_array;
use function str_replace;
use function strpos;
use function ucfirst;
use function ucwords;

/**
 * ArrayHydrator
 *
 * @TODO (idea) check the type of values...
 */
class ArrayHydrator extends BaseArrayHydrator
{

    /**
     * {@inheritDoc}
     */
    protected function hydrateToManyAssociation($entity, $propertyName, $mapping, $value)
    {
        $entityRef = new ReflectionObject($entity);

        $adderName = $this->createAdderNameForPropertyName($propertyName);
        if (!$entityRef->hasMethod($adderName)) {
            return $entity;
        }

        $adderRef = $entityRef->getMethod($adderName);
        if (!($adderRef->isPublic() && !$adderRef->isStatic())) {
            return $entity;
        }

        if (0 === $adderRef->getNumberOfParameters()) {
            return $entity;
        }

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $value) {
            $targetEntity = is_array($value) ?
            $this->hydrate($mapping['targetEntity'], $value) :
            $this->fetchAssociationEntity($mapping['targetEntity'], $value);

            if ($targetEntity instanceof $mapping['targetEntity']) {
                $adderRef->invoke($entity, $targetEntity);
            }
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    protected function setProperty($entity, $propertyName, $value, $entityRef = null)
    {
        $entityRef = $entityRef ?? new ReflectionObject($entity);

        $setterName = $this->createSetterNameForPropertyName($propertyName);
        if (!$entityRef->hasMethod($setterName)) {
            return $entity;
        }

        $setterRef = $entityRef->getMethod($setterName);
        if (!($setterRef->isPublic() && !$setterRef->isStatic())) {
            return $entity;
        }

        if (0 === $setterRef->getNumberOfParameters()) {
            return $entity;
        }

        $setterRef->invoke($entity, $value);

        return $entity;
    }

    /**
     * Creates an adder name for the given property name
     *
     * @param string $propertyName
     *
     * @return string
     */
    private function createAdderNameForPropertyName(string $propertyName) : string
    {
        $propertyName = $this->convertPropertyNameFromSnakeCaseToCamelCase($propertyName);

        // Sometimes it's not possible to determine a unique singular/plural form for the given word.
        // In those cases, the methods return an array with all the possible forms.
        $singularPropertyName = (array) Inflector::singularize($propertyName);

        return 'add' . $singularPropertyName[0];
    }

    /**
     * Creates a setter name for the given property name
     *
     * @param string $propertyName
     *
     * @return string
     */
    private function createSetterNameForPropertyName(string $propertyName) : string
    {
        $propertyName = $this->convertPropertyNameFromSnakeCaseToCamelCase($propertyName);

        return 'set' . $propertyName;
    }

    /**
     * Converts the given property name from a snake case to a camel case
     *
     * @param string $propertyName
     *
     * @return string
     */
    private function convertPropertyNameFromSnakeCaseToCamelCase(string $propertyName) : string
    {
        if (false === strpos($propertyName, '_')) {
            return ucfirst($propertyName);
        }

        $propertyName = str_replace('_', ' ', $propertyName);
        $propertyName = ucwords($propertyName);
        $propertyName = str_replace(' ', '', $propertyName);

        return $propertyName;
    }
}
