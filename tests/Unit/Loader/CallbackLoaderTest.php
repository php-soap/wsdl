<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Unit\Loader;

use Exception;
use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\CallbackLoader;

final class CallbackLoaderTest extends TestCase
{
    public function test_it_can_load_wsdl_through_callback(): void
    {
        $loader = new CallbackLoader(static fn (string $wsdl): string => $wsdl);
        $contents = ($loader)('wsdl');

        static::assertSame('wsdl', $contents);
    }

    public function test_it_transforms_exceptions(): void
    {
        $loader = new CallbackLoader(static fn (string $wsdl): string => throw new Exception('hello'));

        $this->expectException(UnloadableWsdlException::class);
        $this->expectExceptionMessage('hello');
        ($loader)('wsdl');
    }

    public function test_it_does_not_transforms_unloadable_exception(): void
    {
        $loader = new CallbackLoader(
            static fn (string $wsdl): string => throw UnloadableWsdlException::fromLocation($wsdl)
        );

        $this->expectException(UnloadableWsdlException::class);
        $this->expectExceptionMessage('file.wsdl');
        ($loader)('file.wsdl');
    }
}
