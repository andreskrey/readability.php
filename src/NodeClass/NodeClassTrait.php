<?php

namespace andreskrey\Readability\NodeClass;

trait NodeClassTrait
{

    /**
     * @var int
     */
    protected $contentScore = 0;

    /**
     * @var array
     */
    private $regexps = [
        'positive' => '/article|body|content|entry|hentry|h-entry|main|page|pagination|post|text|blog|story/i',
        'negative' => '/hidden|^hid$| hid$| hid |^hid |banner|combx|comment|com-|contact|foot|footer|footnote|masthead|media|meta|modal|outbrain|promo|related|scroll|share|shoutbox|sidebar|skyscraper|sponsor|shopping|tags|tool|widget/i',
    ];

    /**
     * Checks for the tag name. Case insensitive.
     *
     * @param string $value Name to compare to the current tag
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
     * @return string
     */
    public function getTagName()
    {
        return $this->node->nodeName;
    }

    /**
     * Checks for the node type.
     *
     * @param string $value Type of node to compare to
     *
     * @return bool
     */
    public function nodeTypeEqualsTo($value)
    {
        return $this->node->nodeType === $value;
    }



    /**
     * Get the ancestors of the current node.
     *
     * @param int|bool $maxLevel Max amount of ancestors to get. False for all of them
     *
     * @return array
     */
    public function getNodeAncestors($maxLevel = 3)
    {
        $ancestors = [];
        $level = 0;

        $node = $this->getParent();

        while ($node) {
            $ancestors[] = $node;
            $level++;
            if ($level === $maxLevel) {
                break;
            }
            $node = $node->getParent();
        }

        return $ancestors;
    }

    /**
     * Overloading the getParent function from League\HTMLToMarkdown\Element due to a bug when there are no more parents
     * on the selected element.
     *
     * @return Readability|null
     */
    public function getParent()
    {
        $node = $this->node->parentNode;

        return ($node) ? new self($node) : null;
    }

    /**
     * Returns all links from the current element.
     *
     * @return array|null
     */
    public function getAllLinks()
    {
        if (($this->isText())) {
            return null;
        } else {
            $links = [];
            foreach ($this->node->getElementsByTagName('a') as $link) {
                $links[] = new self($link);
            }

            return $links;
        }
    }
    /**
     * Calculates the weight of the class/id of the current element.
     *
     * @todo check for flag that lets this function run or not
     *
     * @return int
     */
    public function getClassWeight()
    {
        //        TODO To implement. How to get config from html parser from readability
//        if ($this->getConfig()->getOption('weightClasses')) {
//            return 0;
//        }
//
        $weight = 0;

        // Look for a special classname
        $class = $this->getAttribute('class');
        if (trim($class)) {
            if (preg_match($this->regexps['negative'], $class)) {
                $weight -= 25;
            }

            if (preg_match($this->regexps['positive'], $class)) {
                $weight += 25;
            }
        }

        // Look for a special ID
        $id = $this->getAttribute('id');
        if (trim($id)) {
            if (preg_match($this->regexps['negative'], $id)) {
                $weight -= 25;
            }

            if (preg_match($this->regexps['positive'], $id)) {
                $weight += 25;
            }
        }

        return $weight;
    }

    /**
     * Returns the current score of the Readability object.
     *
     * @return int
     */
    public function getContentScore()
    {
        return $this->contentScore;
    }

    /**
     * Returns the current score of the Readability object.
     *
     * @param int $score
     */
    public function setContentScore($score)
    {
        $this->contentScore = $score;
    }


    /**
     * Returns the full text of the node.
     *
     * @param bool $normalize Normalize white space?
     *
     * @return string
     */
    public function getTextContent($normalize = false)
    {
        $nodeValue = $this->node->nodeValue;
        if ($normalize) {
            $nodeValue = trim(preg_replace('/\s{2,}/', ' ', $nodeValue));
        }

        return $nodeValue;
    }

    /**
     * Removes the current node and returns the next node to be parsed (child, sibling or parent).
     *
     * @param Readability $node
     *
     * @return Readability
     */
    public function removeAndGetNext($node)
    {
        $nextNode = $this->getNextNode($node, true);
        $node->node->parentNode->removeChild($node->node);

        return $nextNode;
    }

    /**
     * Returns the next node. First checks for childs (if the flag allows it), then for siblings, and finally
     * for parents.
     *
     * @param Readability $originalNode
     * @param bool $ignoreSelfAndKids
     *
     * @return Readability
     */
    public function getNextNode($originalNode, $ignoreSelfAndKids = false)
    {
        /*
         * Traverse the DOM from node to node, starting at the node passed in.
         * Pass true for the second parameter to indicate this node itself
         * (and its kids) are going away, and we want the next node over.
         *
         * Calling this in a loop will traverse the DOM depth-first.
         */

        // First check for kids if those aren't being ignored
        if (!$ignoreSelfAndKids && $originalNode->node->firstChild) {
            return new self($originalNode->node->firstChild);
        }

        // Then for siblings...
        if ($originalNode->node->nextSibling) {
            return new self($originalNode->node->nextSibling);
        }

        // And finally, move up the parent chain *and* find a sibling
        // (because this is depth-first traversal, we will have already
        // seen the parent nodes themselves).
        do {
            $originalNode = $originalNode->getParent();
        } while ($originalNode && !$originalNode->node->nextSibling);

        return ($originalNode) ? new self($originalNode->node->nextSibling) : $originalNode;
    }

    /**
     * Compares nodes. Checks for tag name and text content.
     *
     * It's a replacement of the original JS code, which looked like this:
     *
     * $node1 == $node2
     *
     * I'm not sure this works the same in PHP, so I created a mock function to check the actual content of the node.
     * Should serve the same porpuse as the original comparison.
     *
     * @param Readability $node1
     * @param Readability $node2
     *
     * @return bool
     */
    public function compareNodes($node1, $node2)
    {
        if ($node1->getTagName() !== $node2->getTagName()) {
            return false;
        }

        if ($node1->getTextContent(true) !== $node2->getTextContent(true)) {
            return false;
        }

        return true;
    }

    /**
     * Creates a new node based on the text content of the original node.
     *
     * @param Readability $originalNode
     * @param string $tagName
     *
     * @return Readability
     */
    public function createNode(self $originalNode, $tagName)
    {
        $text = $originalNode->getTextContent();
        $newNode = $originalNode->node->ownerDocument->createElement($tagName, $text);

        return new static($newNode);
    }

    /**
     * Check if a given node has one of its ancestor tag name matching the
     * provided one.
     *
     * @param Readability $node
     * @param string $tagName
     * @param int $maxDepth
     *
     * @return bool
     */
    public function hasAncestorTag(self $node, $tagName, $maxDepth = 3)
    {
        $depth = 0;
        while ($node->getParent()) {
            if ($maxDepth > 0 && $depth > $maxDepth) {
                return false;
            }
            if ($node->getParent()->tagNameEqualsTo($tagName)) {
                return true;
            }
            $node = $node->getParent();
            $depth++;
        }

        return false;
    }

    /**
     * Returns the children of the current node.
     *
     * @param bool $filterEmptyDOMText Filter empty DOMText nodes?
     *
     * @return array
     */
    public function getChildren($filterEmptyDOMText = false)
    {
        $ret = [];
        /** @var \DOMNode $node */
        foreach ($this->node->childNodes as $node) {
            if ($filterEmptyDOMText && $node->nodeName === '#text' && !trim($node->nodeValue)) {
                continue;
            }

            $ret[] = new static($node);
        }

        return $ret;
    }

    /**
     * Determines if a node has no content or it is just a bunch of dividing lines and/or whitespace.
     *
     * @return bool
     */
    public function isElementWithoutContent()
    {
        return $this->node instanceof \DOMElement &&
            // /\x{00A0}|\s+/u TODO to be replaced with regexps array
            mb_strlen(preg_replace('/\x{00A0}|\s+/u', '', $this->node->textContent)) === 0 &&
            ($this->node->childNodes->length === 0 ||
                $this->node->childNodes->length === $this->node->getElementsByTagName('br')->length + $this->node->getElementsByTagName('hr')->length
                /*
                 * Special DOMDocument case: We also need to count how many DOMText we have inside the node.
                 * If there's an empty tag with an space inside and a BR (for example "<p> <br/></p>) counting only BRs and
                 * HRs will will say that the example has 2 nodes, instead of one. This happens because in DOMDocument,
                 * DOMTexts are also nodes (which doesn't happen in JS). So we need to also count how many DOMText we
                 * are dealing with (And at this point we know they are empty or are just whitespace, because of the
                 * mb_strlen in this chain of checks).
                 */
                + count(array_filter(iterator_to_array($this->node->childNodes), function ($child) {
                    return $child instanceof \DOMText;
                }))

            );
    }

}
