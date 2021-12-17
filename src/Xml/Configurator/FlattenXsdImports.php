<?php

declare(strict_types=1);

namespace Soap\Wsdl\Xml\Configurator;

use DOMDocument;
use DOMElement;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Uri\IncludePathBuilder;
use Soap\Wsdl\Xml\Exception\FlattenException;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Configurator\Configurator;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function VeeWee\Xml\Dom\Locator\Element\parent_element;
use function VeeWee\Xml\Dom\Locator\Node\children;
use function VeeWee\Xml\Dom\Manipulator\Element\copy_named_xmlns_attributes;
use function VeeWee\Xml\Dom\Manipulator\Node\append_external_node;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;
use function VeeWee\Xml\Dom\Manipulator\Node\replace_by_external_nodes;

/**
 * @TODO check https://github.com/pkielgithub/SchemaLightener/blob/master/src/SchemaLightener.java implementation
 * -> http://www.strategicdevelopment.io/tools/index.php
 * -> http://www.strategicdevelopment.io/tools/SchemaLightener.php
 * -> http://www.strategicdevelopment.io/tools/WSDLFlattener.php
 */
final class FlattenXsdImports implements Configurator
{
    public function __construct(
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
            $schema = match ($import->localName) {
                'include', 'redefine' => $this->includeSchema($import),
                'import' => $this->importSchema($import),
            };

            if ($schema) {
                append_external_node($types, $schema);
            }
        }

        return $document;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     * @throws FlattenException
     */
    private function includeSchema(DOMElement $include): ?DOMElement
    {
        // All includes and redefines require a schemLocation attribute
        if (!$location = $include->getAttribute('schemaLocation')) {
            throw FlattenException::noLocation($include->localName);
        }

        // https://docs.microsoft.com/en-us/previous-versions/dotnet/netframework-4.0/ms256198(v=vs.100)
        /*
         * The included schema document must meet one of the following conditions.
            -    It must have the same target namespace as the containing schema document.
            -    It must not have a target namespace specified (no targetNamespace attribute).
         */


        // Include tags can be removed, since the schema will be made available in the types
        // using the namespace it defines.
        $schema = $this->loadSchema(
            $location,
            fn ($absolutePath): ?string => $this->context->include($absolutePath)
        );

        if (!$schema) {
            remove($include);
            return null;
        }

        copy_named_xmlns_attributes(parent_element($include), $schema);
        replace_by_external_nodes($include, children($schema));

        // Todo -> append the redefine's children as well after import

        return null;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     * @throws FlattenException
     */
    private function importSchema(DOMElement $import): ?DOMElement
    {
        // xsd:import tags don't require a location!
        $location = $import->getAttribute('schemaLocation');
        if (!$location) {
            return null;
        }

        // Normally an import has an owner document, since it is coming from xpath on an existing document
        // However, static analysis does not know about this.
        if (!$import->ownerDocument) {
            return null;
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

        $schema = $this->loadSchema(
            $location,
            fn ($absolutePath): ?string => $this->context->import($absolutePath)
        );
        $import->removeAttribute('schemaLocation');

        return $schema;
    }

    /**
     * @param callable(string): ?string $loader
     *
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     */
    private function loadSchema(string $location, callable $loader): ?DOMElement
    {
        $path = IncludePathBuilder::build($location, $this->currentLocation);
        $result = $loader($path);

        if (!$result) {
            return null;
        }

        $imported = Document::fromXmlString($result);
        /** @var DOMElement $schema */
        $schema = $imported->xpath(new WsdlPreset($imported))->querySingle('/schema:schema');

        return $schema;
    }
}
