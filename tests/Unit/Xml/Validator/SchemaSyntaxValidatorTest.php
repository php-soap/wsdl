<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Xml\Validator;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Xml\Validator\SchemaSyntaxValidator;
use VeeWee\Xml\Dom\Document;

final class SchemaSyntaxValidatorTest extends TestCase
{
    /**
     *
     * @dataProvider provideTestCases
     */
    public function test_it_can_validate_errors(string $wsdl, array $errorMessages): void
    {
        $validator = new SchemaSyntaxValidator();
        $issues = [...Document::fromXmlFile($wsdl)->validate($validator)];

        static::assertCount(count($errorMessages), $issues);
        foreach ($errorMessages as $index => $message) {
            static::assertStringContainsString($message, $issues[$index]->message());
        }
    }

    public static function provideTestCases()
    {
        yield 'no-errors' => [
            'wsdl' => FIXTURE_DIR.'/wsdl.wsdl',
            'errorMessages' => []
        ];
        yield 'invalid-xsd' => [
            'wsdl' => FIXTURE_DIR.'/validator/invalid-schema.wsdl',
            'errorMessages' => [
                "Element '{http://www.w3.org/2001/XMLSchema}string': This element is not expected.",
            ]
        ];
        yield 'invalid-multi-schema' => [
            'wsdl' => FIXTURE_DIR.'/validator/invalid-multi-schema.wsdl',
            'errorMessages' => [
                "Element '{http://www.w3.org/2001/XMLSchema}string': This element is not expected.",
                "Element '{http://www.w3.org/2001/XMLSchema}string': This element is not expected.",
            ]
        ];
    }
}
