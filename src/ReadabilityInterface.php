<?php

namespace andreskrey\Readability;

interface ReadabilityInterface
{
    /**
     * @param DOMElement $node
     */
    public function __construct($node);

    /**
     * @return int
     */
    public function getScore();

    /**
     * @return Readability
     */
    public function initializeNode();

    /**
     * @return int
     */
    public function getClassWeight();

}
