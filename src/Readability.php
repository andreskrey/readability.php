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
     * Checks if the current node has a single child and if that child is a P node.
     * Useful to convert <div><p> nodes to a single <p> node and avoid confusing the scoring system since div with p
     * tags are, in practice, paragraphs.
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

        $node = $this;

        while ($node && $node->getParent()) {
            $ancestors[] = new static($node->node);
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

        return ($node) ? new static($node) : null;
    }

    /**
     * Returns all links from the current element.
     *
     * @return Readability|null
     */
    public function getAllLinks()
    {
        return ($this->isText()) ? null : $this->node->getElementsByTagName('a');
    }

    /**
     * Initializer. Calculates the current score of the node and returns a full Readability object.
     *
     * @return Readability
     */
    public function initializeNode()
    {
        switch ($this->getTagName()) {
            case 'div':
                $this->contentScore += 5;
                break;

            case 'pre':
            case 'td':
            case 'blockquote':
                $this->contentScore += 3;
                break;

            case 'address':
            case 'ol':
            case 'ul':
            case 'dl':
            case 'dd':
            case 'dt':
            case 'li':
            case 'form':
                $this->contentScore -= 3;
                break;

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'th':
                $this->contentScore -= 5;
                break;
        }

        $this->contentScore += $this->getClassWeight();

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
        $id = $this->getAttribute('class');
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
        $this->contentScore = $score;

        return $this->contentScore;
    }

    /**
     * Returns the full text of the node.
     *
     * @return string
     */
    public function getTextContent()
    {
        return $this->getChildrenAsString();
    }
}
