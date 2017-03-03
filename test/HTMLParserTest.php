<?php

namespace andreskrey\Readability\Test;

use andreskrey\Readability\HTMLParser;

class HTMLParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSamplePages
     */
    public function testHTMLParserParsesHTML($html, $expectedResult, $expectedMetadata, $config)
    {
        $options = ['originalURL' => 'http://fakehost/test/test.html',
            'fixRelativeURLs' => true,
            'normalizeSpaces' => false,
            'substituteEntities' => true
        ];

        if ($config) {
            $options = $config;
        }

        $readability = new HTMLParser($options);
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
            $config = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'config.json');
            if ($config) {
                $config = json_decode($config);
            }

            $pages[$testPage] = [$source, $expectedHTML, $expectedMetadata, $config];
        }

        return $pages;
    }
}
