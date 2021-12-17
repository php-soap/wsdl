<?php

declare(strict_types=1);

namespace Soap\Wsdl\Xml\Configurator;

use DOMDocument;
use DOMElement;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Uri\IncludePathBuilder;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Configurator\Configurator;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function VeeWee\Xml\Dom\Locator\document_element;
use function VeeWee\Xml\Dom\Locator\Node\children;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;
use function VeeWee\Xml\Dom\Manipulator\Node\replace_by_external_nodes;

final class FlattenWsdlImports implements Configurator
{
    public function __construct(
        private string $currentLocation,
        private FlatteningContext $context
    ) {
    }

    /**
     * This method flattens wsdl:import locations.
     * It loads the WSDL and adds the definitions replaces the import tag with the definition children from the external file.
     *
     * For now, we don't care about the namespace property on the wsdl:import tag.
     * Future reference:
     * @link http://itdoc.hitachi.co.jp/manuals/3020/30203Y2310e/EY230669.HTM#ID01496
     *
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     */
    public function __invoke(DOMDocument $document): DOMDocument
    {
        $xml = Document::fromUnsafeDocument($document);
        $xpath = $xml->xpath(new WsdlPreset($xml));

        $imports = $xpath->query('wsdl:import');
        $imports->forEach(fn (DOMElement $import) => $this->importWsdlImportElement($import));

        return $document;
    }

    /**
     * @throws RuntimeException
     * @throws UnloadableWsdlException
     */
    private function importWsdlImportElement(DOMElement $import): void
    {
        $location = IncludePathBuilder::build(
            $import->getAttribute('location'),
            $this->currentLocation
        );

        $result = $this->context->import($location);
        if (!$result) {
            remove($import);
            return;
        }

        $imported = Document::fromXmlString($result);
        $definitions = $imported->map(document_element());

        replace_by_external_nodes(
            $import,
            children($definitions)
        );
    }
}
