<?php

namespace andreskrey\Readability;


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
}