<?php

namespace Phlib;

/**
 * Class MockFunction
 * @package Phlib
 */
class MockFunction
{
    /**
     * @var array
     */
    public static $mocks = [];

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @param $namespace
     * @param $function
     * @return bool
     */
    public static function getMock($namespace, $function)
    {
        if (isset(self::$mocks[$namespace][$function])) {
            return self::$mocks[$namespace][$function];
        }

        return false;
    }

    /**
     * @param $namespace
     */
    public function __construct($namespace)
    {
        $namespace = trim($namespace, '\\');
        $this->namespace = $namespace;
        self::$mocks[$namespace] = [];
    }

    /**
     * @param $function
     * @param null $paramsDefinition
     * @return $this
     */
    public function override($function, $paramsDefinition = null)
    {
        $generator = new MockFunctionGenerator(
            $this->namespace,
            $function
        );
        if ($paramsDefinition) {
            $generator->setParamsDefinitionOverride($paramsDefinition);
        }
        $generator->override();

        return $this;
    }

    /**
     * @param $function
     * @return \Mockery\Expectation
     */
    public function shouldReceive($function)
    {
        if (!function_exists($this->namespace . '\\' . $function)) {
            throw new \RuntimeException(
                sprintf(
                    'No call to override has been made for "%s"',
                    $function
                )
            );
        }

        $mock = self::getMock($this->namespace, $function);
        if (!$mock) {
            $className = $this->namespace . '\\Mockery_' . $function;
            $mock = \Mockery::mock($className);
            self::$mocks[$this->namespace][$function] = $mock;
        }

        return $mock->shouldReceive($function);
    }

    /**
     *
     */
    public function __destruct()
    {
        self::$mocks[$this->namespace] = null;
    }
}
