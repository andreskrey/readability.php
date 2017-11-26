<?php

namespace andreskrey\Readability;

use andreskrey\Readability\NodeClass\DOMDocument;
use andreskrey\Readability\NodeClass\DOMAttr;
use andreskrey\Readability\NodeClass\DOMCdataSection;
use andreskrey\Readability\NodeClass\DOMCharacterData;
use andreskrey\Readability\NodeClass\DOMComment;
use andreskrey\Readability\NodeClass\DOMDocumentFragment;
use andreskrey\Readability\NodeClass\DOMDocumentType;
use andreskrey\Readability\NodeClass\DOMElement;
use andreskrey\Readability\NodeClass\DOMEntity;
use andreskrey\Readability\NodeClass\DOMEntityReference;
use andreskrey\Readability\NodeClass\DOMException;
use andreskrey\Readability\NodeClass\DOMImplementation;
use andreskrey\Readability\NodeClass\DOMNamedNodeMap;
use andreskrey\Readability\NodeClass\DOMNode;
use andreskrey\Readability\NodeClass\DOMNodeList;
use andreskrey\Readability\NodeClass\DOMNotation;
use andreskrey\Readability\NodeClass\DOMProcessingInstruction;
use andreskrey\Readability\NodeClass\DOMText;

/**
 * Class Readability
 */
class Readability
{
    /**
     * @var string|null
     */
    protected $title = null;
    /**
     * @var string|null
     */
    protected $content = null;
    /**
     * @var string|null
     */
    protected $image = null;
    /**
     * @var string|null
     */
    protected $author = null;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Readability constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        // To avoid having a gazillion of errors on malformed HTMLs
        libxml_use_internal_errors(true);
    }

    public function parse($html)
    {
        $this->loadHTML($html);
    }

    /**
     * Creates a DOM Document object and loads the provided HTML on it.
     *
     * Used for the first load of Readability and subsequent reloads (when disabling flags and rescanning the text)
     * Previous versions of Readability used this method one time and cloned the DOM to keep a backup. This caused bugs
     * because cloning the DOM object keeps a relation between the clone and the original one, doing changes in both
     * objects and ruining the backup.
     *
     * @param string $html
     *
     * @return DOMDocument
     */
    private function loadHTML($html)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->registerNodeClass('DOMAttr', DOMAttr::class);
        $dom->registerNodeClass('DOMCdataSection', DOMCdataSection::class);
        $dom->registerNodeClass('DOMCharacterData', DOMCharacterData::class);
        $dom->registerNodeClass('DOMComment', DOMComment::class);
        $dom->registerNodeClass('DOMDocumentFragment', DOMDocumentFragment::class);
        $dom->registerNodeClass('DOMDocumentType', DOMDocumentType::class);
        $dom->registerNodeClass('DOMElement', DOMElement::class);
        $dom->registerNodeClass('DOMEntity', DOMEntity::class);
        $dom->registerNodeClass('DOMEntityReference', DOMEntityReference::class);
        $dom->registerNodeClass('DOMException', DOMException::class);
        $dom->registerNodeClass('DOMImplementation', DOMImplementation::class);
        $dom->registerNodeClass('DOMNamedNodeMap', DOMNamedNodeMap::class);
        $dom->registerNodeClass('DOMNode', DOMNode::class);
        $dom->registerNodeClass('DOMNodeList', DOMNodeList::class);
        $dom->registerNodeClass('DOMNotation', DOMNotation::class);
        $dom->registerNodeClass('DOMProcessingInstruction', DOMProcessingInstruction::class);
        $dom->registerNodeClass('DOMText', DOMText::class);


        if (!$this->configuration->getSubstituteEntities()) {
            // Keep the original HTML entities
            $dom->substituteEntities = false;
        }

        if ($this->configuration->getNormalizeEntities()) {
            // Replace UTF-8 characters with the HTML Entity equivalent. Useful to fix html with mixed content
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        }

        if ($this->configuration->getSummonCthulhu()) {
            $html = preg_replace('/<script\b[^>]*>([\s\S]*?)<\/script>/', '', $html);
        }

        // Prepend the XML tag to avoid having issues with special characters. Should be harmless.
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $dom->encoding = 'UTF-8';

        $this->removeScripts($dom);

        $this->prepDocument($dom);

        return $dom;
    }

    /**
     * Removes all the scripts of the html.
     *
     * @param DOMDocument $dom
     */
    private function removeScripts(DOMDocument $dom)
    {
        $toRemove = ['script', 'noscript'];

        foreach ($toRemove as $tag) {
            while ($script = $dom->getElementsByTagName($tag)) {
                if ($script->item(0)) {
                    $script->item(0)->parentNode->removeChild($script->item(0));
                } else {
                    break;
                }
            }
        }
    }

    /**
     * Prepares the document for parsing.
     *
     * @param DOMDocument $dom
     */
    private function prepDocument(DOMDocument $dom)
    {
        /*
         * DOMNodeList must be converted to an array before looping over it.
         * This is done to avoid node shifting when removing nodes.
         *
         * Reverse traversing cannot be done here because we need to find brs that are right next to other brs.
         * (If we go the other way around we need to search for previous nodes forcing the creation of new functions
         * that will be used only here)
         */
        foreach (iterator_to_array($dom->getElementsByTagName('br')) as $br) {
            $next = $br->nextSibling;

            /*
             * Whether 2 or more <br> elements have been found and replaced with a
             * <p> block.
             */
            $replaced = false;

            /*
             * If we find a <br> chain, remove the <br>s until we hit another element
             * or non-whitespace. This leaves behind the first <br> in the chain
             * (which will be replaced with a <p> later).
             */
            while (($next = NodeUtility::nextElement($next)) && ($next->nodeName === 'br')) {
                $replaced = true;
                $brSibling = $next->nextSibling;
                $next->parentNode->removeChild($next);
                $next = $brSibling;
            }

            /*
             * If we removed a <br> chain, replace the remaining <br> with a <p>. Add
             * all sibling nodes as children of the <p> until we hit another <br>
             * chain.
             */

            if ($replaced) {
                $p = $dom->createElement('p');
                $br->parentNode->replaceChild($p, $br);

                $next = $p->nextSibling;
                while ($next) {
                    // If we've hit another <br><br>, we're done adding children to this <p>.
                    if ($next->nodeName === 'br') {
                        $nextElem = NodeUtility::nextElement($next);
                        if ($nextElem && $nextElem->nodeName === 'br') {
                            break;
                        }
                    }

                    // Otherwise, make this node a child of the new <p>.
                    $sibling = $next->nextSibling;
                    $p->appendChild($next);
                    $next = $sibling;
                }
            }
        }

        // Replace font tags with span
        $fonts = $dom->getElementsByTagName('font');
        $length = $fonts->length;
        for ($i = 0; $i < $length; $i++) {
            $font = $fonts->item($length - 1 - $i);
            NodeUtility::setNodeTag($font, 'span', true);
        }
    }



    /**
     * @return null|string
     */
    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null $title
     */
    protected function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param null $content
     */
    protected function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param null $image
     */
    protected function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string|null
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param null $author
     */
    protected function setAuthor($author)
    {
        $this->author = $author;
    }

}