<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader;

use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Xml\Flattener;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;

final class FlatteningLoader implements WsdlLoader
{
    public function __construct(
        private WsdlLoader $loader,
    ) {
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     */
    public function __invoke(string $location): string
    {
        $currentDoc = Document::fromXmlString(($this->loader)($location));
        $context = FlatteningContext::forWsdl($location, $currentDoc, $this->loader);

        return (new Flattener())($location, $currentDoc, $context);
    }
}
