<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader;

final class FlatteningLoader implements WsdlLoader
{
    public function __construct(
        private WsdlLoader $loader
    ){
    }

    public function __invoke(string $location): string
    {
    }
}
