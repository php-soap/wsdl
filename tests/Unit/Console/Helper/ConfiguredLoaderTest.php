<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Unit\Console\Helper;

use PHPUnit\Framework\TestCase;
use Psl\File\WriteMode;
use Soap\Wsdl\Console\Helper\ConfiguredLoader;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use function Psl\File\write;
use function Psl\Filesystem\delete_file;

final class ConfiguredLoaderTest extends TestCase
{
    public function test_it_can_load_without_file(): void
    {
        $loader = ConfiguredLoader::createFromConfig(null);
        static::assertInstanceOf(StreamWrapperLoader::class, $loader);
    }

    public function test_it_can_configure_loader(): void
    {
        $loader = ConfiguredLoader::createFromConfig(
            null,
            static function ($internal) {
                self::assertInstanceOf(StreamWrapperLoader::class, $internal);

                return new CallbackLoader(static fn () => '');
            }
        );
        static::assertInstanceOf(CallbackLoader::class, $loader);
    }

    
    public function test_it_can_load_from_file(): void
    {
        $this->withLoaderFile(
            static function (string $file) {
                $loader = ConfiguredLoader::createFromConfig($file);
                self::assertSame('loaded', $loader('x'));
            }
        );
    }

    
    public function test_it_can_configure_loaded_from_file(): void
    {
        $this->withLoaderFile(
            static function (string $file) {
                $loader = ConfiguredLoader::createFromConfig($file, static function ($internal) {
                    self::assertInstanceOf(CallbackLoader::class, $internal);

                    return new CallbackLoader(static fn () => 'overwritten');
                });
                self::assertSame('overwritten', $loader('x'));
            },
        );
    }

    private function withLoaderFile(callable $execute): void
    {
        $file = tempnam(sys_get_temp_dir(), 'wsdlloader');
        write(
            $file,
            <<<EOPHP
        <?php
        return new \Soap\Wsdl\Loader\CallbackLoader(static fn () => 'loaded');    
        EOPHP,
            WriteMode::TRUNCATE
        );

        try {
            $execute($file);
        } finally {
            delete_file($file);
        }
    }
}
