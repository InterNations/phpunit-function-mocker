<?php
namespace PHPUnit\Extension;

use PHPUnit\Extension\FunctionMocker\CodeGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function bin2hex;
use function random_bytes;

class FunctionMocker
{
    private TestCase $testCase;
    private string $namespace;

    /** @var string[] */
    private array $functions = [];

    /** @var string[] */
    private array $constants = [];

    /** @var string[] */
    private static $mockedFunctions = [];

    private function __construct(TestCase $testCase, string $namespace)
    {
        $this->testCase = $testCase;
        $this->namespace = trim($namespace, '\\');
    }

    /**
     * Create a mock for the given namespace to override global namespace functions.
     *
     * Example: PHP global namespace function setcookie() needs to be overridden in order to test
     * if a cookie gets set. When setcookie() is called from inside a class in the namespace
     * \Foo\Bar the mock setcookie() created here will be used instead of the real function.
     */
    public static function start(TestCase $testCase, string $namespace): self
    {
        return new static($testCase, $namespace);
    }

    public static function tearDown(): void
    {
        unset($GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER']);
    }

    public function mockFunction(string $function): self
    {
        $function = trim(strtolower($function));

        if (!in_array($function, $this->functions, true)) {
            $this->functions[] = $function;
        }

        return $this;
    }

    /** @param mixed $value */
    public function mockConstant(string $constant, $value): self
    {
        $this->constants[trim($constant)] = $value;

        return $this;
    }

    public function getMock(): MockObject
    {
        $mock = $this->testCase->getMockBuilder('stdClass')
            ->addMethods($this->functions)
            ->setMockClassName('PHPUnit_Extension_FunctionMocker_' . bin2hex(random_bytes(16)))
            ->getMock();

        foreach ($this->constants as $constant => $value) {
            CodeGenerator::defineConstant($this->namespace, $constant, $value);
        }

        foreach ($this->functions as $function) {
            $fqFunction = $this->namespace . '\\' . $function;

            if (in_array($fqFunction, static::$mockedFunctions, true)) {
                continue;
            }

            CodeGenerator::defineFunction($this->namespace, $function);
            static::$mockedFunctions[] = $fqFunction;
        }

        if (!isset($GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER'])) {
            $GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER'] = [];
        }

        $GLOBALS['__PHPUNIT_EXTENSION_FUNCTIONMOCKER'][$this->namespace] = $mock;

        return $mock;
    }
}
