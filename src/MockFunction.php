<?php

namespace Phlib;

class MockFunction
{
    public static $mocks = [];

    protected $namespace;

    public function __construct($namespace)
    {
        $namespace = trim($namespace, '\\');
        $this->namespace = $namespace;
        self::$mocks[$namespace] = [];
    }

    public function getParamsDefinition(\ReflectionFunction $function)
    {
        $params = [];
        $functionParams = $function->getParameters();
        foreach ($functionParams as $param) {
            $paramDef = $param->isPassedByReference() ? '&' : '';
            $paramDef .= '$' . $param->getName();

            if ($param->isDefaultValueAvailable()) {
                $paramDef .= ' = ' . var_export($param->getDefaultValue(), true);
            } elseif ($param->isOptional()) {
                $paramDef .= ' = null';
            }

            $params[] = $paramDef;
        }

        return implode(', ', $params);
    }

    public function getParamsCall(\ReflectionFunction $function)
    {
        $params = [];
        $functionParams = $function->getParameters();
        foreach ($functionParams as $param) {
            $params[] = '$' . $param->getName();
        }

        return implode(', ', $params);
    }

    public function getCode(\ReflectionFunction $function)
    {
        $targetNamespace  = $this->namespace;
        $functionName     = $function->getName();
        $paramsDefinition = $this->getParamsDefinition($function);
        $paramsCall       = $this->getParamsCall($function);

        $self = '\\' . __CLASS__;

        $code = <<<CODE
namespace $targetNamespace;

function $functionName($paramsDefinition)
{
    return $self::\$mocks['$targetNamespace']['$functionName']->$functionName($paramsCall);
}

class MockFunction_$functionName
{
    public function $functionName($paramsDefinition) {}
}

CODE;

        return $code;
    }

    /**
     * @param string $functionName
     * @return \Mockery\Expectation
     */
    public function shouldReceive($functionName)
    {
        if (!function_exists($this->namespace . '\\' . $functionName)) {
            $function = new \ReflectionFunction('\\'.$functionName);
            eval($this->getCode($function));
        }

        if (!isset(self::$mocks[$this->namespace][$functionName])) {
            $className = $this->namespace.'\\MockFunction_'.$functionName;
            self::$mocks[$this->namespace][$functionName] = \Mockery::mock($className);
        }

        return self::$mocks[$this->namespace][$functionName]->shouldReceive($functionName);
    }
}
