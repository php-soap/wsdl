<?php

declare(strict_types=1);

namespace Soap\Wsdl\Xml\Exception;

use RuntimeException;

final class FlattenException extends RuntimeException
{
    public static function noLocation(string $elementName): self
    {
        return new self("Parsing Schema: {$elementName} has no 'schemaLocation' attribute");
    }

    public static function invalidIncludeTargetNamespace(string $parentTns, string $currentTns): self
    {
        return new self("Parsing Schema: include has an invalid targetNamespace of '$currentTns'. Expected '$parentTns'");
    }

    public static function unableToImportXsd(string $location): self
    {
        $target = $location ? ' from '.$location : '';

        return new self("Parsing Schema: can't import schema{$target}. Namespace must not match the enclosing schema 'targetNamespace'");
    }
}
