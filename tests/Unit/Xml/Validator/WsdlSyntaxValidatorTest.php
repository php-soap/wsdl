<?php
declare(strict_types=1);

namespace SoapTest\Wsdl\Xml\Validator;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Xml\Validator\WsdlSyntaxValidator;
use VeeWee\Xml\Dom\Document;

final class WsdlSyntaxValidatorTest extends TestCase
{
    /**
     *
     * @dataProvider provideTestCases
     */
    public function test_it_can_validate_errors(string $wsdl, array $errorMessages): void
    {
        $validator = new WsdlSyntaxValidator();
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
        yield 'invalid-wsdl' => [
            'wsdl' => FIXTURE_DIR.'/validator/invalid-wsdl.wsdl',
            'errorMessages' => [
                "Element '{http://schemas.xmlsoap.org/wsdl/}definitions': This element is not expected."
            ]
        ];
    }
}
