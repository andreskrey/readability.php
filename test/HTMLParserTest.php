<?php

namespace andreskrey\Readability\Test;

use andreskrey\Readability\HTMLParser;

class HTMLParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSamplePages
     */
    public function testHTMLParserParsesHTML($html, $expectedResult, $expectedMetadata)
    {
        $readability = new HTMLParser();
        $result = $readability->parse($html);

        $this->assertEquals($expectedResult, $result['html']);
    }

    public function getSamplePages()
    {
        $path = pathinfo(__FILE__, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'test-pages';
        $testPages = scandir($path);
        if (in_array('.DS_Store', $testPages)) {
            unset($testPages[array_search('.DS_Store', $testPages)]);
        }

        $pages = [];

        foreach (array_slice($testPages, 2) as $testPage) {
            $source = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'source.html');
            $expectedHTML = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected.html');
            $expectedMetadata = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected-metadata.json');

            $pages[] = [$source, $expectedHTML, $expectedMetadata];
        }

        return $pages;
    }
}