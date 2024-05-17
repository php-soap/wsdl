<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader;

use Exception;
use Soap\Wsdl\Exception\UnloadableWsdlException;

final class StreamWrapperLoader implements WsdlLoader
{
    /**
     * This must be a valid stream context.
     *
     * @var null|resource
     */
    private $context;

    /**
     * @param null|resource $context
     */
    public function __construct($context = null)
    {
        $this->context = $context;
    }

    public function __invoke(string $location): string
    {
        try {
            $content = @file_get_contents(
                $location,
                context: is_resource($this->context) ? $this->context : null
            );
        } catch (Exception $e) {
            throw UnloadableWsdlException::fromException($e);
        }

        if ($content === false) {
            throw UnloadableWsdlException::fromLocation($location);
        }

        return $content;
    }
}
