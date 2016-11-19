<?php

namespace andreskrey\Readability;

use League\HTMLToMarkdown\Element;

/**
 * Class DOMElement.
 *
 * This is a extension of the original Element class from League\HTMLToMarkdown\Element.
 * This class adds functions specific to Readability.php and overloads some of them to fit the purpose of this project.
 */
class Readability extends Element implements ReadabilityInterface
{
    /**
     * @var \DOMNode
     */
    protected $node;

    /**
     * @var int
     */
    protected $contentScore = 0;

    /**
     * @var int
     */
    protected $initialized = false;

    /**
     * @var array
     */
    private $regexps = [
        'positive' => '/article|body|content|entry|hentry|h-entry|main|page|pagination|post|text|blog|story/i',
        'negative' => '/hidden|^hid$| hid$| hid |^hid |banner|combx|comment|com-|contact|foot|footer|footnote|masthead|media|meta|modal|outbrain|promo|related|scroll|share|shoutbox|sidebar|skyscraper|sponsor|shopping|tags|tool|widget/i',
    ];

    /**
     * Constructor.
     *
     * @param \DOMNode $node Selected element from DOMDocument
     */
    public function __construct(\DOMNode $node)
    {
        parent::__construct($node);

        if (get_class($node) !== 'DOMText') {
            /*
             * Restore the score if the object has been already scored.
             *
             * An if must be added before calling the getAttribute function, because if we reach the DOMDocument
             * by getting the node parents we'll get a undefined function fatal error
             */
            $score = 0;

            if (!in_array(get_class($node), ['DOMDocument', 'DOMComment'])) {
                $hasScore = $node->getAttribute('readability');
                if ($hasScore !== '') {
                    // Node was initialized previously. Restoring score and setting flag.
                    $this->initialized = true;
                    $score = $hasScore;
                } else {
                    // Fresh, uninitialized node.
                    $score = 0;
                }
            }

            $this->setContentScore($score);
        }
    }

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
     * Get the ancestors of the current node.
     *
     * @param int $maxLevel Max amount of ancestors to get.
     *
     * @return array
     */
    public function getNodeAncestors($maxLevel = 3)
    {
        $ancestors = [];
        $level = 0;

        $node = $this->getParent();

        while ($node) {
            $ancestors[] = $node->initializeNode();
            $level++;
            if ($level >= $maxLevel) {
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
     * Initializer. Calculates the current score of the node and returns a full Readability object.
     *
     * @return Readability
     */
    public function initializeNode()
    {
        if (!$this->initialized) {
            $contentScore = 0;

            switch ($this->getTagName()) {
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

            $this->setContentScore($contentScore + $this->getClassWeight());

            $this->initialized = true;
        }

        return $this;
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
        // if(!Config::FLAG_WEIGHT_CLASSES) return 0;

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
     *
     * @return int
     */
    public function setContentScore($score)
    {
        if (!in_array(get_class($this->node), ['DOMDocument', 'DOMComment'])) {

            $this->contentScore = (float)$score;

            // Set score in an attribute of the tag to prevent losing it while creating new Readability objects.
            $this->node->setAttribute('readability', $this->contentScore);

            return $this->contentScore;
        }

        return 0;
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
     * Changes the node tag name. Since tagName on DOMElement is a read only value, this must be done creating a new
     * element with the new tag name and importing it to the main DOMDocument
     *
     * @param string $value
     */
    public function setNodeTag($value)
    {
        $new = new \DOMDocument();
        $new->appendChild($new->createElement($value));

        $childs = $this->node->childNodes;
        for ($i = 0; $i < $childs->length; $i++) {
            $import = $new->importNode($childs->item($i), true);
            $new->firstChild->appendChild($import);
        }

        // The import must be done on the firstChild of $new, since $new is a DOMDocument and not a DOMElement.
        $import = $this->node->ownerDocument->importNode($new->firstChild, true);
        $this->node->parentNode->replaceChild($import, $this->node);

        $this->node = $import;
    }

    /**
     * Returns the current DOMNode.
     *
     * @return \DOMNode
     */
    public function getDOMNode()
    {
        return $this->node;
    }

    /**
     * Removes the current node and returns the next node to be parsed (child, sibling or parent)
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
     * Replaces child node with a new one.
     *
     * @param Readability $newNode
     *
     */
    public function replaceChild(Readability $newNode)
    {
        $this->node->parentNode->replaceChild($newNode->node, $this->node);
    }

    /**
     * Creates a new node based on the text content of the original node.
     *
     * @param Readability $originalNode
     * @param string $tagName
     *
     * @return Readability
     */
    public function createNode(Readability $originalNode, $tagName)
    {
        $text = $originalNode->getTextContent();
        $newnode = $originalNode->node->ownerDocument->createElement($tagName, $text);

        $return = $originalNode->node->appendChild($newnode);

        return new static($return);
    }

    /**
     * Checks if the object is initialized.
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }
}
