<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Exception;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Exception\UnloadableWsdlException;

class UnloadableWsdlExceptionTest extends TestCase
{
    /** @test */
    public function it_can_not_load_from_location(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectException(UnloadableWsdlException::class);
        $this->expectExceptionMessage('location.wsdl');

        throw UnloadableWsdlException::fromLocation('location.wsdl');
    }

    /** @test */
    public function it_can_not_load_with_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectException(UnloadableWsdlException::class);
        $this->expectExceptionMessage('nope');

        $e = new \Exception('nope');
        throw UnloadableWsdlException::fromException($e);
    }

}