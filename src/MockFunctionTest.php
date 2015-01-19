<?php

namespace Phlib;

require_once './MockFunction.php';

class MockFunctionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \MockFunction
     */
    protected $functions;

    public function setUp()
    {
        $this->functions = new MockFunction(__NAMESPACE__);
    }

    public function testFclose1()
    {
        $this->functions->shouldReceive('fclose')->once()->with(1);
        $this->functions->shouldReceive('fclose')->once()->with(2);

        fclose(1);
        fclose(2);

        $this->assertTrue(true);
    }

    public function testFclose2()
    {
        $this->functions->shouldReceive('fclose')->once()->with(3);
        $this->functions->shouldReceive('fclose')->once()->with(4);

        fclose(3);
        fclose(4);

        $this->assertTrue(true);
    }

    public function testSocketSelect()
    {
        $this->functions
            ->shouldReceive('socket_select')
            ->once()
            ->with([], null, null, 1, 0)
            ->andReturnUsing(function(&$read) {
                $read = ['(socket1)'];

                return 'call1';
            })
        ;

        $this->functions
            ->shouldReceive('socket_select')
            ->once()
            ->with(['(socket1)', '(socket2)', '(socket3)'], null, null, 1, 0)
            ->andReturnUsing(function(&$read, &$write, &$except) {
                $read   = ['(socket2)'];
                $write  = ['(socket3)'];
                $except = [];

                return 'call2';
            })
        ;

        $read    = [];
        $write   = $except = null;
        $timeout = 1;

        $result = socket_select($read, $write, $except, $timeout);
        $this->assertSame('call1', $result);
        $this->assertSame(['(socket1)'], $read);

        $read    = ['(socket1)', '(socket2)', '(socket3)'];
        $write   = $except = null;
        $timeout = 1;

        $result = socket_select($read, $write, $except, $timeout);
        $this->assertSame('call2', $result);
        $this->assertSame(['(socket2)'], $read);
        $this->assertSame(['(socket3)'], $write);
        $this->assertSame([], $except);

        $this->assertTrue(true);
    }
}
