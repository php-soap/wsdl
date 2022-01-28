<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader;

use Exception;
use Soap\Wsdl\Exception\UnloadableWsdlException;

final class CallbackLoader implements WsdlLoader
{
    /**
     * @param callable(string): string $callback
     */
    private $callback;

    /**
     * @param callable(string): string $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @throws UnloadableWsdlException
     */
    public function __invoke(string $location): string
    {
        try {
            return ($this->callback)($location);
        } catch (UnloadableWsdlException $e) {
            throw $e;
        } catch (Exception $e) {
            throw UnloadableWsdlException::fromException($e);
        }
    }
}
