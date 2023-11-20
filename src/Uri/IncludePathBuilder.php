<?php

declare(strict_types=1);

namespace Soap\Wsdl\Uri;

use League\Uri\BaseUri;
use League\Uri\Modifier;

final class IncludePathBuilder
{
    public static function build(string $relativePath, string $fromFile): string
    {
        return Modifier::from(BaseUri::from($fromFile)->resolve($relativePath))
            ->removeDotSegments()
            ->removeEmptySegments()
            ->getUri()
            ->toString()
        ;
    }
}
