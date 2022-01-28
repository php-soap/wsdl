<?php
declare(strict_types=1);

namespace Soap\Wsdl\Xml\Configurator;

use DOMDocument;
use DOMElement;
use Soap\Xml\Xmlns;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Configurator\Configurator;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function VeeWee\Xml\Dom\Builder\namespaced_element;
use function VeeWee\Xml\Dom\Locator\Node\children;
use function VeeWee\Xml\Dom\Manipulator\append;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;

/**
 * This class transforms multiple wsdl:types elements into 1 single element.
 * This makes importing xsd's easier (and prevents some bugs in some soap related tools)
 */
final class FlattenTypes implements Configurator
{
    /**
     * @throws RuntimeException
     */
    public function __invoke(DOMDocument $document): DOMDocument
    {
        $xml = Document::fromUnsafeDocument($document);
        $xpath = $xml->xpath(new WsdlPreset($xml));
        /** @var list<DOMElement> $types */
        $types = [...$xpath->query('wsdl:types')];

        // Creates wsdl:types if no matching element exists yet
        if (!count($types)) {
            $document->documentElement->append(
                namespaced_element(Xmlns::wsdl()->value(), 'types')($document)
            );

            return $document;
        }

        // Skip if only one exists
        $first = array_shift($types);
        if (!count($types)) {
            return $document;
        }

        // Flattens multiple wsdl:types elements.
        foreach ($types as $additionalTypes) {
            $children = children($additionalTypes);
            if (count($children)) {
                append(...$children)($first);
            }

            remove($additionalTypes);
        }

        return $document;
    }
}
