<?php
declare(strict_types=1);

namespace Soap\Wsdl\Test\Unit\Loader;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\comparable;

final class FlatteningLoaderTest extends TestCase
{
    private FlatteningLoader $loader;

    protected function setUp(): void
    {
        $this->loader = FlatteningLoader::createForLoader(new StreamWrapperLoader());
    }

    /**
     *
     * @dataProvider provideTestCases
     */
    public function test_it_can_load_flattened_imports(string $wsdlUri, Document $expected): void
    {
        $result = ($this->loader)($wsdlUri);
        $flattened = Document::fromXmlString($result, comparable());

        static::assertSame($expected->toXmlString(), $flattened->toXmlString());
    }

    public function provideTestCases()
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
        // XSDs
        //
        yield 'single-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/single-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/single-xsd-result.wsdl', comparable()),
        ];
        yield 'once-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/once-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/once-xsd-result.wsdl', comparable()),
        ];
        yield 'multi-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/multi-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/multi-xsd-result.wsdl', comparable()),
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
