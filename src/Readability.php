<?php

namespace andreskrey\Readability;

class Readability implements ReadabilityInterface
{
    /**
     * @var int
     */
    protected $contentScore = 0;

    /**
     * @var null
     */
    protected $node;

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
     * @param DOMElement $node
     */
    public function __construct($node)
    {
        $this->node = $node;
    }

    /**
     * Initializer. Calculates the current score of the node and returns a full Readability object.
     *
     * @return Readability
     */
    public function initializeNode()
    {
        switch ($this->node->getTagName()) {
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
        $class = $this->node->getAttribute('class');
        if (trim($class)) {
            if (preg_match($this->regexps['negative'], $class)) {
                $weight -= 25;
            }

            if (preg_match($this->regexps['positive'], $class)) {
                $weight += 25;
            }
        }

        // Look for a special ID
        $id = $this->node->getAttribute('class');
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
        return $this->node->getChildrenAsString();
    }

    /**
     * Returns all links from the current element.
     *
     * @return DOMElement|null
     */
    public function getAllLinks()
    {
        return $this->node->getAllLinks();
    }
}
