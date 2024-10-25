<?php

declare(strict_types=1);

namespace Soap\Wsdl\Test\Unit\Uri;

use PHPUnit\Framework\TestCase;
use Soap\Wsdl\Uri\IncludePathBuilder;

final class IncludePathBuilderTest extends TestCase
{
    /**
     *
     * @dataProvider provideBuildPaths
     */
    public function test_it_can_build_include_paths(string $relativePath, string $fromFile, string $expected): void
    {
        static::assertSame($expected, IncludePathBuilder::build($relativePath, $fromFile));
    }

    public static function provideBuildPaths()
    {
        yield 'same-dir-file' => [
            'relativePath' => 'otherfile.xml',
            'fromFile' => 'somedir/somefile.xml',
            'expected' => 'somedir/otherfile.xml',
        ];
        yield 'child-dir-file' => [
            'relativePath' => '../otherfile.xml',
            'fromFile' => 'somedir/child/somefile.xml',
            'expected' => 'somedir/otherfile.xml',
        ];
        yield 'http-file' => [
            'relativePath' => 'otherfile.xml',
            'fromFile' => 'http://localhost/somedir/somefile.xml',
            'expected' => 'http://localhost/somedir/otherfile.xml',
        ];
        yield 'http-dir-file' => [
            'relativePath' => '../otherfile.xml',
            'fromFile' => 'http://localhost/somedir/child/somefile.xml',
            'expected' => 'http://localhost/somedir/otherfile.xml',
        ];
    }
}
