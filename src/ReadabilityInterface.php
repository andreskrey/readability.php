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

    /**
     * @param bool $normalize Normalize white space?
     * @return string
     */
    public function getTextContent($normalize);

    /**
     * @param string $value
     */
    public function setNodeTag($value);

    /**
     * @return \DOMNode
     */
    public function getDOMNode();

    /**
     * @param Readability $node
     *
     * @return Readability
     */
    public function removeAndGetNext($node);

    /**
     * @param Readability $originalNode
     * @param bool $ignoreSelfAndKids
     *
     * @return Readability
     */

    public function getNextNode($originalNode, $ignoreSelfAndKids = false);

    /**
     * @param Readability $node1
     * @param Readability $node2
     *
     * @return bool
     */
    public function compareNodes($node1, $node2);

    /**
     * @param Readability $newNode
     */
    public function replaceChild(Readability $newNode);
}
