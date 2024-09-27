<?php declare(strict_types=1);

namespace Soap\Wsdl\Xml\Xmlns;

use DOMElement;
use DOMNameSpaceNode;
use Psl\Option\Option;
use RuntimeException;
use Soap\Wsdl\Xml\Visitor\ReprefixTypeQname;
use VeeWee\Xml\Dom\Collection\NodeList;
use VeeWee\Xml\Dom\Document;
use function Psl\Dict\merge;
use function Psl\Option\none;
use function Psl\Option\some;
use function VeeWee\Xml\Dom\Builder\xmlns_attribute;
use function VeeWee\Xml\Dom\Locator\Xmlns\linked_namespaces;

/**
 * Cross-import schemas can contain namespace conflicts.
 *
 * For example: import1 requires import2:
 *
 * - Import 1 specifies xmlns:ns1="urn:1"
 * - Import 2 specifies xmlns:ns1="urn:2".
 *
 * This method will detect conflicting namespaces and resolve them.
 * Namespaces will be renamed to a unique name and the "type" argument with QName's will be re-prefixed.
 *
 * @psalm-type RePrefixMap=array<string, string>
 */
final class RegisterNonConflictingXmlnsNamespaces
{
    /**
     * @throws RuntimeException
     */
    public function __invoke(DOMElement $existingSchema, DOMElement $newSchema): void
    {
        $existingLinkedNamespaces = linked_namespaces($existingSchema);

        $rePrefixMap = linked_namespaces($newSchema)->reduce(
            /**
             * @param RePrefixMap $rePrefixMap
             * @return RePrefixMap
             */
            function (array $rePrefixMap, DOMNameSpaceNode $xmlns) use ($existingSchema, $existingLinkedNamespaces): array {
                // Skip non-named xmlns attributes:
                if (!$xmlns->prefix) {
                    return $rePrefixMap;
                }

                // Check for duplicates:
                if ($existingSchema->hasAttribute($xmlns->nodeName) && $existingSchema->getAttribute($xmlns->nodeName) !== $xmlns->prefix) {
                    return merge(
                        $rePrefixMap,
                        // Can be improved with orElse when we are using PSL V3.
                        $this->tryUsingExistingPrefix($existingLinkedNamespaces, $xmlns)
                            ->unwrapOrElse(
                                fn () => $this->tryUsingUniquePrefixHash($existingSchema, $xmlns)
                                    ->unwrapOrElse(
                                        static fn () => throw new RuntimeException('Could not resolve conflicting namespace declarations whilst flattening your WSDL file.')
                                    )
                            )
                    );
                }

                xmlns_attribute($xmlns->prefix, $xmlns->namespaceURI)($existingSchema);

                return $rePrefixMap;
            },
            []
        );

        if (count($rePrefixMap)) {
            Document::fromUnsafeDocument($newSchema->ownerDocument)->traverse(new ReprefixTypeQname($rePrefixMap));
        }
        (new FixRemovedDefaultXmlnsDeclarationsDuringImport())($existingSchema, $newSchema);
    }

    /**
     * @param NodeList<DOMNameSpaceNode> $existingLinkedNamespaces
     *
     * @return Option<RePrefixMap>
     */
    private function tryUsingExistingPrefix(
        NodeList $existingLinkedNamespaces,
        DOMNameSpaceNode $xmlns
    ): Option {
        $existingPrefix = $existingLinkedNamespaces->filter(
            static fn (DOMNameSpaceNode $node) => $node->namespaceURI === $xmlns->namespaceURI
        )->first()?->prefix;

        if ($existingPrefix === null) {
            /** @var Option<RePrefixMap> */
            return none();
        }

        /** @var Option<RePrefixMap> */
        return some([$xmlns->prefix => $existingPrefix]);
    }

    /**
     * @return Option<RePrefixMap>
     *
     * @throws RuntimeException
     */
    private function tryUsingUniquePrefixHash(
        DOMElement $existingSchema,
        DOMNameSpaceNode $xmlns
    ): Option {
        $uniquePrefix = 'ns' . substr(md5($xmlns->namespaceURI), 0, 8);
        if ($existingSchema->hasAttribute('xmlns:'.$uniquePrefix)) {
            /** @var Option<RePrefixMap> */
            return none();
        }

        $this->copyXmlnsDeclaration($existingSchema, $xmlns->namespaceURI, $uniquePrefix);

        /** @var Option<RePrefixMap> */
        return some([$xmlns->prefix => $uniquePrefix]);
    }

    /**
     * @throws RuntimeException
     */
    private function copyXmlnsDeclaration(DOMElement $existingSchema, string $namespaceUri, string $prefix): void
    {
        xmlns_attribute($prefix, $namespaceUri)($existingSchema);
    }
}
