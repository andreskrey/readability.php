<?php

namespace andreskrey\Readability\Test;

use andreskrey\Readability\Configuration;

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
        // just part of params, it's enough
        $this->assertEquals($options['originalURL'], $config->getOriginalURL());
        $this->assertEquals($options['fixRelativeURLs'], $config->getFixRelativeURLs());
        $this->assertEquals($options['articleByLine'], $config->getArticleByLine());
        $this->assertEquals($options['maxTopCandidates'], $config->getMaxTopCandidates());
        $this->assertEquals($options['stripUnlikelyCandidates'], $config->getStripUnlikelyCandidates());
        $this->assertEquals($options['substituteEntities'], $config->getSubstituteEntities());
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return [
            [
                [
                    'originalURL' => 'my.original.url',
                    'fixRelativeURLs' => true,
                    'articleByLine' => true,
                    'maxTopCandidates' => 3,
                    'stripUnlikelyCandidates' => false,
                    'substituteEntities' => true,
                    'invalidParameter' => 'invalidParameterValue',
                ],
            ],
        ];
    }
}
