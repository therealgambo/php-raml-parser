<?php

namespace Raml;

class AnnotationType implements ArrayInstantiationInterface
{
    /**
     * A list of valid 'allowedTarget' options
     *
     * @var array
     */
    public static $validTargets = [
        'API',
        'DocumentationItem',
        'Resource',
        'Method',
        'Response',
        'RequestBody',
        'ResponseBody',
        'TypeDeclaration',
        'Example',
        'ResourceType',
        'Trait',
        'SecurityScheme',
        'SecuritySchemeSettings',
        'AnnotationType',
        'Library',
        'Overlay',
        'Extension'
    ];

    /**
     * The key for the annotation type
     *
     * @var string
     */
    private $key;

    /**
     * The display name of the annotation type
     *
     * @var string
     */
    private $displayName;

    /**
     * The description of the annotation type
     *
     * @var string
     */
    private $description;

    /**
     * The annotations for this annotation type
     *
     * @var Annotation[]
     */
    private $annotations = [];

    /**
     * The allowed targets that this annotation type can be applied to
     *
     * @var string[]
     */
    private $allowedTargets = [];

    /**
     * Creates an annotation type with the given key
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Creates an annotation type from an array
     *
     * @param string $key
     * @param array  $data
     * [
     *  displayName:    ?string
     *  description:    ?string
     *  annotations:    ?Annotation[]
     *  allowedTargets: ?string[]
     * ]
     * @param ApiDefinition $apiDefinition
     *
     * @return AnnotationType
     */
    public static function createFromArray($key, array $data = [], ApiDefinition $apiDefinition = null)
    {
        $annotationType = new static($key === 'null' ? null : $key);

        if (isset($data['displayName'])) {
            $annotationType->setDisplayName($data['displayName']);
        } else {
            $annotationType->setDisplayName($key);
        }

        if (isset($data['description'])) {
            $annotationType->setDescription($data['description']);
        }

        if (isset($data['allowedTargets'])) {
            foreach ($data['allowedTargets'] as $target) {
                if (in_array($target, self::$validTargets)) {
                    $annotationType->addAllowedTarget($target);
                } else {
                    throw new Exception\InvalidAnnotationTargetException(
                        'The \'' . $target . '\' target is not a valid target for annotations.'
                    );
                }
            }
        }

        return $annotationType;
    }

    /**
     * Gets the annotation type 'key'
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the 'displayName' for the annotation type
     *
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the 'displayName' for the annotation type
     *
     * @param mixed $displayName
     *
     * @return self
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * Gets the 'description' for the annotation type
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the 'description' for the annotation type
     *
     * @param mixed $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Gets the 'annotations' for the annotation type
     *
     * @return mixed
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Adds an 'annotations' for the annotation type
     *
     * @param Annotation $annotation
     *
     * @return self
     */
    public function addAnnotation($annotation)
    {
        $this->annotations[] = $annotation;
        return $this;
    }

    /**
     * Gets all 'allowedTargets' for the annotation type
     *
     * @return mixed
     */
    public function getAllowedTargets()
    {
        return $this->allowedTargets;
    }

    /**
     * Adds an 'allowedTarget' for the annotation type
     *
     * @param mixed $allowedTarget
     *
     * @return self
     */
    public function addAllowedTarget($allowedTarget)
    {
        $this->allowedTargets[] = $allowedTarget;
        return $this;
    }
}
