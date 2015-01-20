<?php

namespace Phlib;

class MockFunctionGenerator
{
    /**
     * @var string
     */
    protected $targetNamespace;

    /**
     * @var \ReflectionFunction
     */
    protected $reflectionFunction;

    /**
     * @var string
     */
    protected $paramsDefinitionOverride;

    /**
     * @var string
     */
    protected $containerClass = '\Phlib\MockFunction';

    /**
     * @param $targetNamespace
     * @param $functionName
     */
    public function __construct($targetNamespace, $functionName)
    {
        // regex to validate namespace
        $regex = '/^[a-z]+[\\a-z0-9]*[a-z0-9]+$/i';
        $targetNamespace = trim($targetNamespace);
        if (!preg_match($regex, $targetNamespace)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid namespace provided "%s"',
                    $targetNamespace
                )
            );
        }
        $this->targetNamespace = $targetNamespace;

        $this->reflectionFunction = new \ReflectionFunction('\\'.$functionName);
    }

    /**
     * @param string $paramsOverride
     * @return $this
     */
    public function setParamsDefinitionOverride($paramsOverride)
    {
        $this->paramsDefinitionOverride = (string)$paramsOverride;

        return $this;
    }

    /**
     * @param $containerClass
     * @return $this
     */
    public function setContainerClass($containerClass)
    {
        $reflectionClass = new ReflectionClass($containerClass);
        if (!$reflectionClass->getMethod('getMock')->isStatic()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot access method getMock on container class "%s"',
                    $containerClass
                )
            );
        }
        $this->containerClass = $containerClass;

        return $this;
    }

    /**
     * @return string
     */
    protected function getParamsDefinition()
    {
        if (!empty($this->paramsDefinitionOverride)) {
            return $this->paramsDefinitionOverride;
        }

        $params = [];
        $functionParams = $this->reflectionFunction->getParameters();
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

    /**
     * @return string
     */
    protected function getParamsCall()
    {
        $params = [];
        $functionParams = $this->reflectionFunction->getParameters();
        foreach ($functionParams as $param) {
            $params[] = '$' . $param->getName();
        }

        return implode(', ', $params);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        $targetNamespace  = $this->targetNamespace;
        $functionName     = $this->reflectionFunction->getName();

        $paramsDefinition = $this->getParamsDefinition();
        $paramsCall       = $this->getParamsCall();

        $containerClass   = $this->containerClass;

        $code = <<<CODE
namespace $targetNamespace;

function $functionName($paramsDefinition)
{
    \$mock = $containerClass::getMock('$targetNamespace', '$functionName');
    if (\$mock) {
        return \$mock->$functionName($paramsCall);
    }

    return \\$functionName($paramsCall);
}

class Mockery_$functionName
{
    public function $functionName($paramsDefinition) {}
}

CODE;

        return $code;
    }

    /**
     * @return $this
     */
    public function override()
    {
        if (!function_exists($this->targetNamespace . '\\' . $this->reflectionFunction->getName())) {
            eval($this->getCode());
        }

        return $this;
    }
}
