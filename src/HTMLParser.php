<?php

namespace andreskrey\Readability;

use DOMDocument;

/**
 * Class HTMLParser.
 *
 * A helper class to parse HTML and get a Readability object.
 */
class HTMLParser
{
    /**
     * @var DOMDocument
     */
    private $dom = null;

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var array
     */
    private $title = [];

    /**
     * @var array
     */
    private $elementsToScore = [];

    /**
     * @var array
     */
    private $regexps = [
        'unlikelyCandidates' => '/banner|combx|comment|community|disqus|extra|foot|header|menu|modal|related|remark|rss|share|shoutbox|sidebar|skyscraper|sponsor|ad-break|agegate|pagination|pager|popup/i',
        'okMaybeItsACandidate' => '/and|article|body|column|main|shadow/i',
        'extraneous' => '/print|archive|comment|discuss|e[\-]?mail|share|reply|all|login|sign|single|utility/i',
        'byline' => '/byline|author|dateline|writtenby|p-author/i',
        'replaceFonts' => '/<(\/?)font[^>]*>/gi',
        'normalize' => '/\s{2,}/g',
        'videos' => '/\/\/(www\.)?(dailymotion|youtube|youtube-nocookie|player\.vimeo)\.com/i',
        'nextLink' => '/(next|weiter|continue|>([^\|]|$)|»([^\|]|$))/i',
        'prevLink' => '/(prev|earl|old|new|<|«)/i',
        'whitespace' => '/^\s*$/',
        'hasContent' => '/\S$/',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->dom = new DOMDocument('1.0', 'utf-8');

        // To avoid having a gazillion of errors on malformed HTMLs
        libxml_use_internal_errors(true);
    }

    /**
     * Parse the html. This is the main entry point of the HTMLParser.
     *
     * @param string $html Full html of the website, page, etc.
     *
     * #return ? TBD
     */
    public function parse($html)
    {
        $this->loadHTML($html);

        $this->removeScripts();

        $this->metadata = $this->getMetadata();

        $this->title = $this->getTitle();

        if (!($root = $this->dom->getElementsByTagName('body')->item(0))) {
            throw new \InvalidArgumentException('Invalid HTML was provided');
        }

        $root = new DOMElement($root);

        $this->getNodes($root);

        $this->rateNodes($this->elementsToScore);
    }

    /**
     * @param string $html
     */
    private function loadHTML($html)
    {
        $this->dom->loadHTML($html);
        $this->dom->encoding = 'UTF-8';
    }

    /**
     * Removes all the scripts of the html.
     *
     * @TODO is this really necessary? Readability.js uses it to chop any script that might interfere with their
     * system. Is it necessary here?
     */
    private function removeScripts()
    {
        while ($script = $this->dom->getElementsByTagName('script')) {
            if ($script->item(0)) {
                $script->item(0)->parentNode->removeChild($script->item(0));
            } else {
                break;
            }
        }
    }

    /**
     * Tries to guess relevant info from metadata of the html.
     *
     * @return array Metadata info. May have title, excerpt and or byline.
     */
    private function getMetadata()
    {
        $metadata = [];
        foreach ($this->dom->getElementsByTagName('meta') as $meta) {
            /* @var DOMElement $meta */
            $name = $meta->getAttribute('name');
            $property = $meta->getAttribute('property');

            // Select either name or property
            $item = ($name ? $name : $property);

            if ($item == 'og:title' || $item == 'twitter:title') {
                $metadata['title'] = $meta->getAttribute('content');
            }

            if ($item == 'og:description' || $item == 'twitter:description') {
                $metadata['excerpt'] = $meta->getAttribute('content');
            }

            if ($item == 'author') {
                $metadata['byline'] = $meta->getAttribute('content');
            }
        }

        return $metadata;
    }

    /**
     * Get the density of links as a percentage of the content
     * This is the amount of text that is inside a link divided by the total text in the node.
     *
     * @param Readability $readability
     *
     * @return int
     */
    public function getLinkDensity($readability)
    {
        $linkLength = 0;
        $textLength = strlen($readability->getTextContent());

        if (!$textLength) {
            return 0;
        }

        $links = $readability->getAllLinks();

        foreach ($links as $link) {
            // TODO This is not very pretty, $link should be a Element type
            $linkLength += strlen($link->C14N());
        }

        return $linkLength / $textLength;
    }

    /**
     * Returns the title of the html. Prioritizes the title from the metadata against the title tag.
     *
     * @return string|null
     */
    private function getTitle()
    {
        if (isset($this->metadata['title'])) {
            return $this->metadata['title'];
        }

        $title = $this->dom->getElementsByTagName('title');
        if ($title) {
            return $title->item(0)->nodeValue;
        }

        return null;
    }

    /**
     * Gets nodes from the root element.
     *
     * @param $node DOMElementInterface
     */
    private function getNodes(DOMElementInterface $node)
    {
        $matchString = $node->getAttribute('class') . ' ' . $node->getAttribute('id');

        // Avoid elements that are unlikely to have any useful information.
        if (
            preg_match($this->regexps['unlikelyCandidates'], $matchString) &&
            !preg_match($this->regexps['okMaybeItsACandidate'], $matchString) &&
            !$node->tagNameEqualsTo('body') &&
            !$node->tagNameEqualsTo('a')
        ) {
            return;
        }

        // Loop over the element if it has children
        if ($node->hasChildren()) {
            foreach ($node->getChildren() as $child) {
                $this->getNodes($child);
            }
        }

        // Check for nodes that have only on P node as a child and convert them to a single P node
        if ($node->hasSinglePNode()) {
            $pNode = $node->getChildren();
            $node = $pNode[0];
        }

        // If there's any info on the node, add it to the elements to score in the next step.
        if (trim($node->getValue())) {
            $this->elementsToScore[] = $node;
        }
    }

    /**
     * Assign scores to each node. This function will rate each node and return a Readability object for each one.
     *
     * @param array $nodes
     */
    private function rateNodes($nodes)
    {
        $candidates = [];

        foreach ($nodes as $node) {

            // Discard nodes with less than 25 characters
            if (strlen($node->getValue()) < 25) {
                continue;
            }

            $ancestors = $node->getNodeAncestors();

            // Exclude nodes with no ancestor
            if ($ancestors === 0) {
                continue;
            }

            // Start with a point for the paragraph itself as a base.
            $contentScore = 1;

            // Add points for any commas within this paragraph.
            $contentScore += count(explode(', ', $node->getValue()));

            // For every 100 characters in this paragraph, add another point. Up to 3 points.
            $contentScore += min(floor(strlen($node->getValue()) / 100), 3);

            // Initialize and score ancestors.
            foreach ($ancestors as $level => $ancestor) {
                $readability = new Readability($ancestor);
                $readability = $readability->initializeNode();

                /*
                 * Node score divider:
                 *  - parent:             1 (no division)
                 *  - grandparent:        2
                 *  - great grandparent+: ancestor level * 3
                 */

                if ($level === 0) {
                    $scoreDivider = 1;
                } else if ($level === 1) {
                    $scoreDivider = 2;
                } else {
                    $scoreDivider = $level * 3;
                }

                $currentScore = $readability->getContentScore();
                $readability->setContentScore($currentScore + ($contentScore / $scoreDivider));

                $candidates[] = $readability;
            }

            /*
             * After we've calculated scores, loop through all of the possible
             * candidate nodes we found and find the one with the highest score.
             */

            $topCandidates = [];
            foreach ($candidates as $candidate) {
                /*
                 * Scale the final candidates score based on link density. Good content
                 * should have a relatively small link density (5% or less) and be mostly
                 * unaffected by this operation.
                 */

                $candidate->setContentScore($candidate->getContentScore() * (1 - $this->getLinkDensity($candidate)));

            }
        }
    }
}
