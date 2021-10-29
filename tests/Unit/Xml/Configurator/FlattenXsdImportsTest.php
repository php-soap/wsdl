<?php
declare(strict_types=1);

namespace Soap\Wsdl\Test\Unit\Xml\Configurator;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\Wsdl\Xml\Configurator\FlattenXsdImports;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\comparable;

final class FlattenXsdImportsTest extends TestCase
{
    /**
     *
     * @dataProvider provideTestCases
     */
    public function test_it_can_flatten_xsd_imports(string $wsdlUri, Document $expected): void
    {
        $wsdl = Document::fromXmlFile($wsdlUri);
        $configurator = new FlattenXsdImports(
            new StreamWrapperLoader(),
            $wsdlUri,
            FlatteningContext::forWsdl($wsdlUri, $wsdl)
        );
        $flattened = Document::fromUnsafeDocument($wsdl->toUnsafeDocument(), $configurator, comparable());

        static::assertSame($expected->toXmlString(), $flattened->toXmlString());
    }

    public function provideTestCases()
    {
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
        yield 'circular-xsd' => [
            'wsdl' => FIXTURE_DIR.'/flattening/circular-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/circular-xsd-intermediate-result.wsdl', comparable()),
        ];
    }
}