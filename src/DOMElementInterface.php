<?php

namespace andreskrey\Readability;

use League\HTMLToMarkdown\ElementInterface;

interface DOMElementInterface extends ElementInterface
{
    /**
     * @param string $value
     *
     * @return bool
     */
    public function tagNameEqualsTo($value);

    /**
     *
     * @return bool
     */
    public function hasSinglePNode();

    /**
     *
     * @return integer
     */
    public function getNodeAncestors();
}
