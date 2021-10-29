<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader;

use DOMDocument;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Xml\Configurator\FlattenTypes;
use Soap\Wsdl\Xml\Configurator\FlattenWsdlImports;
use Soap\Wsdl\Xml\Configurator\FlattenXsdImports;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function Psl\Fun\pipe;
use function Psl\Fun\when;
use function VeeWee\Xml\Dom\Configurator\utf8;

final class FlatteningLoader implements WsdlLoader
{
    private function __construct(
        private WsdlLoader $loader,
        private ?FlatteningContext $context = null
    ) {
    }

    public static function createForLoader(WsdlLoader $loader): self
    {
        return new self($loader);
    }

    /**
     * This loader works in 2 different modes:
     * * When no context is provided yet, we are importing a new WSDL file - which will have its own context storage.
     * * When a context is provided, we are parsing an "import" wsdl or xsd document - which will have a shared context
     *
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     */
    public function __invoke(string $location): string
    {
        $currentDoc = Document::fromXmlString(($this->loader)($location));
        $context = $this->context ?: FlatteningContext::forWsdl($location, $currentDoc);
        $loader = new self($this->loader, $context);

        return $this->runConfiguratorsOnDocument($location, $currentDoc, $context, $loader);
    }

    /**
     * @throws RuntimeException
     */
    private function runConfiguratorsOnDocument(
        string $location,
        Document $document,
        FlatteningContext $context,
        WsdlLoader $loader
    ): string {
        // Make sure that the location is registered as imported!
        $context->announceImport($location);

        return Document::fromUnsafeDocument(
            $document->toUnsafeDocument(),
            pipe(
                utf8(),
                when(
                    static fn (DOMDocument $document): bool => $document->documentElement->localName === 'definitions',
                    pipe(
                        new FlattenWsdlImports($loader, $location, $context),
                        new FlattenTypes(),
                        new FlattenXsdImports($loader, $location, $context)
                    ),
                    new FlattenXsdImports($loader, $location, $context)
                )
            )
        )->toXmlString();
    }
}
