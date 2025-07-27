<?php

use \cebe\openapi\spec\Components;
use \cebe\openapi\spec\Info;
use \cebe\openapi\spec\MediaType;
use \cebe\openapi\spec\OpenApi;
use \cebe\openapi\spec\Operation;
use \cebe\openapi\spec\PathItem;
use \cebe\openapi\spec\RequestBody;
use \cebe\openapi\spec\Response;
use \cebe\openapi\spec\Schema;
use \cebe\openapi\spec\Server;
use \cebe\openapi\Writer;

class Fab_OpenApi_AutoDiscover
{
    /** @var array Default schema building strategies */
    protected static $_defaultStrategies = array('DoctrineRecord', 'DocComment', 'Properties');

    /** @var string Service name */
    protected $_serviceName = 'OpenAPI Service';

    /** @var string Service version */
    protected $_serviceVersion = '1.0';

    /** @var string Service URL */
    protected $_serviceUrl = '/';
    
    /** @var array Class map (short name to full name) */
    protected $_classmap = array();

    /** @var PathItem[] OpenAPI paths */
    protected $_paths = array();

    /** @var Schema[] OpenAPI schemas */
    protected $_schemas = array(
        'Error' => array(
            'type' => 'object',
            'properties' => array(
                'code' => array('type' => 'integer'),
                'message' => array('type' => 'string'),
                'data' => array('type' => 'object'),
            ),
            'required' => array('code', 'message')
        ),
    );

    /** @var Fab_OpenApi_Strategy_Interface Type mapping strategy */
    protected $_strategy;

    /**
     * OpenAPI AutoDiscover constructor.
     * @param array $strategies List of strategies to use for building schemas
     * @param array $strategyPrefixToPaths Association of custom strategy prefixes to paths
     */
    public function __construct(array $strategies = array(), array $strategyPrefixToPaths = array())
    {
        if (empty($strategies))
            $strategies = self::$_defaultStrategies;

        $strategyClasses = array();
        $pluginLoader = new Zend_Loader_PluginLoader(array_merge($strategyPrefixToPaths, array(
            'Fab_OpenApi_Strategy_' => 'Fab/OpenApi/Strategy/',
        )));
        foreach ($strategies as $strategy) {
            $strategyClasses[] = $pluginLoader->load($strategy);
        }

        $this->_strategy = new Fab_OpenApi_Strategy_Composite($strategyClasses);
        $this->_strategy->setContext($this);
    }

    /**
     * Get the service name.
     * @return string
     */
    public function getServiceName()
    {
        return $this->_serviceName;
    }

    /**
     * Set the service name.
     * @param string $serviceName
     * @return $this
     */
    public function setServiceName($serviceName)
    {
        $this->_serviceName = $serviceName;
        return $this;
    }

    /**
     * Get the service version.
     * @return string
     */
    public function getServiceVersion()
    {
        return $this->_serviceVersion;
    }

    /**
     * Set the service version.
     * @param string $serviceVersion
     * @return $this
     */
    public function setServiceVersion($serviceVersion)
    {
        $this->_serviceVersion = $serviceVersion;
        return $this;
    }

    /**
     * Get the service URL.
     * @return string
     */
    public function getServiceUrl()
    {
        return $this->_serviceUrl;
    }

    /**
     * Set the service URL.
     * @param Zend_Uri|string $serviceUrl
     * @return $this
     */
    public function setServiceUrl($serviceUrl)
    {
        if ($serviceUrl instanceof Zend_Uri) {
            $serviceUrl = $serviceUrl->getUri();
        }
        $this->_serviceUrl = $serviceUrl;
        return $this;
    }

    /**
     * Get the class map (short name to full name).
     * @return array
     */
    public function getClassmap()
    {
        return $this->_classmap;
    }

    /**
     * Set the class map (short name to full name).
     * @param array $classmap
     * @return $this
     */
    public function setClassmap(array $classmap)
    {
        $this->_classmap = $classmap;
        return $this;
    }

    /**
     * Set the service class.
     * @param string $serviceClass Service class name
     * @param string $prefix Path prefix for all service operations
     * @return $this
     */
    public function setServiceClass($serviceClass, $prefix = '/')
    {
        $class = Zend_Server_Reflection::reflectClass($serviceClass);
        foreach ($class->getMethods() as $method) {
            $this->_addFunctionToPaths($method, $prefix);
        }
        return $this;
    }

    /**
     * Add a function to the OpenAPI paths.
     * @param Zend_Server_Reflection_Function_Abstract $function Function to add
     * @param string $prefix Path prefix for the function
     */
    protected function _addFunctionToPaths($function, $prefix = '/')
    {
        $prototype = $this->_getMainPrototype($function);
        $requestSchema = $this->_buildRequestSchema($prototype);
        $responseSchema = $this->_buildResponseSchema($prototype);
        $operation = $this->_buildOperation($function, $requestSchema, $responseSchema);

        $this->_paths[$prefix . $function->getName()] = new PathItem(array('post' => $operation));
    }

    /**
     * Get the main prototype of a function.
     * This is the prototype with the most parameters.
     * @param Zend_Server_Reflection_Function_Abstract $function
     * @return Zend_Server_Reflection_Prototype The main prototype of the function
     */
    protected function _getMainPrototype($function)
    {
        $prototype = null;
        $maxNumArgumentsOfPrototype = -1;
        foreach ($function->getPrototypes() as $tmpPrototype) {
            $numParams = count($tmpPrototype->getParameters());
            if ($numParams > $maxNumArgumentsOfPrototype) {
                $maxNumArgumentsOfPrototype = $numParams;
                $prototype = $tmpPrototype;
            }
        }
        if ($prototype === null) {
            throw new Fab_OpenApi_Exception("No prototypes could be found for the '" . $function->getName() . "' function");
        }
        return $prototype;
    }

    /**
     * Build the request schema for a function prototype.
     * @param Zend_Server_Reflection_Prototype $prototype Function prototype
     * @return Schema OpenAPI Schema for the request
     */
    protected function _buildRequestSchema($prototype)
    {
        $requestParams = array();
        $requestRequired = array();
        foreach ($prototype->getParameters() as $param) {
            $requestParams[$param->getName()] = $this->mapType($param->getType());
            if ($param->isDefaultValueAvailable()) {
                $requestParams[$param->getName()]['default'] = $param->getDefaultValue();
            }
            if (!$param->isOptional()) {
                $requestRequired[] = $param->getName();
            }
        }
        $requestSchema = new Schema(array(
            'type' => 'object',
            'properties' => $requestParams,
        ));
        if (!empty($requestRequired)) {
            $requestSchema->required = $requestRequired;
        }
        return $requestSchema;
    }

    /**
     * Build the response schema for a function prototype.
     * @param Zend_Server_Reflection_Prototype $prototype Function prototype
     * @return Schema OpenAPI Schema for the response
     */
    protected function _buildResponseSchema($prototype)
    {
        return new Schema($this->mapType($prototype->getReturnType()));
    }

    /**
     * Build an OpenAPI operation for a function.
     * @param Zend_Server_Reflection_Function_Abstract $function Function to build the operation for
     * @param Schema $requestSchema Request schema
     * @param Schema $responseSchema Response schema
     * @return Operation OpenAPI Operation object
     */
    protected function _buildOperation($function, $requestSchema, $responseSchema)
    {
        return new Operation(array(
            'operationId' => $function->getName(),
            'summary' => $function->getDescription(),
            'requestBody' => new RequestBody(array(
                'required' => true,
                'content' => array(
                    'application/json' => new MediaType(array(
                        'schema' => $requestSchema,
                    )),
                ),
            )),
            'responses' => array(
                '200' => new Response(array(
                    'description' => 'OK',
                    'content' => array(
                        'application/json' => new MediaType(array(
                            'schema' => $responseSchema,
                        )),
                    ),
                )),
                '400' => new Response(array(
                    'description' => 'Bad Request',
                    'content' => array(
                        'application/json' => new MediaType(array(
                            'schema' => array('$ref' => '#/components/schemas/Error'),
                        )),
                    ),
                )),
                '500' => new Response(array(
                    'description' => 'Internal Server Error',
                    'content' => array(
                        'application/json' => new MediaType(array(
                            'schema' => array('$ref' => '#/components/schemas/Error'),
                        )),
                    ),
                )),
            ),
        ));
    }

    /**
     * Map a PHP type to an OpenAPI Schema.
     * @param string $type PHP type
     * @return array OpenAPI Schema
     */
    public function mapType($type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
            case 'long':
                return array('type' => 'integer');
            case 'float':
            case 'double':
            case 'decimal':
                return array('type' => 'number');
            case 'bool':
            case 'boolean':
                return array('type' => 'boolean');
            case 'str':
            case 'string':
            case 'text':
            case 'enum':
                return array('type' => 'string');
            case 'date':
                return array('type' => 'string', 'format' => 'date');
            case 'timestamp':
                return array('type' => 'string', 'format' => 'date-time');
            case 'array':
                return array('type' => 'array');
            case 'object':
            case 'mixed':
                return array('type' => 'object');
            case 'void':
                return array();
            default:
                if (substr($type, -2) == '[]') {
                    // Handle array types
                    $itemType = substr($type, 0, -2);
                    return array(
                        'type' => 'array',
                        'items' => $this->mapType($itemType),
                    );
                } else {
                    // Handle custom types
                    $ref = $this->_addTypeToSchemas($type);
                    return array('$ref' => $ref);
                }
        }
    }

    /**
     * Add a custom PHP type to the OpenAPI schemas if it is not already present.
     * @param string $type PHP type
     * @return string Schema reference
     */
    protected function _addTypeToSchemas($type)
    {
        // Get the mapped class name
        $name = $type;
        if (($mappedType = array_search($type, $this->_classmap)) !== false)
            $name = $mappedType;

        // Return reference if already exists
        $ref = '#/components/schemas/' . $name;
        if (isset($this->_schemas[$name]))
            return $ref;

        // Create empty schema to avoid infinite recursion
        $this->_schemas[$name] = new Schema(array('type' => 'object'));

        // Build schema using the strategy
        $this->_schemas[$name] = $this->_strategy->buildSchemaForType($type);

        return $ref;
    }

    /**
     * Generate the OpenAPI Description for the service.
     * @return OpenApi OpenAPI Description
     */
    public function getOpenApiDescription()
    {
        return new OpenApi(array(
            'openapi' => '3.0.0',
            'info' => new Info(array(
                'title' => $this->_serviceName,
                'version' => $this->_serviceVersion,
            )),
            'servers' => array(
                new Server(array(
                    'url' => $this->_serviceUrl,
                )),
            ),
            'paths' => $this->_paths,
            'components' => new Components(array(
                'schemas' => $this->_schemas,
            )),
        ));
    }

    /**
     * Get the OpenAPI Description as a JSON string.
     * @return string OpenAPI Description as JSON
     */
    public function toJson()
    {
        $openapi = $this->getOpenApiDescription();
        return Writer::writeToJson($openapi);
    }

}
