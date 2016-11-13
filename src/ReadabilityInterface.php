<?php

namespace andreskrey\Readability;

use League\HTMLToMarkdown\ElementInterface;

interface ReadabilityInterface extends ElementInterface
{
    /**
     * @param string $value
     *
     * @return bool
     */
    public function tagNameEqualsTo($value);

    /**
     * @return int
     */
    public function getNodeAncestors();

    /**
     * @return Readability|null
     */
    public function getAllLinks();

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
}
