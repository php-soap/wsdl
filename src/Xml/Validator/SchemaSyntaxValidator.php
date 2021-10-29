<?php

declare(strict_types=1);

namespace Soap\Soap\Xml\Validator;

use DOMDocument;
use DOMElement;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Dom\Validator\Validator;
use VeeWee\Xml\ErrorHandling\Issue\IssueCollection;
use function VeeWee\Xml\Dom\Validator\xsd_validator;

class SchemaSyntaxValidator implements Validator
{
    private string $xsd;

    public function __construct(?string $xsd = null)
    {
        $this->xsd = $xsd ?: dirname(__DIR__, 3).'/xsd/XMLSchema.xsd';
    }

    public function __invoke(DOMDocument $document): IssueCollection
    {
        $xml = Document::fromUnsafeDocument($document);
        $xpath = $xml->xpath(new WsdlPreset($xml));

        return $xpath->query('//schema:schema')->reduce(
            fn (IssueCollection $issues, DOMElement $schema) => new IssueCollection(
                ...$issues,
                ...Document::fromXmlNode($schema)->validate(xsd_validator($this->xsd))
            ),
            new IssueCollection()
        );
    }
}
