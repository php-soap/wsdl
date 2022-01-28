<?php
declare(strict_types=1);

namespace Soap\Wsdl\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\LogicException;

final class AppFactory
{
    /**
     * @throws LogicException
     */
    public static function create(): Application
    {
        $app = new Application('wsdl-tools', '1.0.0');
        $app->addCommands([
            new Command\FlattenCommand(),
            new Command\ValidateCommand(),
        ]);

        return $app;
    }
}
