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
                'import' => $this->importSchema($import),
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
        // All includes and redefines require a schemLocation attribute
        if (!$location = $include->getAttribute('schemaLocation')) {
            throw FlattenException::noLocation($include->localName);
        }

        // Include tags can be removed, since the schema will be made available in the types
        // using the namespace it defines.
        $schemas = $this->loadSchemas($location);
        $include->remove();

        return $schemas;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     * @throws FlattenException
     *
     * @return iterable<DOMElement>
     */
    private function importSchema(DOMElement $import): iterable
    {
        // xsd:import tags don't require a location!
        $location = $import->getAttribute('schemaLocation');
        if (!$location) {
            return [];
        }

        // Normally an import has an owner document, since it is coming from xpath on an existing document
        // However, static analysis does not know about this.
        if (!$import->ownerDocument) {
            return [];
        }

        // Find the schema that wants to import the new schema:
        $doc = Document::fromUnsafeDocument($import->ownerDocument);
        $xpath = $doc->xpath(new WsdlPreset($doc));
        /** @var DOMElement $schema */
        $schema = $xpath->querySingle('ancestor::schema:schema', $import);

        // Detect namespaces from both the current schema and the import
        $namespace = $import->getAttribute('namespace');
        $tns = $schema->getAttribute('targetNamespace');

        // Imports can only deal with different namespaces.
        // You'll need to use "include" if you want to inject something in the same namespace.
        if ($tns && $namespace && $tns === $namespace) {
            throw FlattenException::unableToImportXsd($location);
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
