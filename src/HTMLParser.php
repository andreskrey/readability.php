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

    private $defaultTagsToScore = [
        'section',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'p',
        'td',
        'pre',
    ];

    /**
     * @var array
     */
    private $alterToDIVExceptions = [
        'div',
        'article',
        'section',
        'p',
        // TODO, check if this is correct, #text elements do not exist in js
        '#text',
    ];

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
        'select'
    ];

    /**
     * Constructor.
     *
     * @param array $options Options to override the default ones
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'maxTopCandidates' => 5, // Max amount of top level candidates
            'articleByLine' => null,
            'stripUnlikelyCandidates' => true
        ];

        $this->environment = Environment::createDefaultEnvironment($defaults);

        $this->environment->getConfig()->merge($options);

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

        // Checking for minimum HTML to work with.
        if (!($root = $this->dom->getElementsByTagName('body')->item(0))) {
            return false;
        }

        $root = new Readability($root->firstChild);

        $this->getNodes($root);

        $result = $this->rateNodes($this->elementsToScore);

        // Todo, fix return, check for values, maybe create a function to create the return object
        return [
            'title' => $this->metadata['title'],
            'author' => $this->metadata['author'],
            'image' => $this->metadata['image'],
            'article' => $result
        ];
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
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->environment->getConfig();
    }

    /**
     * Removes all the scripts of the html.
     */
    private function removeScripts()
    {
        $toRemove = ['script', 'noscript'];

        foreach ($toRemove as $tag) {
            while ($script = $this->dom->getElementsByTagName($tag)) {
                if ($script->item(0)) {
                    $script->item(0)->parentNode->removeChild($script->item(0));
                } else {
                    break;
                }
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
            /* @var Readability $meta */
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

            if ($item == 'og:image' || $item == 'twitter:image') {
                $metadata['image'] = $meta->getAttribute('content');
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
        $textLength = strlen($readability->getTextContent(true));

        if (!$textLength) {
            return 0;
        }

        $links = $readability->getAllLinks();

        if ($links) {
            /** @var Readability $link */
            foreach ($links as $link) {
                $linkLength += strlen($link->getTextContent(true));
            }
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
     * @param $node Readability
     */
    private function getNodes(Readability $node)
    {
        $stripUnlikelyCandidates = $this->getConfig()->getOption('stripUnlikelyCandidates');

        /*
         * First, node prepping. Trash nodes that look cruddy (like ones with the
         * class name "comment", etc), and turn divs into P tags where they have been
         * used inappropriately (as in, where they contain no other block level elements.)
         */

        while ($node) {
            $matchString = $node->getAttribute('class') . ' ' . $node->getAttribute('id');

            // Check to see if this node is a byline, and remove it if it is.
            if ($this->checkByline($node, $matchString)) {
                $node = $node->removeAndGetNext($node);
                continue;
            }

            // Remove unlikely candidates
            if ($stripUnlikelyCandidates) {
                if (
                    preg_match($this->regexps['unlikelyCandidates'], $matchString) &&
                    !preg_match($this->regexps['okMaybeItsACandidate'], $matchString) &&
                    !$node->tagNameEqualsTo('body') &&
                    !$node->tagNameEqualsTo('a')
                ) {
                    $node = $node->removeAndGetNext($node);
                    continue;
                }
            }

            if (in_array(strtolower($node->getTagName()), $this->defaultTagsToScore)) {
                $this->elementsToScore[] = $node;
            }

            // Turn all divs that don't have children block level elements into p's
            if ($node->tagNameEqualsTo('div')) {
                /*
                 * Sites like http://mobile.slate.com encloses each paragraph with a DIV
                 * element. DIVs with only a P element inside and no text content can be
                 * safely converted into plain P elements to avoid confusing the scoring
                 * algorithm with DIVs with are, in practice, paragraphs.
                 */
                if ($this->hasSinglePNode($node)) {
                    $pNode = $node->getChildren()[0];
                    $node->replaceChild($pNode);
                    $node = $pNode;
                } elseif (!$this->hasSingleChildBlockElement($node)) {
                    // FIXME!!!
                    $node->setNodeTag('p');
                    $this->elementsToScore[] = $node;
                }
            }

            $node = $node->getNextNode($node);
        }
    }

    /**
     * Assign scores to each node. This function will rate each node and return a Readability object for each one.
     *
     * @param array $nodes
     *
     * @return DOMDocument
     */
    private function rateNodes($nodes)
    {
        $candidates = [];

        /** @var Readability $node */
        foreach ($nodes as $node) {

            // Discard nodes with less than 25 characters, without blank space
            if (strlen($node->getValue(true)) < 25) {
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
            $contentScore += count(explode(',', $node->getValue(true)));

            // For every 100 characters in this paragraph, add another point. Up to 3 points.
            $contentScore += min(floor(strlen($node->getValue(true)) / 100), 3);

            // Initialize and score ancestors.
            /** @var Readability $ancestor */
            foreach ($ancestors as $level => $ancestor) {
                $ancestor = $ancestor->initializeNode();

                /*
                 * Node score divider:
                 *  - parent:             1 (no division)
                 *  - grandparent:        2
                 *  - great grandparent+: ancestor level * 3
                 */

                if ($level === 0) {
                    $scoreDivider = 1;
                } elseif ($level === 1) {
                    $scoreDivider = 2;
                } else {
                    $scoreDivider = $level * 3;
                }

                $currentScore = $ancestor->getContentScore();
                $ancestor->setContentScore($currentScore + ($contentScore / $scoreDivider));

                $candidates[] = $ancestor;
            }
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

            for ($i = 0; $i < $this->getConfig()->getOption('maxTopCandidates'); $i++) {
                $aTopCandidate = isset($topCandidates[$i]) ? $topCandidates[$i] : null;

                if (!$aTopCandidate || $candidate->getContentScore() > $aTopCandidate->getContentScore()) {
                    array_splice($topCandidates, $i, 0, [$candidate]);
                    if (count($topCandidates) > $this->getConfig()->getOption('maxTopCandidates')) {
                        array_pop($topCandidates);
                    }
                    break;
                }
            }
        }

        $topCandidate = isset($topCandidates[0]) ? $topCandidates[0] : null;
        $neededToCreateTopCandidate = false;

        /*
         * If we still have no top candidate, just use the body as a last resort.
         * We also have to copy the body node so it is something we can modify.
         */

        if ($topCandidate === null || $topCandidate->tagNameEqualsTo('body')) {
            // Move all of the page's children into topCandidate
            $neededToCreateTopCandidate = true;

            $topCandidate = new DOMDocument();
            $topCandidate->appendChild($topCandidate->createElement('div', ''));
            $kids = $this->dom->getElementsByTagName('body')->item(0)->childNodes;

            // Cannot be foreached, don't ask me why.
            for ($i = 0; $i <= count($kids); $i++) {
                $import = $topCandidate->importNode($kids->item($i), true);
                $topCandidate->firstChild->appendChild($import);
            }

            // Readability must be created using firstChild to grab de DOMElement instead of the DOMDocument.
            $topCandidate = new Readability($topCandidate->firstChild);
            $topCandidate->initializeNode();

            //TODO on the original code, $topCandidate is added to the page variable, which holds the whole HTML
            // Should be done this here also? (line 823 in readability.js)
        } elseif ($topCandidate) {
            /*
             * Because of our bonus system, parents of candidates might have scores
             * themselves. They get half of the node. There won't be nodes with higher
             * scores than our topCandidate, but if we see the score going *up* in the first
             * few steps up the tree, that's a decent sign that there might be more content
             * lurking in other places that we want to unify in. The sibling stuff
             * below does some of that - but only if we've looked high enough up the DOM
             * tree.
             */

            $parentOfTopCandidate = $topCandidate->getParent();
            $lastScore = $topCandidate->getContentScore();

            // The scores shouldn't get too low.
            $scoreThreshold = $lastScore / 3;

            while ($parentOfTopCandidate) {
                /* @var Readability $parentOfTopCandidate */
                $parentScore = $parentOfTopCandidate->getContentScore();
                if ($parentScore < $scoreThreshold) {
                    break;
                }

                if ($parentScore > $lastScore) {
                    // Alright! We found a better parent to use.
                    $topCandidate = $parentOfTopCandidate;
                    break;
                }
                $lastScore = $parentOfTopCandidate->getContentScore();
                $parentOfTopCandidate = $parentOfTopCandidate->getParent();
            }
        }

        /*
         * Now that we have the top candidate, look through its siblings for content
         * that might also be related. Things like preambles, content split by ads
         * that we removed, etc.
         */

        $articleContent = new DOMDocument();
        $articleContent->createElement('div');

        $siblingScoreThreshold = max(10, $topCandidate->getContentScore() * 0.2);
        $siblings = $topCandidate->getChildren();

        /** @var Readability $sibling */
        foreach ($siblings as $sibling) {
            $append = false;

            if ($sibling->compareNodes($sibling, $topCandidate)) {
                $append = true;
            } else {
                $contentBonus = 0;

                // Give a bonus if sibling nodes and top candidates have the example same classname
                if ($sibling->getAttribute('class') === $topCandidate->getAttribute('class') && $topCandidate->getAttribute('class') !== '') {
                    $contentBonus += $topCandidate->getContentScore() * 0.2;
                }
                if ($sibling->getContentScore() + $contentBonus >= $siblingScoreThreshold) {
                    $append = true;
                } elseif ($sibling->tagNameEqualsTo('p')) {
                    $linkDensity = $this->getLinkDensity($sibling);
                    $nodeContent = $sibling->getTextContent(true);

                    if (strlen($nodeContent) > 80 && $linkDensity < 0.25) {
                        $append = true;
                        // TODO Check if pregmatch is working as expected
                    } elseif ($nodeContent && strlen($nodeContent) < 80 && $linkDensity === 0 && preg_match('//\.( |$)/', $nodeContent)) {
                        $append = true;
                    }
                }
            }

            if ($append) {
                if (in_array(strtolower($sibling->getTagName()), $this->alterToDIVExceptions)) {
                    /*
                     * We have a node that isn't a common block level element, like a form or td tag.
                     * Turn it into a div so it doesn't get filtered out later by accident.
                     */

                    // TODO This is not working! Fix!
//                    $sibling->setNodeName('div');
                }

                $import = $articleContent->importNode($sibling->getDOMNode(), true);
                $articleContent->appendChild($import);
            }
        }

        return $articleContent;
    }

    /**
     * Checks if the node is a byline.
     *
     * @param Readability $node
     * @param string $matchString
     *
     * @return bool
     */
    private function checkByline($node, $matchString)
    {
        if ($this->getConfig()->getOption('articleByLine')) {
            return false;
        }

        $rel = $node->getAttribute('rel');

        if ($rel === 'author' || preg_match($this->regexps['byline'], $matchString) && $this->isValidByline($node->getTextContent())) {
            $this->metadata['byline'] = trim($node->getTextContent());

            return true;
        }

        return false;
    }

    /**
     * Checks the validity of a byLine. Based on string length.
     *
     * @param string $text
     *
     * @return bool
     */
    private function isValidByline($text)
    {
        if (gettype($text) == 'string') {
            $byline = trim($text);

            return (strlen($byline) > 0) && (strlen($text) < 100);
        }

        return false;
    }

    /**
     * Checks if the current node has a single child and if that child is a P node.
     * Useful to convert <div><p> nodes to a single <p> node and avoid confusing the scoring system since div with p
     * tags are, in practice, paragraphs.
     *
     * @param Readability $node
     * @return bool
     */
    private function hasSinglePNode(Readability $node)
    {
        // There should be exactly 1 element child which is a P:
        if ($node->hasChildren()) {
            $children = $node->getChildren();

            if (count($children) === 1) {
                if ($node->tagNameEqualsTo('p')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasSingleChildBlockElement(Readability $node)
    {
        /** @var Readability $child */
        foreach ($node->getChildren() as $child) {
            if (in_array($child->getTagName(), $this->divToPElements)) {
                $this->hasSingleChildBlockElement($child);
            } else {
                return false;
            }
        }

        return true;
    }
}
