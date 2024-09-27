<?php declare(strict_types=1);

namespace SoapTest\Wsdl\Unit\Xml\Visitor;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Xml\Visitor\ReprefixTypeQname;
use VeeWee\Xml\Dom\Document;

final class ReprefixTypeQnameTest extends TestCase
{
    /**
     *
     * @dataProvider provideCases
     */
    public function test_it_can_reprefix_qname_types(string $input, string $expected): void
    {
        $doc = Document::fromXmlString($input);
        $doc->traverse(new ReprefixTypeQname([
            'tns' => 'new',
            'new' => 'previous', // To make sure prefix replacements don't get chained
        ]));

        static::assertXmlStringEqualsXmlString($expected, $doc->toXmlString());
    }

    public static function provideCases(): iterable
    {
        yield 'no-attr' => [
            '<element />',
            '<element />',
        ];
        yield 'other-attribute' => [
            '<element other="xsd:Type" />',
            '<element other="xsd:Type" />',
        ];
        yield 'no-qualified' => [
            '<element type="Type" />',
            '<element type="Type" />',
        ];
        yield 'simple' => [
            '<node type="tns:Type" />',
            '<node type="new:Type" />',
        ];
        yield 'element' => [
            '<element type="tns:Type" />',
            '<element type="new:Type" />',
        ];
        yield 'attribute' => [
            '<attribute type="tns:Type" />',
            '<attribute type="new:Type" />',
        ];
        yield 'nested-schema' => [
            <<<EOXML
            <complexType name="Store">
                <sequence>
                    <element minOccurs="1" maxOccurs="1" name="phone" type="tns:string"/>
                </sequence>
            </complexType>
            EOXML,
            <<<EOXML
            <complexType name="Store">
                <sequence>
                    <element minOccurs="1" maxOccurs="1" name="phone" type="new:string"/>
                </sequence>
            </complexType>
            EOXML,
        ];
        yield 'dont-chain-reprefixes' => [
            '<schema><element type="tns:Type" /><element type="new:Type" /></schema>',
            '<schema><element type="new:Type" /><element type="previous:Type" /></schema>',
        ];
    }
}
