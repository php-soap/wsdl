<?php
declare(strict_types=1);

namespace Soap\Wsdl\Exception;

use Throwable;

final class UnloadableWsdlException extends \RuntimeException
{
    protected function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromLocation(string $location): self
    {
        return new self('Could not load WSDL from location "'.$location.'".');
    }
}
