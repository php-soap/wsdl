<?php

declare(strict_types=1);

namespace Soap\Wsdl\Uri;

use League\Uri\BaseUri;
use League\Uri\Modifier;

final class IncludePathBuilder
{
    public static function build(string $relativePath, string $fromFile): string
    {
        $baseUri = BaseUri::from($fromFile);
        $uri = $baseUri->resolve($relativePath);

        return Modifier::from($uri)
            ->removeDotSegments()
            ->removeEmptySegments()
            ->getUri()
            ->toString()
        ;
    }
}
