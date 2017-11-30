<?php

namespace andreskrey\Readability\Test;


use andreskrey\Readability\Configuration;
use andreskrey\Readability\Readability;

class ReadabilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSamplePages
     */
    public function testReadabilityParsesHTML($html, $expectedResult, $expectedMetadata, $config, $expectedImages)
    {
        $options = ['originalURL' => 'http://fakehost/test/test.html',
            'fixRelativeURLs' => true,
            'substituteEntities' => true,
        ];

        if ($config) {
            $options = array_merge($options, $config);
        }

        $configuration = new Configuration();

        foreach($options as $key => $value){
            $name = 'set' . $key;
            $configuration->$name($value);
        }

        $readability = new Readability($configuration);
        $readability->parse($html);

        $this->assertEquals($expectedResult, $readability->getContent());
    }

    /**
     * @dataProvider getSamplePages
     */
    public function testHTMLParserParsesImages($html, $expectedResult, $expectedMetadata, $config, $expectedImages)
    {
        $options = ['originalURL' => 'http://fakehost/test.html',
            'fixRelativeURLs' => true,
            'substituteEntities' => true,
        ];

        if ($config) {
            $options = array_merge($options, $config);
        }
        $configuration = new Configuration();

        foreach($options as $key => $value){
            $name = 'set' . $key;
            $configuration->$name($value);
        }

        $readability = new Readability($configuration);
        $readability->parse($html);

        $this->assertEquals($expectedImages, json_encode($readability->getImages()));
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
            $expectedImages = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected-images.json');

            $config = null;
            if (file_exists($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'config.json')) {
                $config = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'config.json');
                if ($config) {
                    $config = json_decode($config, true);
                }
            }

            $pages[$testPage] = [$source, $expectedHTML, $expectedMetadata, $config, $expectedImages];
        }

        return $pages;
    }
}
