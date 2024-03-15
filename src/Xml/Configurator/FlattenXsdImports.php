<?php

declare(strict_types=1);

namespace Soap\Wsdl\Xml\Configurator;

use DOMDocument;
use DOMElement;
use DOMNode;
use Psl\Type\Exception\AssertException;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Uri\IncludePathBuilder;
use Soap\Wsdl\Xml\Exception\FlattenException;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Configurator\Configurator;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function Psl\Type\instance_of;
use function Psl\Type\nullable;
use function VeeWee\Xml\Dom\Assert\assert_element;
use function VeeWee\Xml\Dom\Locator\Node\children;
use function VeeWee\Xml\Dom\Manipulator\Element\copy_named_xmlns_attributes;
use function VeeWee\Xml\Dom\Manipulator\Node\append_external_node;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;

/**
 * This class deals with xsd:import, xsd:include and xsd:redefine tags.
 * It imports the types grouped by schema namespace to make sure that the size of the result is as small as possible!
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

        foreach ($imports as $import) {
            $schema = match ($import->localName) {
                'include', 'redefine' => $this->includeSchema($import),
                'import' => $this->importSchema($import),
            };

            if ($schema) {
                $this->registerSchemaInTypes($schema);
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

        /*
         * Currently we do not validate the namespace of includes - we assume the provided imports are valid!
         *
         * @see https://docs.microsoft.com/en-us/previous-versions/dotnet/netframework-4.0/ms256198(v=vs.100)
         * The included schema document must meet one of the following conditions.
            -    It must have the same target namespace as the containing schema document.
            -    It must not have a target namespace specified (no targetNamespace attribute).
         */

        $schema = $this->loadSchema($location);

        // Redefines overwrite tags from includes.
        // The children of redefine elements are appended to the newly loaded schema.
        if ($schema && $include->localName === 'redefine') {
            children($include)->map(
                static fn (DOMNode $node) => append_external_node($schema, $node)
            );
        }

        // Include tags can be removed, since the schema will be made available in the types
        // using the namespace it defines. The include/redefine tag will have no purpose anymore.
        remove($include);

        return $schema;
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

        // After loading the schema, the import element needs to remain in the XSD.
        // The schema location is removed, since it will be embedded in the WSDL.
        $schema = $this->loadSchema($location);
        $import->removeAttribute('schemaLocation');

        return $schema;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     */
    private function loadSchema(string $location): ?DOMElement
    {
        $path = IncludePathBuilder::build($location, $this->currentLocation);
        $result = $this->context->import($path);

        if ($result === null || $result === '') {
            return null;
        }

        $imported = Document::fromXmlString($result);
        /** @var DOMElement $schema */
        $schema = $imported->xpath(new WsdlPreset($imported))->querySingle('/schema:schema');

        return $schema;
    }

    /**
     * This function registers the newly provided schema in the WSDL types section.
     * It groups all imports by targetNamespace.
     *
     * @throws RuntimeException
     * @throws AssertException
     */
    private function registerSchemaInTypes(DOMElement $schema): void
    {
        $wsdl = $this->context->wsdl();
        $xpath = $wsdl->xpath(new WsdlPreset($wsdl));
        $types = $this->context->types();
        $tns = $schema->getAttribute('targetNamespace');

        $query = $tns ? './schema:schema[@targetNamespace=\''.$tns.'\']' : './schema:schema[not(@targetNamespace)]';
        $existingSchema = nullable(instance_of(DOMElement::class))
            ->assert($xpath->query($query, $types)->first());

        // If no schema exists yet: Add the newly loaded schema as a completely new schema in the WSDL types.
        if (!$existingSchema) {
            $imported = assert_element(append_external_node($types, $schema));
            $this->fixRemovedDefaultXmlnsDeclarationsDuringImport($imported, $schema);
            return;
        }

        // When an existing schema exists, all xmlns attributes need to be copied.
        // This is to make sure that possible QNames (strings) get resolved in XSD.
        // Finally - all children of the newly loaded schema can be appended to the existing schema.
        copy_named_xmlns_attributes($existingSchema, $schema);
        $this->fixRemovedDefaultXmlnsDeclarationsDuringImport($existingSchema, $schema);
        children($schema)->forEach(
            static fn (DOMNode $node) => append_external_node($existingSchema, $node)
        );
    }

    /**
     * @see https://gist.github.com/veewee/32c3aa94adcf878700a9d5baa4b2a2de
     *
     * PHP does an optimization of namespaces during `importNode()`.
     * In some cases, this causes the root xmlns to be removed from the imported node which could lead to xsd qname errors.
     *
     * This function tries to re-add the root xmlns if it's available on the source but not on the target.
     *
     * It will most likely be solved in PHP 8.4's new spec compliant DOM\XMLDocument implementation.
     * @see https://github.com/php/php-src/pull/13031
     *
     * For now, this will do the trick.
     */
    private function fixRemovedDefaultXmlnsDeclarationsDuringImport(DOMElement $target, DOMElement $source): void
    {
        if (!$source->getAttribute('xmlns') || $target->hasAttribute('xmlns')) {
            return;
        }

        $target->setAttribute('xmlns', $source->getAttribute('xmlns'));
    }
}
