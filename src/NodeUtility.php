<?php

namespace andreskrey\Readability;
use andreskrey\Readability\NodeClass\DOMDocument;
use andreskrey\Readability\NodeClass\DOMNode;
use andreskrey\Readability\NodeClass\DOMNodeList;


/**
 * Class NodeUtility
 * @package andreskrey\Readability
 */
class NodeUtility
{

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
        $new = new DOMDocument();
        $new->appendChild($new->createElement($value));

        $children = $node->childNodes;
        /** @var $children DOMNodeList $i */

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


}