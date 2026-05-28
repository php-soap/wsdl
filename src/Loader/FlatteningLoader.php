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
        $location = self::normalizeLocation($location);
        $content = ($this->loader)($location);
        if ($content === '') {
            throw UnloadableWsdlException::noContentAt($location);
        }

        $currentDoc = Document::fromXmlString($content);
        $context = FlatteningContext::forWsdl($location, $currentDoc, $this->loader);

        return (new Flattener())($location, $currentDoc, $context);
    }

    /**
     * Ensures the base location used for resolving imports is absolute.
     * Why: league/uri >= 7.6 throws when resolving a relative reference against a non-absolute base.
     *
     * @throws UnloadableWsdlException
     */
    private static function normalizeLocation(string $location): string
    {
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $location) === 1) {
            return $location;
        }

        $absolute = realpath($location);
        if ($absolute === false) {
            throw UnloadableWsdlException::fromLocation($location);
        }

        return $absolute;
    }
}
