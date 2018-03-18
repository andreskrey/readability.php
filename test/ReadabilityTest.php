<?php

namespace andreskrey\Readability\Test;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;

class ReadabilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSamplePages
     */
    public function testReadabilityParsesHTML($html, $expectedResult, $expectedMetadata, $config, $expectedImages)
    {
        $options = ['OriginalURL' => 'http://fakehost/test/test.html',
            'FixRelativeURLs' => true,
            'SubstituteEntities' => true,
            'ArticleByLine' => true
        ];

        if ($config === null || $expectedMetadata === null) {
            $this->markTestSkipped('Wrong test configuration');
        }

        if ($config) {
            $options = array_merge($config, $options);
        }

        $configuration = new Configuration($options);

        $readability = new Readability($configuration);
        $readability->parse($html);

        $this->assertSame($expectedResult, $readability->getContent());

        foreach ($expectedMetadata as $key => $metadata) {
            $function = 'get' . $key;
            $this->assertEquals($metadata, $readability->$function(), sprintf('Failed asserting %s metadata', $key));
        }
    }

    /**
     * @dataProvider getSamplePages
     */
    public function testHTMLParserParsesImages($html, $expectedResult, $expectedMetadata, $config, $expectedImages)
    {
        $options = ['OriginalURL' => 'http://fakehost/test/test.html',
            'fixRelativeURLs' => true,
            'substituteEntities' => true,
        ];

        if ($config) {
            $options = array_merge($options, $config);
        }

        $configuration = new Configuration($options);

        $readability = new Readability($configuration);
        $readability->parse($html);

        $this->assertSame($expectedImages, json_encode($readability->getImages()));
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
            $expectedImages = file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected-images.json');

            $expectedMetadata = json_decode(file_get_contents($path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR . 'expected-metadata.json'));

            $config = false;
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

    public function testReadabilityThrowsExceptionWithMalformedHTML()
    {
        $parser = new Readability(new Configuration());
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid or incomplete HTML.');
        $parser->parse('<html>');
    }

    public function testReadabilityThrowsExceptionWithUnparseableHTML()
    {
        $parser = new Readability(new Configuration());
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Could not parse text.');
        $parser->parse('<html><body><p></p></body></html>');
    }

    public function testReadabilityCallGetContentWithNoContent()
    {
        $parser = new Readability(new Configuration());
        $this->assertNull($parser->getContent());
    }
}
