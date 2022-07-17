<?php
namespace PHPUnitTests\Extension;

use PHPUnit\Extension\FunctionMocker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnitTests\Extension\Fixtures\TestClass;

require_once __DIR__ . '/Fixtures/TestClass.php'; 

class IntegrationTest extends TestCase
{
    private MockObject $php;

    public function setUp(): void
    {
        $this->php = FunctionMocker::start($this, 'PHPUnitTests\Extension\Fixtures')
            ->mockFunction('strpos')
            ->mockConstant('CNT', 'val')
            ->getMock();
    }

    public function testMockFunction(): void
    {
        $this->php
            ->expects(self::once())
            ->method('strpos')
            ->with('ffoo', 'o')
            ->will(self::returnValue('mocked'));

        self::assertSame('mocked', TestClass::invokeGlobalFunction());
    }

    public function testMockingGlobalFunctionAndCallingOriginalAgain(): void
    {
        $this->testMockFunction();
        FunctionMocker::tearDown();

        self::assertSame(2, TestClass::invokeGlobalFunction());
    }

    public function testMockConstant(): void
    {
        self::assertSame('val', TestClass::getGlobalConstant());
    }
}
