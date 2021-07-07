<?php
declare(strict_types=1);

namespace Soap\Wsdl\Exception;

use Exception;
use RuntimeException;
use Throwable;

final class UnloadableWsdlException extends RuntimeException
{
    protected function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromLocation(string $location): self
    {
        return new self('Could not load WSDL from location "'.$location.'".');
    }

    public static function fromException(Exception $e): self
    {
        return new self($e->getMessage(), (int)$e->getCode(), $e);
    }
}
