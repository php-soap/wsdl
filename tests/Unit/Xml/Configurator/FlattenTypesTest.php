<?php
declare(strict_types=1);

namespace Soap\Wsdl\Test\Unit\Xml\Configurator;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Xml\Configurator\FlattenTypes;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\comparable;

final class FlattenTypesTest extends TestCase
{
    /**
     *
     * @dataProvider provideTestCases
     */
    public function test_it_can_flatten_types(string $wsdlUri, Document $expected): void
    {
        $wsdl = Document::fromXmlFile($wsdlUri, new FlattenTypes(), comparable());

        static::assertSame($expected->toXmlString(), $wsdl->toXmlString());
    }

    public function provideTestCases()
    {
        yield 'single-type' => [
            'wsdl' => FIXTURE_DIR . '/flattening/functional/empty-schema.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR . '/flattening/functional/empty-schema.wsdl', comparable()),
        ];
        yield 'no-type' => [
            'wsdl' => FIXTURE_DIR . '/flattening/functional/empty.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR . '/flattening/result/empty-typed.wsdl', comparable()),
        ];
        yield 'multiple-types' => [
            'wsdl' => FIXTURE_DIR . '/flattening/result/import-with-own-tags-result.wsdl',
            'expected' => Document::fromXmlFile(FIXTURE_DIR . '/flattening/result/import-with-own-tags-single-type-result.wsdl', comparable()),
        ];
    }
}
