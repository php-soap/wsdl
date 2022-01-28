<?php
declare(strict_types=1);

namespace Soap\Wsdl\Console\Command;

use Exception;
use Soap\Wsdl\Console\Helper\ConfiguredLoader;
use Soap\Wsdl\Xml\Validator;
use Soap\Xml\Xpath\WsdlPreset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\ErrorHandling\Issue\IssueCollection;

final class ValidateCommand extends Command
{
    public static function getDefaultName(): string
    {
        return 'validate';
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setDescription('Run validations a (flattened) WSDL file.');
        $this->addArgument('wsdl', InputArgument::REQUIRED, 'Provide the URI of the WSDL you want to validate');
        $this->addOption('loader', 'l', InputOption::VALUE_REQUIRED, 'Customize the WSDL loader file that will be used');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $loader = ConfiguredLoader::createFromConfig($input->getOption('loader'));
        $wsdl = $input->getArgument('wsdl');

        $style->info('Loading "'.$wsdl.'"...');
        $document = Document::fromXmlString($loader($wsdl));
        $xpath = $document->xpath(new WsdlPreset($document));

        $result = $this->runValidationStage(
            $style,
            'Validating WSDL syntax',
            static fn () => $document->validate(new Validator\WsdlSyntaxValidator())
        );

        $result = $result && $this->runValidationStage(
            $style,
            'Validating XSD types...',
            static function () use ($style, $document, $xpath): ?IssueCollection {
                $schemas = $xpath->query('//schema:schema');
                if ($schemas->count() !== 1) {
                    $style->warning('Skipped : XSD types can only be validated if there is one schema element.');
                    return null;
                }

                return $document->validate(new Validator\SchemaSyntaxValidator());
            }
        );

        return $result ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param callable(): ?IssueCollection $validator
     */
    private function runValidationStage(SymfonyStyle $style, string $label, callable $validator): bool
    {
        $style->info($label.'...');
        $issues = $validator();

        // Skipped ...
        if ($issues === null) {
            return true;
        }

        if ($issues->count()) {
            $style->block($issues->toString());
            $style->error('Validation failed!');
            return false;
        }

        $style->success('All good!');
        return true;
    }
}
