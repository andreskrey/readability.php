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
    public function getContentScore();

    /**
     * @return Readability
     */
    public function initializeNode();

    /**
     * @return int
     */
    public function getClassWeight();

    /**
     * @param int $score
     *
     * @return int
     */
    public function setContentScore($score);

    /**
     * @return DOMElement|null
     */
    public function getAllLinks();
}
