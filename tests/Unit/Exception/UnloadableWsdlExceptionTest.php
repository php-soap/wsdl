<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soap\Wsdl\Exception\UnloadableWsdlException;

final class UnloadableWsdlExceptionTest extends TestCase
{
    
    public function test_it_can_not_load_from_location(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectException(UnloadableWsdlException::class);
        $this->expectExceptionMessage('location.wsdl');

        throw UnloadableWsdlException::fromLocation('location.wsdl');
    }

    
    public function test_it_can_not_load_with_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectException(UnloadableWsdlException::class);
        $this->expectExceptionMessage('nope');

        $e = new Exception('nope');
        throw UnloadableWsdlException::fromException($e);
    }
}
