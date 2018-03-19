<?php

namespace andreskrey\Readability\Test;

use andreskrey\Readability\Configuration;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

/**
 * Class ConfigurationTest.
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getParams
     *
     * @param array $params
     */
    public function testConfigurationConstructorSetsParameters(array $params)
    {
        $config = new Configuration($params);
        $this->doEqualsAsserts($config, $params);
    }

    /**
     * @dataProvider getParams
     *
     * @param array $params
     */
    public function testInvalidParameterIsNotInConfig(array $params)
    {
        $config = new Configuration($params);
        $this->assertArrayNotHasKey('invalidParameter', $config->toArray(), 'Invalid param key is not present in config');
    }

    /**
     * @param Configuration $config
     * @param array $options
     */
    private function doEqualsAsserts(Configuration $config, array $options)
    {
        $this->assertEquals($options['maxTopCandidates'], $config->getMaxTopCandidates());
        $this->assertEquals($options['wordThreshold'], $config->getWordThreshold());
        $this->assertEquals($options['articleByLine'], $config->getArticleByLine());
        $this->assertEquals($options['stripUnlikelyCandidates'], $config->getStripUnlikelyCandidates());
        $this->assertEquals($options['cleanConditionally'], $config->getCleanConditionally());
        $this->assertEquals($options['weightClasses'], $config->getWeightClasses());
        $this->assertEquals($options['fixRelativeURLs'], $config->getFixRelativeURLs());
        $this->assertEquals($options['substituteEntities'], $config->getSubstituteEntities());
        $this->assertEquals($options['normalizeEntities'], $config->getNormalizeEntities());
        $this->assertEquals($options['originalURL'], $config->getOriginalURL());
        $this->assertEquals($options['summonCthulhu'], $config->getOriginalURL());
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return [[
            'All current parameters' => [
                'maxTopCandidates' => 3,
                'wordThreshold' => 500,
                'articleByLine' => true,
                'stripUnlikelyCandidates' => false,
                'cleanConditionally' => false,
                'weightClasses' => false,
                'fixRelativeURLs' => true,
                'substituteEntities' => true,
                'normalizeEntities' => true,
                'originalURL' => 'my.original.url',
                'summonCthulhu' => 'my.original.url',
                'invalidParameter' => 'invalidParameterValue'
            ]
        ]];
    }

    /**
     * Test if a logger interface can be injected and retrieved from the Configuration object.
     */
    public function testLoggerCanBeInjected()
    {
        $configuration = new Configuration();
        $log = new Logger('Readability');
        $log->pushHandler(new NullHandler());

        $configuration->setLogger($log);

        $this->assertSame($log, $configuration->getLogger());
    }
}
