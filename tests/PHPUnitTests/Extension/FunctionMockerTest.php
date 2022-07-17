<?php
namespace PHPUnitTests\Extension;

use PHPUnit\Extension\FunctionMocker;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FunctionMockerTest extends TestCase
{
    private FunctionMocker $functionMocker;

    public function setUp(): void
    {
        $this->functionMocker = FunctionMocker::start($this, 'My\TestNamespace');
    }

    public function tearDown(): void
    {
        FunctionMocker::tearDown();
    }

    public function testBasicMockingFunction(): void
    {
        $this->assertMockFunctionNotDefined('My\TestNamespace\strlen');

        $this->functionMocker
            ->mockFunction('strlen')
            ->mockFunction('substr');

        $this->assertMockFunctionNotDefined('My\TestNamespace\strlen');
        $this->assertMockFunctionNotDefined('My\TestNamespace\substr');

        $mock = $this->functionMocker->getMock();

        $this->assertMockFunctionDefined('My\TestNamespace\strlen', 'My\TestNamespace');
        $this->assertMockFunctionDefined('My\TestNamespace\substr', 'My\TestNamespace');

        $mock
            ->expects(self::once())
            ->method('strlen')
            ->will(self::returnValue('mocked strlen()'));
        $mock
            ->expects(self::once())
            ->method('substr')
            ->will(
                self::returnCallback(
                    /** @return string[] */
                    fn(): array => func_get_args()
                )
            )
        ;

        $this->assertMockObjectPresent('My\TestNamespace', $mock);

        self::assertSame('mocked strlen()', \My\TestNamespace\strlen('foo'));
        self::assertSame(['foo', 0, 3], \My\TestNamespace\substr('foo', 0, 3));
    }

    public function testNamespaceLeadingAndTrailingSlash(): void
    {
        $this->functionMocker = FunctionMocker::start($this, '\My\TestNamespace\\');

        $this->assertMockFunctionNotDefined('My\TestNamespace\strpos');

        $this->functionMocker
            ->mockFunction('strpos');

        $this->assertMockFunctionNotDefined('My\TestNamespace\strpos');

        $mock = $this->functionMocker->getMock();

        $this->assertMockFunctionDefined('My\TestNamespace\strpos', 'My\TestNamespace');

        $mock
            ->expects(self::once())
            ->method('strpos')
            ->will(self::returnArgument(1));

        $this->assertMockObjectPresent('My\TestNamespace', $mock);

        self::assertSame('b', \My\TestNamespace\strpos('abc', 'b'));
    }

    public function testFunctionsAreUsedLowercase(): void
    {
        $this->assertMockFunctionNotDefined('My\TestNamespace\myfunc');

        $this->functionMocker
            ->mockFunction('myfunc')
            ->mockFunction(' myfunc   ')
            ->mockFunction('MYFUNC');

        $this->assertMockFunctionNotDefined('My\TestNamespace\myfunc');

        $mock = $this->functionMocker->getMock();

        $this->assertMockFunctionDefined('My\TestNamespace\myfunc', 'My\TestNamespace');

        $mock
            ->expects(self::once())
            ->method('myfunc')
            ->will(self::returnArgument(0));

        $this->assertMockObjectPresent('My\TestNamespace', $mock);

        self::assertSame('abc', \My\TestNamespace\myfunc('abc'));
    }

    public function testUseOneFunctionMockerMoreThanOnce(): void
    {
        $this->assertMockFunctionNotDefined('My\TestNamespace\strtr');

        $this->functionMocker
            ->mockFunction('strtr');

        $this->assertMockFunctionNotDefined('My\TestNamespace\strtr');

        $this->functionMocker->getMock();

        $this->functionMocker
            ->mockFunction('strtr');

        $mock = $this->functionMocker->getMock();

        $this->assertMockFunctionDefined('My\TestNamespace\strtr', 'My\TestNamespace');

        $mock
            ->expects(self::once())
            ->method('strtr')
            ->with('abcd')
            ->will(self::returnArgument(0));

        $this->assertMockObjectPresent('My\TestNamespace', $mock);

        try {
            self::assertSame('abc', \My\TestNamespace\strtr('abc'));
            self::fail('Expected exception');
        } catch (AssertionFailedError $e) {
            self::assertStringContainsString('does not match expected value', $e->getMessage());
        }

        /** Reset mock objects */
        $reflected = new ReflectionClass(TestCase::class);
        $mockObjects = $reflected->getProperty('mockObjects');
        $mockObjects->setAccessible(true);
        $mockObjects->setValue($this, []);
    }

    public function testMockSameFunctionIsDifferentNamespaces(): void
    {
        $this->assertMockFunctionNotDefined('My\TestNamespace\foofunc');
        $this->functionMocker
            ->mockFunction('foofunc');
        $this->assertMockFunctionNotDefined('My\TestNamespace\foofunc');
        $this->functionMocker->getMock();
        $this->assertMockFunctionDefined('My\TestNamespace\foofunc', 'My\TestNamespace');

        $this->functionMocker = FunctionMocker::start($this, 'My\TestNamespace2');

        self::assertFalse(function_exists('My\TestNamespace2\foofunc'));

        $this->functionMocker
            ->mockFunction('foofunc');

            self::assertFalse(function_exists('My\TestNamespace2\foofunc'));

            $this->functionMocker->getMock();
        $this->assertMockFunctionDefined('My\TestNamespace2\foofunc', 'My\TestNamespace2');
    }

    public function assertMockFunctionNotDefined(string $function): void
    {
        self::assertFalse(
            function_exists($function),
            sprintf('Function "%s()" was expected to be undefined', $function)
        );

        self::assertArrayNotHasKey('__PHPUNIT_EXTENSION_FUNCTIONMOCKER', $GLOBALS);
    }

    public function assertMockFunctionDefined(string $function, string $namespace): void
    {
        self::assertTrue(function_exists($function));
        self::assertArrayHasKey('__PHPUNIT_EXTENSION_FUNCTIONMOCKER', $GLOBALS);
        self::assertArrayHasKey($namespace, $GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER']);
    }

    public function assertMockObjectPresent(string $namespace, MockObject $mock): void
    {
        self::assertArrayHasKey('__PHPUNIT_EXTENSION_FUNCTIONMOCKER', $GLOBALS);
        self::assertArrayHasKey($namespace, $GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER']);
        self::assertSame($GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER'][$namespace], $mock);
    }
}
