<?php

declare(strict_types=1);

namespace Soap\Wsdl\Xml\Configurator;

use DOMDocument;
use DOMElement;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Loader\WsdlLoader;
use Soap\Wsdl\Uri\IncludePathBuilder;
use Soap\Wsdl\Xml\Exception\FlattenException;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Configurator\Configurator;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function VeeWee\Xml\Dom\Locator\Node\children;
use function VeeWee\Xml\Dom\Manipulator\Node\append_external_node;

final class FlattenXsdImports implements Configurator
{
    public function __construct(
        private WsdlLoader $wsdlLoader,
        private string $currentLocation,
        private FlatteningContext $context
    ) {
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     * @throws FlattenException
     */
    public function __invoke(DOMDocument $document): DOMDocument
    {
        $xml = Document::fromUnsafeDocument($document);
        $xpath = $xml->xpath(new WsdlPreset($xml));

        $imports = $xpath
            ->query('//schema:import|//schema:include|//schema:redefine')
            ->expectAllOfType(DOMElement::class);

        if (!count($imports)) {
            return $document;
        }

        $types = $this->context->types();
        foreach ($imports as $import) {
            $schemas = match ($import->localName) {
                'include', 'redefine' => $this->includeSchema($import),
                'import' => $this->importSchema($document, $import),
            };

            foreach ($schemas as $schema) {
                append_external_node($types, $schema);
            }
        }

        return $document;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     * @throws FlattenException
     *
     * @return iterable<DOMElement>
     */
    private function includeSchema(DOMElement $include): iterable
    {
        if (!$location = $include->getAttribute('schemaLocation')) {
            throw FlattenException::noLocation($include->localName);
        }

        $schemas = $this->loadSchemas($location);
        $include->remove();

        return $schemas;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     *
     * @return iterable<DOMElement>
     */
    private function importSchema(DOMDocument $wsdl, DOMElement $import): iterable
    {
        $location = $import->getAttribute('schemaLocation');
        $namespace = $import->getAttribute('namespace');
        $tns = $wsdl->documentElement->getAttribute('targetNamespace');

        // Imports can only deal with different namespaces.
        // You'll need to use "include" if you want to inject something in the same namespace.
        if ($tns && $namespace && $tns === $namespace) {
            // TODO : exception seems a bit too harsh ... Regular import ok?
            //throw FlattenException::unableToImportXsd($location);
        }

        // xsd:import tags don't require a location!
        if (!$location) {
            return [];
        }

        $schemas = $this->loadSchemas($location);
        $import->removeAttribute('schemaLocation');

        return $schemas;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     *
     * @return iterable<DOMElement>
     */
    private function loadSchemas(string $location): iterable
    {
        $path = IncludePathBuilder::build($location, $this->currentLocation);

        if (!$this->context->announceImport($path)) {
            return [];
        }

        $xml = ($this->wsdlLoader)($path);
        $imported = Document::fromXmlString($xml)->toUnsafeDocument();

        return children($imported)
            ->expectAllOfType(DOMElement::class)
            ->filter(static fn (DOMElement $element) => $element->localName === 'schema');
    }
}
