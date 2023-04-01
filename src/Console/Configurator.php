<?php
declare(strict_types=1);

namespace Soap\Wsdl\Console;

use Symfony\Component\Console\Application;

interface Configurator
{
    public static function configure(Application $application): void;
}
