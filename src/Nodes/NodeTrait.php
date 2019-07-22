<?php

namespace andreskrey\Readability\Nodes;

use andreskrey\Readability\Nodes\DOM\DOMDocument;
use andreskrey\Readability\Nodes\DOM\DOMElement;
use andreskrey\Readability\Nodes\DOM\DOMNode;
use andreskrey\Readability\Nodes\DOM\DOMText;
use DOMNodeList;

/**
 * @method \DOMNode removeAttribute($name)
 */
trait NodeTrait
{
    /**
     * Content score of the node. Used to determine the value of the content.
     *
     * @var int
     */
    public $contentScore = 0;

    /**
     * Flag for initialized status.
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Flag data tables.
     *
     * @var bool
     */
    private $readabilityDataTable = false;

    /**
     * @var array
     */
    private $divToPElements = [
        'a',
        'blockquote',
        'dl',
        'div',
        'img',
        'ol',
        'p',
        'pre',
        'table',
        'ul',
        'select',
    ];

    /**
     * The commented out elements qualify as phrasing content but tend to be
     * removed by readability when put into paragraphs, so we ignore them here.
     *
     * @var array
     */
    private $phrasing_elems = [
        // 'CANVAS', 'IFRAME', 'SVG', 'VIDEO',
        'abbr', 'audio', 'b', 'bdo', 'br', 'button', 'cite', 'code', 'data',
        'datalist', 'dfn', 'em', 'embed', 'i', 'img', 'input', 'kbd', 'label',
        'mark', 'math', 'meter', 'noscript', 'object', 'output', 'progress', 'q',
        'ruby', 'samp', 'script', 'select', 'small', 'span', 'strong', 'sub',
        'sup', 'textarea', 'time', 'var', 'wbr'
    ];

    /**
     * initialized getter.
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * @return bool
     */
    public function isReadabilityDataTable()
    {
        /*
         * This is a workaround that I'd like to remove in the future.
         * Seems that although we are extending the base DOMElement and adding custom properties (like this one,
         * 'readabilityDataTable'), these properties get lost when you search for elements with getElementsByTagName.
         * This means that even if we mark the tables in a previous step, when we want to retrieve that information,
         * all the custom properties are in their default values. Somehow we need to find a way to make these properties
         * permanent across the whole DOM.
         *
         * @see https://stackoverflow.com/questions/35654709/php-registernodeclass-and-reusing-variable-names
         */
        return $this->hasAttribute('readabilityDataTable')
            && $this->getAttribute('readabilityDataTable') === '1';
//        return $this->readabilityDataTable;
    }

    /**
     * @param bool $param
     */
    public function setReadabilityDataTable($param)
    {
        // Can't be "true" because DOMDocument casts it to "1"
        $this->setAttribute('readabilityDataTable', $param ? '1' : '0');
//        $this->readabilityDataTable = $param;
    }

    /**
     * Initializer. Calculates the current score of the node and returns a full Readability object.
     *
     * @ TODO: I don't like the weightClasses param. How can we get the config here?
     *
     * @param $weightClasses bool Weight classes?
     *
     * @return static
     */
    public function initializeNode($weightClasses)
    {
        if (!$this->isInitialized()) {
            $contentScore = 0;

            switch ($this->nodeName) {
                case 'div':
                    $contentScore += 5;
                    break;

                case 'pre':
                case 'td':
                case 'blockquote':
                    $contentScore += 3;
                    break;

                case 'address':
                case 'ol':
                case 'ul':
                case 'dl':
                case 'dd':
                case 'dt':
                case 'li':
                case 'form':
                    $contentScore -= 3;
                    break;

                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                case 'th':
                    $contentScore -= 5;
                    break;
            }

            $this->contentScore = $contentScore + ($weightClasses ? $this->getClassWeight() : 0);

            $this->initialized = true;
        }

        return $this;
    }

    /**
     * Override for native getAttribute method. Some nodes have the getAttribute method, some don't, so we need
     * to check first the existence of the attributes property.
     *
     * @param $attributeName string Attribute to retrieve
     *
     * @return string
     */
    public function getAttribute($attributeName)
    {
        if (!is_null($this->attributes)) {
            return parent::getAttribute($attributeName);
        }

        return '';
    }

    /**
     * Override for native hasAttribute.
     *
     * @param $attributeName
     *
     * @return bool
     *
     * @see getAttribute
     */
    public function hasAttribute($attributeName)
    {
        if (!is_null($this->attributes)) {
            return parent::hasAttribute($attributeName);
        }

        return false;
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

        $node = $this->parentNode;

        while ($node && !($node instanceof DOMDocument)) {
            $ancestors[] = $node;
            $level++;
            if ($level === $maxLevel) {
                break;
            }
            $node = $node->parentNode;
        }

        return $ancestors;
    }

    /**
     * Returns all links from the current element.
     *
     * @return array
     */
    public function getAllLinks()
    {
        return iterator_to_array($this->getElementsByTagName('a'));
    }

    /**
     * Get the density of links as a percentage of the content
     * This is the amount of text that is inside a link divided by the total text in the node.
     *
     * @return int
     */
    public function getLinkDensity()
    {
        $linkLength = 0;
        $textLength = mb_strlen($this->getTextContent(true));

        if (!$textLength) {
            return 0;
        }

        $links = $this->getAllLinks();

        if ($links) {
            /** @var DOMElement $link */
            foreach ($links as $link) {
                $linkLength += mb_strlen($link->getTextContent(true));
            }
        }

        return $linkLength / $textLength;
    }

    /**
     * Calculates the weight of the class/id of the current element.
     *
     * @return int
     */
    public function getClassWeight()
    {
        $weight = 0;

        // Look for a special classname
        $class = $this->getAttribute('class');
        if (trim($class)) {
            if (preg_match(NodeUtility::$regexps['negative'], $class)) {
                $weight -= 25;
            }

            if (preg_match(NodeUtility::$regexps['positive'], $class)) {
                $weight += 25;
            }
        }

        // Look for a special ID
        $id = $this->getAttribute('id');
        if (trim($id)) {
            if (preg_match(NodeUtility::$regexps['negative'], $id)) {
                $weight -= 25;
            }

            if (preg_match(NodeUtility::$regexps['positive'], $id)) {
                $weight += 25;
            }
        }

        return $weight;
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
        $nodeValue = $this->nodeValue;
        if ($normalize) {
            $nodeValue = trim(preg_replace('/\s{2,}/', ' ', $nodeValue));
        }

        return $nodeValue;
    }

    /**
     * Returns the children of the current node.
     *
     * @param bool $filterEmptyDOMText Filter empty DOMText nodes?
     *
     * @deprecated Use NodeUtility::filterTextNodes, function will be removed in version 3.0
     *
     * @return array
     */
    public function getChildren($filterEmptyDOMText = false)
    {
        @trigger_error('getChildren was replaced with NodeUtility::filterTextNodes and will be removed in version 3.0', E_USER_DEPRECATED);

        $ret = iterator_to_array($this->childNodes);
        if ($filterEmptyDOMText) {
            // Array values is used to discard the key order. Needs to be 0 to whatever without skipping any number
            $ret = array_values(array_filter($ret, function ($node) {
                return $node->nodeName !== '#text' || mb_strlen(trim($node->nodeValue));
            }));
        }

        return $ret;
    }

    /**
     * Return an array indicating how many rows and columns this table has.
     *
     * @return array
     */
    public function getRowAndColumnCount()
    {
        $rows = $columns = 0;
        $trs = $this->getElementsByTagName('tr');
        foreach ($trs as $tr) {
            /** @var \DOMElement $tr */
            $rowspan = $tr->getAttribute('rowspan');
            $rows += ($rowspan || 1);

            // Now look for column-related info
            $columnsInThisRow = 0;
            $cells = $tr->getElementsByTagName('td');
            foreach ($cells as $cell) {
                /** @var \DOMElement $cell */
                $colspan = $cell->getAttribute('colspan');
                $columnsInThisRow += ($colspan || 1);
            }
            $columns = max($columns, $columnsInThisRow);
        }

        return ['rows' => $rows, 'columns' => $columns];
    }

    /**
     * Creates a new node based on the text content of the original node.
     *
     * @param $originalNode DOMNode
     * @param $tagName string
     *
     * @return DOMElement
     */
    public function createNode($originalNode, $tagName)
    {
        $text = $originalNode->getTextContent();
        $newNode = $originalNode->ownerDocument->createElement($tagName, $text);

        return $newNode;
    }

    /**
     * Check if a given node has one of its ancestor tag name matching the
     * provided one.
     *
     * @param string $tagName
     * @param int $maxDepth
     * @param callable $filterFn
     *
     * @return bool
     */
    public function hasAncestorTag($tagName, $maxDepth = 3, callable $filterFn = null)
    {
        $depth = 0;
        $node = $this;

        while ($node->parentNode) {
            if ($maxDepth > 0 && $depth > $maxDepth) {
                return false;
            }

            if ($node->parentNode->nodeName === $tagName && (!$filterFn || $filterFn($node->parentNode))) {
                return true;
            }

            $node = $node->parentNode;
            $depth++;
        }

        return false;
    }

    /**
     * Check if this node has only whitespace and a single element with given tag
     * or if it contains no element with given tag or more than 1 element.
     *
     * @param $tag string Name of tag
     *
     * @return bool
     */
    public function hasSingleTagInsideElement($tag)
    {
        // There should be exactly 1 element child with given tag
        if (count($children = NodeUtility::filterTextNodes($this->childNodes)) !== 1 || $children->item(0)->nodeName !== $tag) {
            return false;
        }

        // And there should be no text nodes with real content
        return array_reduce(iterator_to_array($children), function ($carry, $child) {
            if (!$carry === false) {
                return false;
            }

            /* @var DOMNode $child */
            return !($child->nodeType === XML_TEXT_NODE && !preg_match('/\S$/', $child->getTextContent()));
        });
    }

    /**
     * Check if the current element has a single child block element.
     * Block elements are the ones defined in the divToPElements array.
     *
     * @return bool
     */
    public function hasSingleChildBlockElement()
    {
        $result = false;
        if ($this->hasChildNodes()) {
            foreach ($this->childNodes as $child) {
                if (in_array($child->nodeName, $this->divToPElements)) {
                    $result = true;
                } else {
                    // If any of the hasSingleChildBlockElement calls return true, return true then.
                    /** @var $child DOMElement */
                    $result = ($result || $child->hasSingleChildBlockElement());
                }
            }
        }

        return $result;
    }

    /**
     * Determines if a node has no content or it is just a bunch of dividing lines and/or whitespace.
     *
     * @return bool
     */
    public function isElementWithoutContent()
    {
        return $this instanceof DOMElement &&
            mb_strlen(preg_replace(NodeUtility::$regexps['onlyWhitespace'], '', $this->textContent)) === 0 &&
            ($this->childNodes->length === 0 ||
                $this->childNodes->length === $this->getElementsByTagName('br')->length + $this->getElementsByTagName('hr')->length
                /*
                 * Special PHP DOMDocument case: We also need to count how many DOMText we have inside the node.
                 * If there's an empty tag with an space inside and a BR (for example "<p> <br/></p>) counting only BRs and
                 * HRs will will say that the example has 2 nodes, instead of one. This happens because in DOMDocument,
                 * DOMTexts are also nodes (which doesn't happen in JS). So we need to also count how many DOMText we
                 * are dealing with (And at this point we know they are empty or are just whitespace, because of the
                 * mb_strlen in this chain of checks).
                 */
                + count(array_filter(iterator_to_array($this->childNodes), function ($child) {
                    return $child instanceof DOMText;
                }))

            );
    }

    /**
     * Determine if a node qualifies as phrasing content.
     * https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Content_categories#Phrasing_content.
     *
     * @return bool
     */
    public function isPhrasingContent()
    {
        return $this->nodeType === XML_TEXT_NODE || in_array($this->nodeName, $this->phrasing_elems) !== false ||
            (!is_null($this->childNodes) &&
                ($this->nodeName === 'a' || $this->nodeName === 'del' || $this->nodeName === 'ins') &&
                array_reduce(iterator_to_array($this->childNodes), function ($carry, $node) {
                    return $node->isPhrasingContent() && $carry;
                }, true)
            );
    }

    /**
     * In the original JS project they check if the node has the style display=none, which unfortunately
     * in our case we have no way of knowing that. So we just check for the attribute hidden or "display: none".
     *
     * Might be a good idea to check for classes or other attributes like 'aria-hidden'
     *
     * @return bool
     */
    public function isProbablyVisible()
    {
        return !preg_match('/display:( )?none/', $this->getAttribute('style')) && !$this->hasAttribute('hidden');
    }

    /**
     * @return bool
     */
    public function isWhitespace()
    {
        return ($this->nodeType === XML_TEXT_NODE && mb_strlen(trim($this->textContent)) === 0) ||
            ($this->nodeType === XML_ELEMENT_NODE && $this->nodeName === 'br');
    }

    /**
     * This is a hack that overcomes the issue of node shifting when scanning and removing nodes.
     *
     * In the JS version of getElementsByTagName, if you remove a node it will not appear during the
     * foreach. This does not happen in PHP DOMDocument, because if you remove a node, it will still appear but as an
     * orphan node and will give an exception if you try to do anything with it.
     *
     * Shifting also occurs when converting parent nodes (like a P to a DIV), which in that case the found nodes are
     * removed from the foreach "pool" but the internal index of the foreach is not aware and skips over nodes that
     * never looped over. (index is at position 5, 2 nodes are removed, next one should be node 3, but the foreach tries
     * to access node 6)
     *
     * This function solves this by searching for the nodes on every loop and keeping track of the count differences.
     * Because on every loop we call getElementsByTagName again, this could cause a performance impact and should be
     * used only when the results of the search are going to be used to remove the nodes.
     *
     * @param string $tag
     *
     * @return \Generator
     */
    public function shiftingAwareGetElementsByTagName($tag)
    {
        /** @var $nodes DOMNodeList */
        $nodes = $this->getElementsByTagName($tag);
        $count = $nodes->length;

        for ($i = 0; $i < $count; $i = max(++$i, 0)) {
            yield $nodes->item($i);

            // Search for all the nodes again
            $nodes = $this->getElementsByTagName($tag);

            // Subtract the amount of nodes removed from the current index
            $i -= $count - $nodes->length;

            // Subtract the amount of nodes removed from the current count
            $count -= ($count - $nodes->length);
        }
    }

    /**
     * Mimics JS's firstElementChild property. PHP only has firstChild which could be any type of DOMNode. Use this
     * function to get the first one that is an DOMElement node.
     *
     * @return \DOMElement|null
     */
    public function getFirstElementChild()
    {
        if ($this->childNodes instanceof \Traversable) {
            foreach ($this->childNodes as $node) {
                if ($node instanceof \DOMElement) {
                    return $node;
                }
            }
        }

        return null;
    }
}
