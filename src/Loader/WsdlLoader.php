<?php

declare(strict_types=1);

namespace Soap\Wsdl\Loader;

use Soap\Wsdl\Exception\UnloadableWsdlException;

/**
 * Loads the content of a WSDL location
 */
interface WsdlLoader
{
    /**
     * @throws UnloadableWsdlException
     */
    public function __invoke(string $location): string;
}
