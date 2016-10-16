<?php

namespace andreskrey\Readability;

use League\HTMLToMarkdown\Element;

class DOMElement extends Element implements DOMElementInterface
{
    /**
     * @var \DOMNode
     */
    protected $node;

    /**
     * @var DOMElementInterface|null
     */
    private $nextCached;

    public function __construct(\DOMNode $node)
    {
        parent::__construct($node);
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function tagNameEqualsTo($value)
    {
        $tagName = $this->getTagName();
        if (strtolower($value) === strtolower($tagName)) {
            return true;
        }

        return false;
    }

    /**
     *
     * @return bool
     */
    public function hasSinglePNode()
    {
        if ($this->hasChildren()) {
            $children = $this->getChildren();

            if (count($children) === 1) {
                if (strtolower($children[0]->getTagName()) === 'p') {
                    return true;
                }
            }
        }

        return false;
    }
}
