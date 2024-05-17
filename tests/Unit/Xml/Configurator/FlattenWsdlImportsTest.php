<?php
declare(strict_types=1);

namespace Soap\Wsdl\Test\Unit\Xml\Configurator;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Loader\Context\FlatteningContext;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\Wsdl\Xml\Configurator\FlattenWsdlImports;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\comparable;

final class FlattenWsdlImportsTest extends TestCase
{
    /**
     *
     * @dataProvider provideTestCases
     */
    public function test_it_can_flatten_wsdl_imports(string $wsdlUri, Document $expected): void
    {
        $wsdl = Document::fromXmlFile($wsdlUri);
        $configurator = new FlattenWsdlImports(
            $wsdlUri,
            FlatteningContext::forWsdl($wsdlUri, $wsdl, new StreamWrapperLoader())
        );
        $flattened = Document::fromUnsafeDocument($wsdl->toUnsafeDocument(), $configurator, comparable());

        static::assertSame($expected->toXmlString(), $flattened->toXmlString());
    }

    public function provideTestCases()
    {
        yield 'only-import' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/functional/float.wsdl', comparable()),
        ];
        yield 'import-once' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/functional/float.wsdl', comparable()),
        ];
        yield 'with-own-tags' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import-with-own-tags.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/import-with-own-tags-result.wsdl', comparable()),
        ];
        yield 'multi-imports' => [
            'wsdl' => FIXTURE_DIR.'/flattening/multi-import.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/multi-import-result.wsdl', comparable()),
        ];
        yield 'xsd-imports' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/import-xsd-result.wsdl', comparable()),
        ];
        yield 'multi-xsd-imports' => [
            'wsdl' => FIXTURE_DIR.'/flattening/import-multi-xsd.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR.'/flattening/result/import-multi-xsd-result.wsdl', comparable()),
        ];
    }
}
