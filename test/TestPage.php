<?php

namespace andreskrey\Readability\Test;

class TestPage
{
    private $configuration;
    private $sourceHTML;
    private $expectedHTML;
    private $expectedImages;
    private $expectedMetadata;

    public function __construct($configuration, $sourceHTML, $expectedHTML, $expectedImages, $expectedMetadata)
    {
        $this->configuration = $configuration;
        $this->sourceHTML = $sourceHTML;
        $this->expectedHTML = $expectedHTML;
        $this->expectedImages = $expectedImages;
        $this->expectedMetadata = $expectedMetadata;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return null
     */
    public function getSourceHTML()
    {
        return $this->sourceHTML;
    }

    /**
     * @return null
     */
    public function getExpectedHTML()
    {
        return $this->expectedHTML;
    }

    /**
     * @return mixed
     */
    public function getExpectedImages()
    {
        return $this->expectedImages;
    }

    /**
     * @return \stdClass
     */
    public function getExpectedMetadata()
    {
        return $this->expectedMetadata;
    }
}
