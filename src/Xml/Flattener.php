<?php
declare(strict_types=1);

namespace Soap\Wsdl\Xml;

use Dom\XMLDocument;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Xml\Configurator\FlattenTypes;
use Soap\Wsdl\Xml\Configurator\FlattenWsdlImports;
use Soap\Wsdl\Xml\Configurator\FlattenXsdImports;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;
use function Psl\Fun\pipe;
use function Psl\Fun\when;
use function VeeWee\Xml\Dom\Configurator\utf8;
use function VeeWee\Xml\Dom\Locator\document_element;

final class Flattener
{
    /**
     * @throws RuntimeException
     */
    public function __invoke(
        string $location,
        Document $document,
        FlatteningContext $context
    ): string {
        return Document::fromUnsafeDocument(
            $document->toUnsafeDocument(),
            pipe(
                utf8(),
                when(
                    static fn (XMLDocument $document): bool => document_element()($document)->localName === 'definitions',
                    pipe(
                        (new FlattenWsdlImports($location, $context))(...),
                        (new FlattenTypes())(...),
                        (new FlattenXsdImports($location, $context))(...),
                    ),
                    (new FlattenXsdImports($location, $context))(...)
                )
            )
        )->toXmlString();
    }
}
