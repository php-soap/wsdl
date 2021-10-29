<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader\Context;

use DOMElement;
use Soap\Wsdl\Xml\Configurator\FlattenTypes;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Exception\RuntimeException;

final class FlatteningContext
{
    /** @var array<string, true> */
    private $imports = [];

    public static function forWsdl(string $location, Document $wsdl): self
    {
        $new = new self($wsdl);
        $new->announceImport($location);

        return $new;
    }

    private function __construct(
        private Document $wsdl
    ) {
    }

    public function isImported(string $location): bool
    {
        return array_key_exists($location, $this->imports);
    }

    /**
     * Announce a new import and decide whether it needs to be imported or not.
     */
    public function announceImport(string $location): bool
    {
        $exists = array_key_exists($location, $this->imports);
        if ($exists) {
            return false;
        }

        $this->imports[$location] = true;

        return true;
    }

    public function wsdl(): Document
    {
        return $this->wsdl;
    }

    /**
     * This method searches for a single <wsdl:types /> tag
     * If no tag exists, it will create an empty one.
     * If multiple tags exist, it will merge those tags into one.
     *
     * @throws RuntimeException
     */
    public function types(): DOMElement
    {
        $doc = Document::fromUnsafeDocument($this->wsdl->toUnsafeDocument(), new FlattenTypes());
        $xpath = $doc->xpath(new WsdlPreset($doc));

        /** @var DOMElement $types */
        $types = $xpath->querySingle('//wsdl:types');

        return $types;
    }
}
