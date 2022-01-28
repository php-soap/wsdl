<?php
declare(strict_types=1);

namespace Soap\Wsdl\Console\Command;

use Exception;
use Psl\Filesystem;
use Soap\Wsdl\Console\Helper\ConfiguredLoader;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\WsdlLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class FlattenCommand extends Command
{
    public static function getDefaultName(): string
    {
        return 'flatten';
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setDescription('Flatten a remote or local WSDL file into 1 file that contains all includes.');
        $this->addArgument('wsdl', InputArgument::REQUIRED, 'Provide the URI of the WSDL you want to flatten');
        $this->addArgument('output', InputArgument::REQUIRED, 'Define where the file must be written to');
        $this->addOption('loader', 'l', InputOption::VALUE_REQUIRED, 'Customize the WSDL loader file that will be used');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $loader = ConfiguredLoader::createFromConfig(
            $input->getOption('loader'),
            fn (WsdlLoader $loader) => $this->configureLoader($loader, $style)
        );
        $wsdl = $input->getArgument('wsdl');
        $output = $input->getArgument('output');

        $style->info('Flattening WSDL "'.$wsdl.'"');
        $style->warning('This can take a while...');
        $contents = $loader($wsdl);

        $style->info('Downloaded the WSDL. Writing it to "'.$output.'".');

        Filesystem\write_file($output, $contents);

        $style->success('Succesfully flattened your WSDL!');

        return self::SUCCESS;
    }

    private function configureLoader(WsdlLoader $loader, SymfonyStyle $style): WsdlLoader
    {
        return new FlatteningLoader(
            new CallbackLoader(static function (string $location) use ($loader, $style): string {
                $style->write('> Loading '.$location . '...');

                $result =  $loader($location);
                $style->writeln(' DONE!');

                return $result;
            })
        );
    }
}
