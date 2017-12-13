<?php
namespace Raml;

use Raml\RouteFormatter\RouteFormatterInterface;
use Raml\RouteFormatter\NoRouteFormatter;

use Raml\Schema\SchemaDefinitionInterface;
use Raml\RouteFormatter\BasicRoute;

use Raml\Exception\InvalidKeyException;
use Raml\Exception\BadParameter\ResourceNotFoundException;
use Raml\Exception\BadParameter\InvalidSchemaDefinitionException;
use Raml\Exception\BadParameter\InvalidProtocolException;
use Raml\Exception\MutuallyExclusiveElementsException;

use Raml\Type\BooleanType;
use Raml\Type\DateOnlyType;
use Raml\Type\DateTimeOnlyType;
use Raml\Type\DateTimeType;
use Raml\Type\FileType;
use Raml\Type\IntegerType;
use Raml\Type\NilType;
use Raml\Type\NumberType;
use Raml\Type\ObjectType;
use Raml\Type\StringType;
use Raml\Type\TimeOnlyType;
use Raml\Type\UnionType;
use Raml\Type\ArrayType;
use Raml\Type\JsonType;
use Raml\Type\XmlType;
use Raml\Type\LazyProxyType;

/**
 * The API Definition
 *
 * @see http://raml.org/spec.html
 */
class ApiDefinition implements ArrayInstantiationInterface
{
    const PROTOCOL_HTTP = 'HTTP';
    const PROTOCOL_HTTPS = 'HTTPS';
    const ROOT_ELEMENT_NAME = '__ROOT_ELEMENT__';

    // ---

    /**
     * The API Title (required)
     *
     * @see http://raml.org/spec.html#api-title
     *
     * @var string
     */
    private $title;

    /**
     * The API Description (optional)
     *
     * @var string
     */
    private $description;

    /**
     * The API Version (optional)
     *
     * @see http://raml.org/spec.html#api-version
     *
     * @var string
     */
    private $version;

    /**
     * The Base URI (optional for development, required in production)
     *
     * @see http://raml.org/spec.html#base-uri-and-baseuriparameters
     *
     * @var string
     */
    private $baseUri;

    /**
     * Parameters defined in the Base URI
     * - There appears to be a bug in the RAML 0.8 spec related to this,
     * however the baseUriParameters appears to be correct
     *
     * @see http://raml.org/spec.html#base-uri-and-baseuriparameters
     * @see http://raml.org/spec.html#uri-parameters
     *
     * @var NamedParameter[]
     */
    private $baseUriParameters = [];

    /**
     * The supported protocols (default to protocol on baseUrl)
     *
     * @see http://raml.org/spec.html#protocols
     *
     * @var array
     */
    private $protocols = [];

    /**
     * The default media type (optional)
     * - text/yaml
     * - text/x-yaml
     * - application/yaml
     *  - application/x-yaml
     *  - Any type from the list of IANA MIME Media Types, http://www.iana.org/assignments/media-types
     *  - A custom type that conforms to the regular expression, "application\/[A-Za-z.-0-1]*+?(json|xml)"
     *
     * @see http://raml.org/spec.html#default-media-type
     *
     * @var string
     */
    private $mediaType;

    /**
     * The documentation for the API (optional)
     *
     * @see http://raml.org/spec.html#user-documentation
     *
     * @var array
     */
    private $documentation;

    /**
     * A list of data types
     *
     * @link https://github.com/raml-org/raml-spec/blob/master/versions/raml-10/raml-10.md/#raml-data-types
     *
     * @var \Raml\TypeCollection
     */
    private $types = [];

    private $traits = [];

    private $resourceTypes = [];

    /**
     * A list of annotation type definitions
     *
     * @link https://github.com/raml-org/raml-spec/blob/master/versions/raml-10/raml-10.md/#annotations
     *
     * @var AnnotationType[]
     */
    private $annotationTypes = [];

    private $annotations = [];

    /**
     * A list of security schemes
     *
     * @see http://raml.org/spec.html#declaration
     *
     * @var SecurityScheme[]
     */
    private $securitySchemes = [];

    /**
     * A list of security schemes that the whole API is secured by
     *
     * @link http://raml.org/spec.html#usage-applying-a-security-scheme-to-an-api
     *
     * @var SecurityScheme[]
     */
    private $securedBy = [];

    private $uses = [];

    /**
     * The resources the API supplies
     * {/*}
     *
     * @see http://raml.org/spec.html#resources-and-nested-resources
     *
     * @var Resource[]
     */
    private $resources = [];


    /**
     * The schemas the API supplies defined in the root (optional)
     *
     * @deprecated Replaced by types element.
     * @see http://raml.org/spec.html#schemas
     *
     * @var array[]
     */
    private $schemaCollections = [];

    ###################################################################################################################

    /**
     * Create a new API Definition
     *
     * @param string $title
     */
    public function __construct($title)
    {
        $this->title = $title;
        $this->types = TypeCollection::getInstance();
        // since the TypeCollection is a singleton, we need to clear it for every parse
        $this->types->clear();
    }

    /**
     * Create a new API Definition from an array
     *
     * @param string $title
     * @param array  $data
     * [
     *  title:              string
     *  version:            ?string
     *  baseUrl:            ?string
     *  baseUriParameters:  ?array
     *  protocols:          ?array
     *  defaultMediaType:   ?string
     *  schemas:            ?array
     *  types:              ?array
     *  securitySchemes:    ?array
     *  documentation:      ?array
     *  /*
     * ]
     *
     * @return ApiDefinition
     */
    public static function createFromArray($title, array $data = [])
    {
        $apiDefinition = new static($title);

        // Schemas and Types must exclusively exist
        if (isset($data['schemas']) && isset($data['types'])) {
            throw new MutuallyExclusiveElementsException();
        }

        // Set the description for this API definition
        if (isset($data['description'])) {
            $apiDefinition->setDescription($data['description']);
        }

        // Set the version for this API definition
        if (isset($data['version'])) {
            $apiDefinition->setVersion($data['version']);
        }

        // Set the baseUri for this API definition
        if (isset($data['baseUri'])) {
            $apiDefinition->setBaseUri($data['baseUri']);
        }

        // Set the baseUriParameters that can be used within the baseUri
        if (isset($data['baseUriParameters'])) {
            foreach ($data['baseUriParameters'] as $key => $baseUriParameter) {
                $apiDefinition->setBaseUriParameter(
                    BaseUriParameter::createFromArray($key, $baseUriParameter)
                );
            }
        }

        // Set the valid HTTP/HTTPS protocols for this API definition
        if (isset($data['protocols'])) {
            foreach ($data['protocols'] as $protocol) {
                $apiDefinition->setProtocol($protocol);
            }
        }

        // Set the default mediaType for this API definition
        if (isset($data['mediaType'])) {
            $apiDefinition->setMediaType($data['mediaType']);
        }

        // Set the documentation for this API definition
        if (isset($data['documentation'])) {
            foreach ($data['documentation'] as $title => $documentation) {
                $apiDefinition->setDocumentation($title, $documentation);
            }
        }

        // Set the valid types that can be used for this API definition
        if (isset($data['types'])) {
            foreach ($data['types'] as $name => $definition) {
                $apiDefinition->setType(ApiDefinition::determineType($name, $definition));
            }
        }

        // @todo: traits

        // @todo: resourceTypes

        if (isset($data['annotationTypes'])) {
            foreach ($data['annotationTypes'] as $key => $annotationType) {
                if (!is_array($annotationType)) {
                    $annotationType = array($annotationType);
                }
                $apiDefinition->addAnnotationType(AnnotationType::createFromArray($key, $annotationType));
            }
        }


        if (isset($data['securitySchemes'])) {
            foreach ($data['securitySchemes'] as $name => $securityScheme) {
                $apiDefinition->addSecurityScheme(SecurityScheme::createFromArray($name, $securityScheme));
            }
        }

        if (isset($data['securedBy'])) {
            foreach ($data['securedBy'] as $securedBy) {
                if ($securedBy) {
                    $apiDefinition->addSecuredBy($apiDefinition->getSecurityScheme($securedBy));
                } else {
                    $apiDefinition->addSecuredBy(SecurityScheme::createFromArray('null', [], $apiDefinition));
                }
            }
        }

        // @todo: uses

        // resolve type inheritance
        $apiDefinition->getTypes()->applyInheritance();

        // ---

        foreach ($data as $resourceName => $resource) {
            // check if actually a resource
            if (strpos($resourceName, '/') === 0) {
                $apiDefinition->setResource(
                    Resource::createFromArray(
                        $resourceName,
                        $resource,
                        $apiDefinition
                    )
                );
            }
        }

        return $apiDefinition;
    }

    // ---

    /**
     * Get a resource by a uri
     *
     * @param string $uri
     *
     * @throws InvalidKeyException
     *
     * @return \Raml\Resource
     */
    public function getResourceByUri($uri)
    {
        // get rid of everything after the ?
        $uri = strtok($uri, '?');

        $resources = $this->getResourcesAsArray($this->resources);
        foreach ($resources as $resource) {
            /** @var $resource \Raml\Resource */
            if ($resource->matchesUri($uri)) {
                return $resource;
            }
        }
        // we never returned so throw exception
        throw new ResourceNotFoundException($uri);
    }

    /**
     * Get a resource by a path
     *
     * @param string $path
     *
     * @throws InvalidKeyException
     *
     * @return \Raml\Resource
     */
    public function getResourceByPath($path)
    {
        // get rid of everything after the ?
        $path = strtok($path, '?');

        $resources = $this->getResourcesAsArray($this->resources);
        foreach ($resources as $resource) {
            /** @var $resource \Raml\Resource */
            if ($path === $resource->getUri()) {
                return $resource;
            }
        }
        // we never returned so throw exception
        throw new ResourceNotFoundException($path);
    }


    /**
     * Returns all the resources as a URI, essentially documenting the entire API Definition.
     * This will output, by default, an array that looks like:
     *
     * GET /songs => [/songs, GET, Raml\Method]
     * GET /songs/{songId} => [/songs/{songId}, GET, Raml\Method]
     *
     * @param RouteFormatterInterface $formatter
     *
     * @return RouteFormatterInterface
     */
    public function getResourcesAsUri(RouteFormatterInterface $formatter = null)
    {
        if (!$formatter) {
            $formatter = new NoRouteFormatter();
        }

        $formatter->format($this->getMethodsAsArray($this->resources));

        return $formatter;
    }

    /**
     * @param $resources
     *
     * @return Resource
     */
    private function getResourcesAsArray($resources)
    {
        $resourceMap = [];

        // Loop over each resource to build out the full URI's that it has.
        foreach ($resources as $resource) {
            /** @var $resource \Raml\Resource */
            $resourceMap[$resource->getUri()] = $resource;

            $resourceMap = array_merge_recursive($resourceMap, $this->getResourcesAsArray($resource->getResources()));
        }

        return $resourceMap;
    }

    /**
     * Does the API support HTTP (non SSL) requests?
     *
     * @return boolean
     */
    public function supportsHttp()
    {
        return in_array(self::PROTOCOL_HTTP, $this->protocols);
    }

    /**
     * Does the API support HTTPS (SSL enabled) requests?
     *
     * @return boolean
     */
    public function supportsHttps()
    {
        return in_array(self::PROTOCOL_HTTPS, $this->protocols);
    }

    /**
     * @deprecated Use types instead!
     * Get the schemas defined in the root of the API
     *
     * @return TypeCollection
     */
    public function getSchemaCollections(): TypeCollection
    {
        return $this->types;
    }

    /**
     * @deprecated Use types instead!
     * Add an schema
     *
     * @param string $collectionName
     * @param array  $schemas
     */
    public function addSchemaCollection($collectionName, $schemas)
    {
        $this->schemaCollections[$collectionName] = [];

        foreach ($schemas as $schemaName => $schema) {
            $this->addSchema($collectionName, $schemaName, $schema);
        }
    }

    /**
     * @deprecated Use types instead!
     * Add a new schema to a collection
     *
     * @param string                            $collectionName
     * @param string                            $schemaName
     * @param string|SchemaDefinitionInterface  $schema
     *
     * @throws InvalidSchemaDefinitionException
     */
    private function addSchema($collectionName, $schemaName, $schema)
    {
        if (!is_string($schema) && !$schema instanceof SchemaDefinitionInterface) {
            throw new InvalidSchemaDefinitionException();
        }

        $this->schemaCollections[$collectionName][$schemaName] = $schema;
    }

    /**
     * Determines the right Type and returns a type instance
     *
     * @param string                    $name       Name of type.
     * @param array                     $definition Definition of type.
     * @param TypeCollection|null       $typeCollection Type collection object.
     *
     * @return TypeInterface
     * @throws \Exception
     **/
    public static function determineType($name, $definition)
    {
        if (is_string($definition)) {
            $definition = ['type' => $definition];
        } elseif (is_array($definition)) {
            if (!isset($definition['type'])) {
                $definition['type'] = isset($definition['properties']) ? 'object' : 'string';
            }
        } elseif ($definition instanceof \stdClass) {
            return JsonType::createFromArray('schema', $definition);
        } else {
            throw new \Exception('Invalid datatype for $definition parameter.');
        }
        if (is_object($name)) {
            throw new \Exception(var_export($name, true));
        }
        
        
        if (strpos($definition['type'], '?') !== false ||
            $pos = strpos($name, '?') !== false) {
            // shorthand for required = false
            $definition['required'] = isset($definition['required']) ? $definition['required'] : false;
        }
        
        // check if we can find a more appropriate Type subclass
        $straightForwardTypes = [
            ArrayType::TYPE_NAME         => 'Raml\Type\ArrayType',
            BooleanType::TYPE_NAME       => 'Raml\Type\BooleanType',
            DateTimeType::TYPE_NAME      => 'Raml\Type\DateTimeType',
            DateTimeOnlyType::TYPE_NAME  => 'Raml\Type\DateTimeOnlyType',
            DateOnlyType::TYPE_NAME      => 'Raml\Type\DateOnlyType',
            FileType::TYPE_NAME          => 'Raml\Type\FileType',
            IntegerType::TYPE_NAME       => 'Raml\Type\IntegerType',
            NilType::TYPE_NAME           => 'Raml\Type\NilType',
            NumberType::TYPE_NAME        => 'Raml\Type\NumberType',
            ObjectType::TYPE_NAME        => 'Raml\Type\ObjectType',
            StringType::TYPE_NAME        => 'Raml\Type\StringType',
            TimeOnlyType::TYPE_NAME      => 'Raml\Type\TimeOnlyType',
        ];
        
        $type = $definition['type'];

        if (in_array($type, array_keys($straightForwardTypes))) {
            return forward_static_call_array([$straightForwardTypes[$type],'createFromArray'], [$name, $definition]);
        }

        if (!in_array($type, ['','any'])) {
            // if $type contains a '|' we can savely assume it's a combination of types (union)
            if (strpos($type, '|') !== false) {
                return UnionType::createFromArray($name, $definition);
            }
            // if $type contains a '[]' it means we have an array with a item restriction
            if (strpos($type, '[]') !== false) {
                return ArrayType::createFromArray($name, $definition);
            }
            // is it a XML schema?
            if (substr(ltrim($type), 0, 1) === '<') {
                return XmlType::createFromArray(self::ROOT_ELEMENT_NAME, $definition);
            }
            // is it a JSON schema?
            if (substr(ltrim($type), 0, 1) === '{') {
                return JsonType::createFromArray(self::ROOT_ELEMENT_NAME, $definition);
            }

            // no? then no standard type found so this must be a reference to a custom defined type.
            // since the actual definition can be defined later then when it is referenced,
            // we create a proxy object for lazy loading when it is needed
            return LazyProxyType::createFromArray($name, $definition);
        }
        
        // No subclass found, let's use base class
        return Type::createFromArray($name, $definition);
    }


    // ---

    /**
     * Recursive function that generates a flat array of the entire API Definition
     *
     * GET /songs => [api.example.org, /songs, GET, [https], Raml\Method]
     * GET /songs/{songId} => [api.example.org, /songs/{songId}, GET, [https], Raml\Method]
     *
     * @param \Raml\Resource[] $resources
     *
     * @return array[BasicRoute]
     */
    private function getMethodsAsArray(array $resources)
    {
        $all = [];
        $baseUrl = $this->getBaseUri();
        $protocols = $this->protocols;

        // Loop over each resource to build out the full URI's that it has.
        foreach ($resources as $resource) {
            $path = $resource->getUri();

            foreach ($resource->getMethods() as $method) {
                $all[$method->getType() . ' ' . $path] = new BasicRoute(
                    $baseUrl,
                    $path,
                    $protocols,
                    $method->getType(),
                    $resource->getUriParameters(),
                    $resource->getMethod($method->getType())
                );
            }

            $all = array_merge_recursive($all, $this->getMethodsAsArray($resource->getResources()));
        }

        return $all;
    }


    ########################################################################################

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function getBaseUri()
    {
        return ($this->version) ? str_replace('{version}', $this->version, $this->baseUri) : $this->baseUri;
    }

    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;

        if (!$this->protocols) {
            $protocol = strtoupper(parse_url($this->baseUri, PHP_URL_SCHEME));
            if (!empty($protocol)) {
                $this->protocols[] = $protocol;
            }
        }
    }

    public function getBaseUriParameters()
    {
        return $this->baseUriParameters;
    }

    public function setBaseUriParameter(NamedParameter $namedParameter)
    {
        $this->baseUriParameters[$namedParameter->getKey()] = $namedParameter;
    }

    public function getProtocols()
    {
        return $this->protocols;
    }

    public function setProtocol($protocol)
    {
        if (!in_array($protocol, [self::PROTOCOL_HTTP, self::PROTOCOL_HTTPS])) {
            throw new InvalidProtocolException(sprintf('"%s" is not a valid protocol', $protocol));
        }

        if (!in_array($protocol, $this->protocols)) {
            $this->protocols[] = $protocol;
        }
    }

    /**
     * @return array|string
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;
    }

    public function getDocumentation()
    {
        return $this->documentation;
    }

    public function setDocumentation($title, $documentation)
    {
        $this->documentation[$title] = $documentation;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function setType(TypeInterface $type)
    {
        $this->types->add($type);
    }

    public function getTraits()
    {
        return $this->traits;
    }

    public function setTrait($key, $trait)
    {
        $this->traits[$key] = $trait;
    }

    public function getResourceTypes()
    {
        return $this->resourceTypes;
    }

    public function setResourceType($key, $type)
    {
        $this->resourceTypes[$key] = $type;
    }

    public function getAnnotationTypes()
    {
        return $this->annotationTypes;
    }

    public function addAnnotationType(AnnotationType $annotationType)
    {
        $this->annotationTypes[$annotationType->getKey()] = $annotationType;
    }

    public function getAnnotations()
    {
        return $this->annotations;
    }

    public function setAnnotation($key, $annotation)
    {
        $this->annotations[$key] = $annotation;
    }

    public function getSecurityScheme($schemeName): SecurityScheme
    {
        return $this->securitySchemes[$schemeName];
    }

    public function addSecurityScheme(SecurityScheme $securityScheme)
    {
        $this->securitySchemes[$securityScheme->getKey()] = $securityScheme;
    }

    public function getSecuredBy()
    {
        return $this->securedBy;
    }

    public function addSecuredBy(SecurityScheme $securityScheme)
    {
        $this->securedBy[$securityScheme->getKey()] = $securityScheme;
    }

    public function getUses()
    {
        return $this->uses;
    }

    public function setUses($use)
    {
        $this->uses[] = $use;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function setResource(\Raml\Resource $resource)
    {
        $this->resources[$resource->getUri()] = $resource;
    }
}
