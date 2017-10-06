<?php

namespace Raml;

class Annotation implements ArrayInstantiationInterface
{
    /**
     * The key for the annotation
     *
     * @var string
     */
    private $key;

    /**
     * The value of the annotation
     *
     * @var mixed
     */
    private $value;

    /**
     * Creates an annotation with the given key
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Creates an annotation from an array
     *
     * @param string        $key
     * @param array         $data
     * @param ApiDefinition $apiDefinition
     * @param string        $node
     *
     * @return Annotation
     */
    public static function createFromArray($key, array $data = [], ApiDefinition $apiDefinition = null, $node = null)
    {
        $annotation = new static($key === 'null' ? null : $key);

        // Get a list of annotation types defined at the root level
        $annotationTypes = $apiDefinition->getAnnotationTypes();

        // Check that the annotation has a valid annotation type defined
        if (array_key_exists($key, $annotationTypes)) {
            $annotationType = $annotationTypes[$key];
        } else {
            throw new Exception\UndefinedAnnotationTypeException(
                'There is no annotationType defined for a \'' . $key . '\' annotation.'
            );
        }

        // Get a list of allowed targets from the annotation type
        $allowedTargets = $annotationType->getAllowedTargets();

        // Check that the annotation can be used at the given node
        if (!empty($allowedTargets) && !in_array($node, $allowedTargets)) {
            throw new Exception\InvalidAnnotationTargetException(
                'The \'' . $key . '\' annotation type cannot be used at this location.'
            );
        }

        $annotation->setValue($data);

        return $annotation;
    }

    /**
     * Gets the annotation 'key'
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the value of the annotation
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value for the annotation
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
