<?php
namespace PHPUnitTests\Extension\Fixtures;

class TestClass
{
    /** @return string|int */
    public static function invokeGlobalFunction()
    {
        return strpos('ffoo', 'o');
    }

    public static function getGlobalConstant(): string
    {
        return CNT;
    }
}
