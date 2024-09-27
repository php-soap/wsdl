<?php declare(strict_types=1);

namespace Soap\Wsdl\Xml\Visitor;

use DOMNode;
use VeeWee\Xml\Dom\Traverser\Action;
use VeeWee\Xml\Dom\Traverser\Visitor;
use function VeeWee\Xml\Dom\Predicate\is_attribute;

final class ReprefixTypeQname extends Visitor\AbstractVisitor
{
    /**
     * @param array<string, string> $prefixMap - "From" key - "To" value prefix map
     */
    public function __construct(
        private readonly array $prefixMap
    ) {
    }

    public function onNodeEnter(DOMNode $node): Action
    {
        if (!is_attribute($node) || $node->localName !== 'type') {
            return new Action\Noop();
        }

        $parts = explode(':', $node->nodeValue ?? '', 2);
        if (count($parts) !== 2) {
            return new Action\Noop();
        }

        [$currentPrefix, $currentTypeName] = $parts;
        if (!array_key_exists($currentPrefix, $this->prefixMap)) {
            return new Action\Noop();
        }

        $node->nodeValue = $this->prefixMap[$currentPrefix].':'.$currentTypeName;

        return new Action\Noop();
    }
}
