<?php
declare(strict_types=1);

namespace Soap\Wsdl\Test\Unit\Loader;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psl\Ref;
use Soap\Wsdl\Exception\UnloadableWsdlException;
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\Wsdl\Loader\WsdlLoader;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\comparable;

final class FlatteningLoaderTest extends TestCase
{
    private FlatteningLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new FlatteningLoader(new StreamWrapperLoader());
    }

    #[DataProvider('provideTestCases')]
    public function test_it_can_load_flattened_imports(string $wsdl, Document $expected): void
    {
        $result = ($this->loader)($wsdl);
        $flattened = Document::fromXmlString($result, comparable());

        static::assertSame($expected->toXmlString(), $flattened->toXmlString());
    }

    public function test_it_resolves_imports_when_given_a_relative_path(): void
    {
        $cwd = getcwd();
        chdir(FIXTURE_DIR.'/flattening');
        try {
            $result = ($this->loader)('single-xsd.wsdl');
        } finally {
            chdir($cwd);
        }

        $flattened = Document::fromXmlString($result, comparable());
        $expected = Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/single-xsd-result.wsdl', comparable());

        static::assertSame($expected->toXmlString(), $flattened->toXmlString());
    }

    public function test_it_leaves_uri_scheme_locations_untouched(): void
    {
        $result = ($this->loader)('file://'.FIXTURE_DIR.'/flattening/single-xsd.wsdl');
        $flattened = Document::fromXmlString($result, comparable());
        $expected = Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/single-xsd-result.wsdl', comparable());

        static::assertSame($expected->toXmlString(), $flattened->toXmlString());
    }

    public function test_it_throws_when_given_a_non_existing_relative_path(): void
    {
        $this->expectException(UnloadableWsdlException::class);
        ($this->loader)('does-not-exist.wsdl');
    }

    #[DataProvider('provideSchemeLocations')]
    public function test_it_forwards_uri_scheme_locations_unchanged(string $location): void
    {
        /** @var Ref<?string> $captured */
        $captured = new Ref(null);
        $capturingLoader = new class($captured) implements WsdlLoader {
            public function __construct(private Ref $captured)
            {
            }
            public function __invoke(string $location): string
            {
                $this->captured->value = $location;
                return '<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"/>';
            }
        };

        (new FlatteningLoader($capturingLoader))($location);

        static::assertSame($location, $captured->value);
    }

    public static function provideSchemeLocations(): iterable
    {
        yield 'http' => ['http://example.com/service.wsdl'];
        yield 'https' => ['https://example.com/service.wsdl'];
        yield 'file' => ['file:///tmp/service.wsdl'];
        yield 'ftp' => ['ftp://example.com/service.wsdl'];
        yield 'scheme-with-plus' => ['svn+ssh://example.com/service.wsdl'];
        yield 'scheme-with-dot' => ['x.y://example.com/service.wsdl'];
        yield 'scheme-with-dash' => ['x-y://example.com/service.wsdl'];
        yield 'mixed-case-scheme' => ['HTTP://example.com/service.wsdl'];
    }

    public static function provideTestCases()
    {
        //
        // Basic test cases
        //
        yield 'no-imports' => [
            'wsdl' => FIXTURE_DIR.'/flattening/functional/float.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/functional/float.wsdl', comparable()),
        ];
        yield 'circular-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/circular-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/circular-xsd-result.wsdl', comparable()),
        ];
        yield 'importing-circular-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/importing-circular-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/importing-circular-xsd-result.wsdl', comparable()),
        ];

        //
        // XSDs Imports
        //
        yield 'single-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/single-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/single-xsd-result.wsdl', comparable()),
        ];
        yield 'import-once-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/once-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/once-xsd-result.wsdl', comparable()),
        ];
        yield 'import-multi-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/multi-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/multi-xsd-result.wsdl', comparable()),
        ];

        //
        // XSD Includes
        //
        yield 'include-single' => [
            'wsdl' => FIXTURE_DIR.'/flattening/include.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/include.wsdl', comparable()),
        ];
        yield 'include-multi' => [
            'wsdl' => FIXTURE_DIR.'/flattening/multi-include.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/multi-include.wsdl', comparable()),
        ];
        yield 'include-nested' => [
            'wsdl' => FIXTURE_DIR.'/flattening/include-nested.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/include.wsdl', comparable()),
        ];
        yield 'imported-include-nested' => [
            'wsdl' => FIXTURE_DIR.'/flattening/include-nested.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/include.wsdl', comparable()),
        ];
        yield 'include-additional-namespaces' => [
            'wsdl' => FIXTURE_DIR.'/flattening/include-namespaces.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/include-namespaces.wsdl', comparable()),
        ];

        //
        // WSDLs
        //
        yield 'only-wsdl-import' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/functional/float.wsdl', comparable()),
        ];
        yield 'import-wsdl-once' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/functional/float.wsdl', comparable()),
        ];
    }
}
