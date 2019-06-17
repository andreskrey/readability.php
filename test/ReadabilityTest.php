<?php

namespace andreskrey\Readability\Test;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;

/**
 * Class ReadabilityTest.
 */
class ReadabilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that Readability parses the HTML correctly and matches the expected result.
     *
     * @dataProvider getSamplePages
     *
     * @param TestPage $testPage
     *
     * @throws ParseException
     */
    public function testReadabilityParsesHTML(TestPage $testPage)
    {
        $options = ['OriginalURL' => 'http://fakehost/test/test.html',
            'FixRelativeURLs' => true,
            'SubstituteEntities' => true,
            'ArticleByLine' => true
        ];

        $configuration = new Configuration(array_merge($testPage->getConfiguration(), $options));

        $readability = new Readability($configuration);
        $readability->parse($testPage->getSourceHTML());

        $this->assertSame($testPage->getExpectedHTML(), $readability->getContent(), 'Parsed text does not match the expected one.');
    }

    /**
     * Test that Readability parses the HTML correctly and matches the expected result.
     *
     * @dataProvider getSamplePages
     *
     * @param TestPage $testPage
     *
     * @throws ParseException
     */
    public function testReadabilityParsesMetadata(TestPage $testPage)
    {
        $options = ['OriginalURL' => 'http://fakehost/test/test.html',
            'FixRelativeURLs' => true,
            'SubstituteEntities' => true,
            'ArticleByLine' => true
        ];

        $configuration = new Configuration(array_merge($testPage->getConfiguration(), $options));

        $readability = new Readability($configuration);
        $readability->parse($testPage->getSourceHTML());

        $this->assertSame($testPage->getExpectedMetadata()->Author, $readability->getAuthor(), 'Parsed Author does not match expected value.');
        $this->assertSame($testPage->getExpectedMetadata()->Direction, $readability->getDirection(), 'Parsed Direction does not match expected value.');
        $this->assertSame($testPage->getExpectedMetadata()->Excerpt, $readability->getExcerpt(), 'Parsed Excerpt does not match expected value.');
        $this->assertSame($testPage->getExpectedMetadata()->Image, $readability->getImage(), 'Parsed Image does not match expected value.');
        $this->assertSame($testPage->getExpectedMetadata()->Title, $readability->getTitle(), 'Parsed Title does not match expected value.');
    }

    /**
     * Test that Readability returns all the expected images from the test page.
     *
     * @param TestPage $testPage
     * @dataProvider getSamplePages
     *
     * @throws ParseException
     */
    public function testHTMLParserParsesImages(TestPage $testPage)
    {
        $options = ['OriginalURL' => 'http://fakehost/test/test.html',
            'fixRelativeURLs' => true,
            'substituteEntities' => true,
        ];

        $configuration = new Configuration(array_merge($testPage->getConfiguration(), $options));

        $readability = new Readability($configuration);
        $readability->parse($testPage->getSourceHTML());

        $this->assertSame($testPage->getExpectedImages(), $readability->getImages());
    }

    /**
     * Main data provider.
     *
     * @return \Generator
     */
    public function getSamplePages()
    {
        $path = pathinfo(__FILE__, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'test-pages';
        $testPages = scandir($path);

        foreach (array_slice($testPages, 2) as $testPage) {
            $testCasePath = $path . DIRECTORY_SEPARATOR . $testPage . DIRECTORY_SEPARATOR;

            $source = file_get_contents($testCasePath . 'source.html');
            $expectedHTML = file_get_contents($testCasePath . 'expected.html');
            $expectedImages = json_decode(file_get_contents($testCasePath . 'expected-images.json'), true);
            $expectedMetadata = json_decode(file_get_contents($testCasePath . 'expected-metadata.json'));
            $configuration = file_exists($testCasePath . 'config.json') ? json_decode(file_get_contents($testCasePath . 'config.json'), true) : [];

            yield $testPage => [new TestPage($configuration, $source, $expectedHTML, $expectedImages, $expectedMetadata)];
        }
    }

    /**
     * Test that Readability throws an exception with malformed HTML.
     *
     * @throws ParseException
     */
    public function testReadabilityThrowsExceptionWithMalformedHTML()
    {
        $parser = new Readability(new Configuration());
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid or incomplete HTML.');
        $parser->parse('<html>');
    }

    /**
     * Test that Readability throws an exception with incomplete or short HTML.
     *
     * @throws ParseException
     */
    public function testReadabilityThrowsExceptionWithUnparseableHTML()
    {
        $parser = new Readability(new Configuration());
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Could not parse text.');
        $parser->parse('<html><body><p></p></body></html>');
    }

    /**
     * Test that the Readability object has no content as soon as it is instantiated.
     */
    public function testReadabilityCallGetContentWithNoContent()
    {
        $parser = new Readability(new Configuration());
        $this->assertNull($parser->getContent());
    }
}
