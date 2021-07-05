<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader;

use Soap\Wsdl\Exception\UnloadableWsdlException;

class StreamWrapperLoader implements WsdlLoader
{
    /**
     * This must be a valid stream context.
     *
     * @var null|resource
     */
    private $context;

    public function __construct($context = null)
    {
        $this->context = $context;
    }
    
    public function __invoke(string $location): string
    {
        $content = file_get_contents(
            $location,
            context: is_resource($this->context) ? $this->context : null
        );

        if ($content === false) {
            throw UnloadableWsdlException::fromLocation($location);
        }

        return $content;
    }
}
