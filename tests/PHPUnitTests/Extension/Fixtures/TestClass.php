<?php
namespace PHPUnitTests\Extension\Fixtures;

class TestClass
{
    public static function invokeGlobalFunction(): string|int
    {
        return strpos('ffoo', 'o');
    }

    public static function getGlobalConstant(): string
    {
        return CNT;
    }
}
