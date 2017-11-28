<?php

namespace andreskrey\Readability;
use andreskrey\Readability\NodeClass\DOMDocument;
use andreskrey\Readability\NodeClass\DOMNode;

/**
 * Class NodeUtility
 * @package andreskrey\Readability
 */
class NodeUtility
{

    /**
     * @var array
     */
    private static $divToPElements = [
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
     *
     * Imported from the Element class on league\html-to-markdown
     *
     * @param $node
     * @return mixed
     */
    public static function nextElement($node)
    {
        $next = $node;
        while ($next
            && $next->nodeName !== '#text'
            && trim($next->textContent)) {
            $next = $next->nextSibling;
        }

        return $next;
    }


    /**
     * Changes the node tag name. Since tagName on DOMElement is a read only value, this must be done creating a new
     * element with the new tag name and importing it to the main DOMDocument.
     *
     * @param string $value
     * @param bool $importAttributes
     * @return DOMNode
     */
    public static function setNodeTag($node, $value, $importAttributes = false)
    {
        $new = new DOMDocument('1.0', 'utf-8');
        $new->appendChild($new->createElement($value));

        $children = $node->childNodes;
        /** @var $children \DOMNodeList $i */

        for ($i = 0; $i < $children->length; $i++) {
            $import = $new->importNode($children->item($i), true);
            $new->firstChild->appendChild($import);
        }

        if ($importAttributes) {
            // Import attributes from the original node.
            foreach ($node->attributes as $attribute) {
                $new->firstChild->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }
        }

        // The import must be done on the firstChild of $new, since $new is a DOMDocument and not a DOMElement.
        $import = $node->ownerDocument->importNode($new->firstChild, true);
        $node->parentNode->replaceChild($import, $node);

        return $import;
    }

    /**
     * Removes the current node and returns the next node to be parsed (child, sibling or parent).
     *
     * @param DOMNode $node
     *
     * @return DOMNode
     */
    public static function removeAndGetNext($node)
    {
        $nextNode = self::getNextNode($node, true);
        $node->parentNode->removeChild($node);

        return $nextNode;
    }

    /**
     * Returns the next node. First checks for childs (if the flag allows it), then for siblings, and finally
     * for parents.
     *
     * @param DOMNode $originalNode
     * @param bool $ignoreSelfAndKids
     *
     * @return DOMNode
     */
    public static function getNextNode($originalNode, $ignoreSelfAndKids = false)
    {
        /*
         * Traverse the DOM from node to node, starting at the node passed in.
         * Pass true for the second parameter to indicate this node itself
         * (and its kids) are going away, and we want the next node over.
         *
         * Calling this in a loop will traverse the DOM depth-first.
         */

        // First check for kids if those aren't being ignored
        if (!$ignoreSelfAndKids && $originalNode->firstChild) {
            return $originalNode->firstChild;
        }

        // Then for siblings...
        if ($originalNode->nextSibling) {
            return $originalNode->nextSibling;
        }

        // And finally, move up the parent chain *and* find a sibling
        // (because this is depth-first traversal, we will have already
        // seen the parent nodes themselves).
        do {
            $originalNode = $originalNode->getParent();
        } while ($originalNode && !$originalNode->nextSibling);

        return ($originalNode) ? $originalNode->nextSibling : $originalNode;
    }

    /**
     * Checks if the current node has a single child and if that child is a P node.
     * Useful to convert <div><p> nodes to a single <p> node and avoid confusing the scoring system since div with p
     * tags are, in practice, paragraphs.
     *
     * @param DOMNode $node
     *
     * @return bool
     */
    public static function hasSinglePNode($node)
    {
        // There should be exactly 1 element child which is a P:
        if (count($children = $node->getChildren(true)) !== 1 || $children[0]->nodeName !== 'p') {
            return false;
        }

        // And there should be no text nodes with real content (param true on ->getChildren)
        foreach ($children as $child) {
            /** @var $child DOMNode */
            if ($child->nodeType === XML_TEXT_NODE && !preg_match('/\S$/', $child->getTextContent())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $node DOMNode
     * @return bool
     */
    public static function hasSingleChildBlockElement($node)
    {
        $result = false;
        if ($node->hasChildNodes()) {
            foreach ($node->getChildren() as $child) {
                if (in_array($child->nodeName, self::$divToPElements)) {
                    $result = true;
                } else {
                    // If any of the hasSingleChildBlockElement calls return true, return true then.
                    $result = ($result || self::hasSingleChildBlockElement($child));
                }
            }
        }

        return $result;
    }

    /**
     * Returns the full text of the node.
     *
     * @param $node DOMNode
     * @param bool $normalize Normalize white space?
     * @return string
     */
    public static function getTextContent($node, $normalize = false)
    {
        $nodeValue = $node->nodeValue;
        if ($normalize) {
            $nodeValue = trim(preg_replace('/\s{2,}/', ' ', $nodeValue));
        }

        return $nodeValue;
    }

}
