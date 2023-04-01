<?php
declare(strict_types=1);

namespace Soap\Wsdl\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\LogicException;

final class AppFactory
{
    /**
     * @psalm-suppress UndefinedClass
     * @var list<class-string>
     */
    private static array $configurators = [
        \Soap\WsdlReader\Console\WsdlReaderConfigurator::class
    ];

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

        self::configure($app);

        return $app;
    }

    private static function configure(Application $app): void
    {
        foreach (self::$configurators as $configurator) {
            if (!class_exists($configurator) || !is_a($configurator, Configurator::class, true)) {
                continue;
            }

            $configurator::configure($app);
        }
    }
}
