<?php declare(strict_types=1);

namespace Soap\Wsdl\Xml\Xmlns;

use Dom\Element;
use Dom\NamespaceInfo;
use Psl\Option\Option;
use RuntimeException;
use Soap\Wsdl\Xml\Visitor\ReprefixTypeQname;
use VeeWee\Xml\Dom\Document;
use function Psl\Dict\merge;
use function Psl\Option\none;
use function Psl\Option\some;
use function VeeWee\Xml\Dom\Assert\assert_document;
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
    public function __invoke(Element $existingSchema, Element $newSchema): void
    {
        $existingLinkedNamespaces = linked_namespaces($existingSchema);

        /** @var RePrefixMap $rePrefixMap */
        $rePrefixMap = array_reduce(
            linked_namespaces($newSchema),
            /**
             * @param RePrefixMap $rePrefixMap
             * @return RePrefixMap
             */
            function (array $rePrefixMap, NamespaceInfo $xmlns) use ($existingSchema, $existingLinkedNamespaces): array {
                $prefix = $xmlns->prefix;
                $namespaceURI = $xmlns->namespaceURI;

                // Skip non-named xmlns attributes:
                if ($prefix === null || $prefix === '' || $namespaceURI === null) {
                    return $rePrefixMap;
                }

                // Check for duplicates:
                $attrName = 'xmlns:'.$prefix;
                $existingValue = $existingSchema->getAttribute($attrName);
                if ($existingValue !== null && $existingValue !== $namespaceURI) {
                    return merge(
                        $rePrefixMap,
                        $this->tryUsingExistingPrefix($existingLinkedNamespaces, $prefix, $namespaceURI)
                            ->unwrapOrElse(
                                fn () => $this->tryUsingUniquePrefixHash($existingSchema, $prefix, $namespaceURI)
                                    ->unwrapOrElse(
                                        static fn () => throw new RuntimeException('Could not resolve conflicting namespace declarations whilst flattening your WSDL file.')
                                    )
                            )
                    );
                }

                xmlns_attribute($prefix, $namespaceURI)($existingSchema);

                return $rePrefixMap;
            },
            []
        );

        if (count($rePrefixMap)) {
            Document::fromUnsafeDocument(assert_document($newSchema->ownerDocument))->traverse(new ReprefixTypeQname($rePrefixMap));
        }
    }

    /**
     * @param list<NamespaceInfo> $existingLinkedNamespaces
     *
     * @return Option<RePrefixMap>
     */
    private function tryUsingExistingPrefix(
        array $existingLinkedNamespaces,
        string $prefix,
        string $namespaceURI
    ): Option {
        $existingPrefix = null;
        foreach ($existingLinkedNamespaces as $node) {
            if ($node->namespaceURI === $namespaceURI) {
                $existingPrefix = $node->prefix;
                break;
            }
        }

        if ($existingPrefix === null) {
            /** @var Option<RePrefixMap> */
            return none();
        }

        /** @var Option<RePrefixMap> */
        return some([$prefix => $existingPrefix]);
    }

    /**
     * @return Option<RePrefixMap>
     *
     * @throws RuntimeException
     */
    private function tryUsingUniquePrefixHash(
        Element $existingSchema,
        string $prefix,
        string $namespaceURI
    ): Option {
        $uniquePrefix = 'ns' . substr(md5($namespaceURI), 0, 8);
        if ($existingSchema->hasAttribute('xmlns:'.$uniquePrefix)) {
            /** @var Option<RePrefixMap> */
            return none();
        }

        $this->copyXmlnsDeclaration($existingSchema, $namespaceURI, $uniquePrefix);

        /** @var Option<RePrefixMap> */
        return some([$prefix => $uniquePrefix]);
    }

    /**
     * @throws RuntimeException
     */
    private function copyXmlnsDeclaration(Element $existingSchema, string $namespaceUri, string $prefix): void
    {
        xmlns_attribute($prefix, $namespaceUri)($existingSchema);
    }
}
