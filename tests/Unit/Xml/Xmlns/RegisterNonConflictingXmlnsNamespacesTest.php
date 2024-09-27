<?php declare(strict_types=1);

namespace SoapTest\Wsdl\Unit\Xml\Xmlns;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Xml\Xmlns\RegisterNonConflictingXmlnsNamespaces;
use VeeWee\Xml\Dom\Document;

final class RegisterNonConflictingXmlnsNamespacesTest extends TestCase
{
    /**
     *
     * @dataProvider provideCases
     */
    public function test_it_registers_non_conflicting_namespaces(
        string $existingSchemaXml,
        string $importedSchemaXml,
        string $expectedExistingSchemaXml,
        string $expectedImportedSchemaXml
    ): void {
        $existingSchema = Document::fromXmlString($existingSchemaXml);
        $importedSchema = Document::fromXmlString($importedSchemaXml);

        (new RegisterNonConflictingXmlnsNamespaces())(
            $existingSchema->locateDocumentElement(),
            $importedSchema->locateDocumentElement()
        );

        static::assertSame($expectedExistingSchemaXml, $existingSchema->stringifyDocumentElement());
        static::assertSame($expectedImportedSchemaXml, $importedSchema->stringifyDocumentElement());
    }

    public static function provideCases(): iterable
    {
        yield 'no-conflict' => [
            'existingSchemaXml' => '<schema xmlns:ns1="urn:1"/>',
            'importedSchemaXml' => '<schema xmlns:ns2="urn:2"><element type="ns2:Type"/></schema>',
            'expectedExistingSchemaXml' => '<schema xmlns:ns1="urn:1" xmlns:ns2="urn:2"/>',
            'expectedImportedSchemaXml' => '<schema xmlns:ns2="urn:2"><element type="ns2:Type"/></schema>',
        ];
        yield 'conflict' => [
            'existingSchemaXml' => '<schema xmlns:ns1="urn:1"/>',
            'importedSchemaXml' => '<schema xmlns:ns1="urn:2"><element type="ns1:Type"/></schema>',
            'expectedExistingSchemaXml' => '<schema xmlns:ns1="urn:1" xmlns:nsbcf50aa8="urn:2"/>',
            'expectedImportedSchemaXml' => '<schema xmlns:ns1="urn:2"><element type="nsbcf50aa8:Type"/></schema>',
        ];
        yield 'conflict-with-existing-alternative' => [
            'existingSchemaXml' => '<schema xmlns:ns1="urn:1" xmlns:ns2="urn:2"/>',
            'importedSchemaXml' => '<schema xmlns:ns1="urn:2"><element type="ns1:Type" /></schema>',
            'expectedExistingSchemaXml' => '<schema xmlns:ns1="urn:1" xmlns:ns2="urn:2"/>',
            'expectedImportedSchemaXml' => '<schema xmlns:ns1="urn:2"><element type="ns2:Type"/></schema>',
        ];
        yield 'multiple-conflicts' => [
            'existingSchemaXml' => '<schema xmlns:ns1="urn:1" xmlns:ns2="urn:2" xmlns:ns3="urn:3"/>',
            'importedSchemaXml' => <<<EOXML
            <schema xmlns:ns1="urn:2" xmlns:ns2="urn:1" xmlns:ns3="urn:4">
                <element name="element1" type="ns1:Type"/>
                <element name="element2" type="ns2:Type"/>
                <element name="element3" type="ns3:Type"/>
            </schema>
            EOXML,
            'expectedExistingSchemaXml' => '<schema xmlns:ns1="urn:1" xmlns:ns2="urn:2" xmlns:ns3="urn:3" xmlns:ns56ce840f="urn:4"/>',
            'expectedImportedSchemaXml' => <<<EOXML
            <schema xmlns:ns1="urn:2" xmlns:ns2="urn:1" xmlns:ns3="urn:4">
                <element name="element1" type="ns2:Type"/>
                <element name="element2" type="ns1:Type"/>
                <element name="element3" type="ns56ce840f:Type"/>
            </schema>
            EOXML,
        ];
    }
}
