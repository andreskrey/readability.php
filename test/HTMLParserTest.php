<?php

namespace andreskrey\Readability\Test;


use andreskrey\Readability\HTMLParser;

class HTMLParserTest extends \PHPUnit_Framework_TestCase
{
    private function HTMLParserParsesHTML($html, $expectedResult, $expectedMetadata)
    {
        $readability = new HTMLParser();
        $result = $readability->parse($html);

        $this->assertEquals($expectedResult, $result['html']);
    }

    public function testSamplePages()
    {
        $path = pathinfo(__FILE__, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'test-pages';
        $testPages = scandir($path);

        foreach(array_slice($testPages, 2) as $testPage){
            $source = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'source.html');
            $expectedMetadata = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected-metadata.json');
            $expectedHTML = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected.html');

            $this->HTMLParserParsesHTML($source, $expectedHTML, $expectedMetadata);
        }
    }
}