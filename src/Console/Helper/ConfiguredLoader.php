<?php
declare(strict_types=1);

namespace Soap\Wsdl\Console\Helper;

use Psl\Filesystem;
use Psl\Type\Exception\AssertException;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\Wsdl\Loader\WsdlLoader;
use function Psl\invariant;
use function Psl\Type\instance_of;

final class ConfiguredLoader
{
    /**
     * @param (callable(WsdlLoader): WsdlLoader)|null $configurator
     *
     * @throws AssertException
     *
     * @psalm-suppress UnresolvableInclude - Including dynamic includes is acutally the goal :)
     */
    public static function createFromConfig(?string $file, callable $configurator = null): WsdlLoader
    {
        $loader = new StreamWrapperLoader();

        if ($file) {
            invariant(Filesystem\exists($file), 'File "%s" does not exist.', $file);
            invariant(Filesystem\is_file($file), 'File "%s" is not a file.', $file);
            invariant(Filesystem\is_readable($file), 'File "%s" is not readable.', $file);

            /** @var WsdlLoader $included */
            $included = require $file;

            $loader = instance_of(WsdlLoader::class)->assert($included);
        }

        return $configurator ? $configurator($loader) : $loader;
    }
}
