<?php

declare(strict_types=1);

namespace Soap\Wsdl\Xml\Validator;

use DOMDocument;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Dom\Validator\Validator;
use VeeWee\Xml\ErrorHandling\Issue\IssueCollection;
use VeeWee\Xml\Exception\RuntimeException;
use function VeeWee\Xml\Dom\Validator\xsd_validator;

final class WsdlSyntaxValidator implements Validator
{
    private string $xsd;

    /**
     * @param non-empty-string|null $xsd
     */
    public function __construct(?string $xsd = null)
    {
        $this->xsd = $xsd ?? dirname(__DIR__, 3).'/xsd/wsdl.xsd';
    }

    /**
     * @throws RuntimeException
     */
    public function __invoke(DOMDocument $document): IssueCollection
    {
        return Document::fromUnsafeDocument($document)->validate(xsd_validator($this->xsd));
    }
}
