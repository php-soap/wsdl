<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Unit\Loader;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\StreamWrapperLoader;

final class StreamWrapperLoaderTest extends TestCase
{
    private StreamWrapperLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new StreamWrapperLoader();
    }

    
    public function test_it_can_load_through_stream_wrapper(): void
    {
        $file = FIXTURE_DIR . '/wsdl.wsdl';
        $contents = ($this->loader)($file);

        static::assertSame($contents, file_get_contents($file));
    }

    
    public function test_it_fails_on_invalid_file(): void
    {
        $file = FIXTURE_DIR . '/invalid.wsdl';
        $this->expectException(UnloadableWsdlException::class);
        ($this->loader)($file);
    }

    
    public function test_it_can_pass_stream_context(): void
    {
        $context = stream_context_create(['http' => []]);
        $loader = new StreamWrapperLoader($context);

        $file = FIXTURE_DIR . '/wsdl.wsdl';
        $contents = ($loader)($file);

        static::assertSame($contents, file_get_contents($file));
    }
}
