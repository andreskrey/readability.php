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
     * @var DOMDocument
     */
    private $backupdom = null;

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
            'stripUnlikelyCandidates' => true,
            'cleanConditionally' => true,
            'removeReadabilityTags' => true
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

        $elementsToScore = $this->getNodes($root);

        $result = $this->rateNodes($elementsToScore);

        // Todo, fix return, check for values, maybe create a function to create the return object
        return [
            'title' => isset($this->metadata['title']) ? $this->metadata['title'] : null,
            'author' => isset($this->metadata['author']) ? $this->metadata['author'] : null,
            'image' => isset($this->metadata['image']) ? $this->metadata['image'] : null,
            'article' => $result,
            'html' => $result->C14N()
        ];
    }

    /**
     * @param string $html
     */
    private function loadHTML($html)
    {
        $this->dom->loadHTML($html);
        $this->dom->encoding = 'UTF-8';

        // In case we need the original HTML to create a fake top candidate
        $this->backupdom = clone $this->dom;
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
        $textLength = mb_strlen($readability->getTextContent(true));

        if (!$textLength) {
            return 0;
        }

        $links = $readability->getAllLinks();

        if ($links) {
            /** @var Readability $link */
            foreach ($links as $link) {
                $linkLength += mb_strlen($link->getTextContent(true));
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

        $elementsToScore = [];

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
                $elementsToScore[] = $node;
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
                    $pNode = $node->getChildren(true)[0];
                    $node->replaceChild($pNode);
                    $node = $pNode;
                } elseif (!$this->hasSingleChildBlockElement($node)) {
                    $node->setNodeTag('p');
                    $elementsToScore[] = $node;
                } else {
                    // EXPERIMENTAL
                    foreach ($node->getChildren() as $child) {
                        if ($child->isText()) {
                            /** @var Readability $child */
                            // Check if there's actual content on the node.
                            if (trim($child->getTextContent())) {
                                $newNode = $node->createNode($child, 'p');
                                $child->replaceChild($newNode);
                            }
                        }
                    }
                }
            }

            $node = $node->getNextNode($node);
        }

        return $elementsToScore;
    }

    /**
     * Assign scores to each node. This function will rate each node and return a Readability object for each one.
     *
     * @param array $nodes
     *
     * @return DOMDocument|bool
     */
    private function rateNodes($nodes)
    {
        $candidates = [];

        /** @var Readability $node */
        foreach ($nodes as $node) {
            if (!$node->getParent()) {
                continue;
            }
            // Discard nodes with less than 25 characters, without blank space
            if (mb_strlen($node->getValue(true)) < 25) {
                continue;
            }

            $ancestors = $node->getNodeAncestors();

            // Exclude nodes with no ancestor
            if (count($ancestors) === 0) {
                continue;
            }

            // Start with a point for the paragraph itself as a base.
            $contentScore = 1;

            // Add points for any commas within this paragraph.
            $contentScore += count(explode(',', $node->getValue(true)));

            // For every 100 characters in this paragraph, add another point. Up to 3 points.
            $contentScore += min(floor(mb_strlen($node->getValue(true)) / 100), 3);

            // Initialize and score ancestors.
            /** @var Readability $ancestor */
            foreach ($ancestors as $level => $ancestor) {
                if (!$ancestor->isInitialized()) {
                    $ancestor->initializeNode();
                    $candidates[] = $ancestor;
                }

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
            }
        }

        /*
         * TODO This is an horrible hack because I don't know how to properly pass by reference.
         * When candidates are added to the $candidates array, they lose the reference to the original object
         * and on each loop, the object inside $candidates doesn't get updated. This function restores the score
         * by getting it of the data-readability tag. This should be fixed using proper references and good coding
         * practices (which I lack)
         */

        foreach ($candidates as $candidate) {
            $candidate->reloadScore();
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
            $kids = $this->backupdom->getElementsByTagName('body')->item(0)->childNodes;

            // Cannot be foreached, don't ask me why.
            for ($i = 0; $i < $kids->length; $i++) {
                $import = $topCandidate->importNode($kids->item($i), true);
                $topCandidate->firstChild->appendChild($import);
            }

            // Readability must be created using firstChild to grab the DOMElement instead of the DOMDocument.
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
        $siblings = $topCandidate->getParent()->getChildren();

        $hasContent = false;

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

                    if (mb_strlen($nodeContent) > 80 && $linkDensity < 0.25) {
                        $append = true;
                    } elseif ($nodeContent && mb_strlen($nodeContent) < 80 && $linkDensity === 0 && preg_match('/\.( |$)/', $nodeContent)) {
                        $append = true;
                    }
                }
            }

            if ($append) {
                $hasContent = true;

                if (!in_array(strtolower($sibling->getTagName()), $this->alterToDIVExceptions)) {
                    /*
                     * We have a node that isn't a common block level element, like a form or td tag.
                     * Turn it into a div so it doesn't get filtered out later by accident.
                     */

                    $sibling->setNodeTag('div');
                }

                $import = $articleContent->importNode($sibling->getDOMNode(), true);
                $articleContent->appendChild($import);

                /*
                 * No node shifting needs to be check because when calling getChildren, an array is made with the
                 * children of the parent node, instead of using the DOMElement childNodes function, which, when used
                 * along with appendChild, would shift the nodes position and the current foreach will behave in
                 * unpredictable ways.
                 */

            }
        }

        $articleContent = $this->prepArticle($articleContent);

        if ($hasContent) {
            return $articleContent;
        } else {
            return false;
        }
    }

    /**
     * TODO To be moved to Readability
     *
     * @param DOMDocument $article
     *
     * @return DOMDocument
     */
    public function prepArticle(DOMDocument $article)
    {
        // Clean out junk from the article content
        $this->_cleanConditionally($article, 'form');
        $this->_clean($article, 'object');
        $this->_clean($article, 'embed');
        $this->_clean($article, 'h1');
        $this->_clean($article, 'footer');

        // If there is only one h2, they are probably using it as a header
        // and not a subheader, so remove it since we already have a header.
        if ($article->getElementsByTagName('h2')->length === 1) {
            $this->_clean($article, 'h2');
        }

        $this->_clean($article, 'iframe');
        $this->_cleanHeaders($article);

        // Do these last as the previous stuff may have removed junk
        // that will affect these
        $this->_cleanConditionally($article, 'table');
        $this->_cleanConditionally($article, 'ul');
        $this->_cleanConditionally($article, 'div');

        $this->_cleanExtraParagraphs($article);

        $this->_cleanReadabilityTags($article);

        // TODO Remove extra BR nodes that have a P sibling.

        return $article;
    }

    /**
     * TODO To be moved to Readability
     *
     * @param DOMDocument $article
     *
     * @return void
     */
    public function _cleanReadabilityTags(DOMDocument $article)
    {
        if ($this->getConfig()->getOption('removeReadabilityTags')) {
            foreach ($article->getElementsByTagName('*') as $tag) {
                if ($tag->hasAttribute('data-readability')) {
                    $tag->removeAttribute('data-readability');
                }
            }
        }
    }

    /**
     * TODO To be moved to Readability
     *
     * @param DOMDocument $article
     *
     * @return void
     */
    public function _cleanExtraParagraphs(DOMDocument $article)
    {
        foreach ($article->getElementsByTagName('p') as $paragraph) {
            $imgCount = $paragraph->getElementsByTagName('img')->length;
            $embedCount = $paragraph->getElementsByTagName('embed')->length;
            $objectCount = $paragraph->getElementsByTagName('object')->length;
            // At this point, nasty iframes have been removed, only remain embedded video ones.
            $iframeCount = $paragraph->getElementsByTagName('iframe')->length;
            $totalCount = $imgCount + $embedCount + $objectCount + $iframeCount;

            if ($totalCount === 0 && !trim($paragraph->textContent)) {
                // TODO must be done via readability
                $paragraph->parentNode->removeChild($paragraph);
            }

        }
    }

    /**
     * TODO To be moved to Readability
     *
     * @param DOMDocument $article
     *
     * @return void
     */
    public function _cleanConditionally(DOMDocument $article, $tag)
    {
        if (!$this->getConfig()->getOption('cleanConditionally')) {
            return;
        }

        $isList = in_array($tag, ['ul', 'ol']);

        /*
         * Gather counts for other typical elements embedded within.
         * Traverse backwards so we can remove nodes at the same time
         * without effecting the traversal.
         */

        $DOMNodeList = $article->getElementsByTagName($tag);
        $length = $DOMNodeList->length;
        for ($i = 0; $i < $length; $i++) {

            $node = $DOMNodeList->item($length - 1 - $i);

            $node = new Readability($node);
            $weight = $node->getClassWeight();

            if ($weight < 0) {
                $this->removeNode($node->getDOMNode());
                continue;
            }

            if (substr_count($node->getTextContent(), ',') < 10) {
                /*
                 * If there are not very many commas, and the number of
                 * non-paragraph elements is more than paragraphs or other
                 * ominous signs, remove the element.
                 */

                // TODO Horrible hack, must be removed once this function is inside Readability
                $p = $node->getDOMNode()->getElementsByTagName('p')->length;
                $img = $node->getDOMNode()->getElementsByTagName('img')->length;
                $li = $node->getDOMNode()->getElementsByTagName('li')->length - 100;
                $input = $node->getDOMNode()->getElementsByTagName('input')->length;

                $embedCount = 0;
                $embeds = $node->getDOMNode()->getElementsByTagName('embed');

                foreach ($embeds as $embedNode) {
                    if (preg_match($this->regexps['videos'], $embedNode->C14N())) {
                        $embedCount++;
                    }
                }

                $linkDensity = $this->getLinkDensity($node);
                $contentLength = mb_strlen($node->getTextContent(true));

                $haveToRemove =
                    // Make an exception for elements with no p's and exactly 1 img.
                    ($img > $p && $node->hasAncestorTag($node, 'figure')) ||
                    (!$isList && $li > $p) ||
                    ($input > floor($p / 3)) ||
                    (!$isList && $contentLength < 25 && ($img === 0 || $img > 2)) ||
                    (!$isList && $weight < 25 && $linkDensity > 0.2) ||
                    ($weight >= 25 && $linkDensity > 0.5) ||
                    (($embedCount === 1 && $contentLength < 75) || $embedCount > 1);

                if ($haveToRemove) {
                    $this->removeNode($node->getDOMNode());
                }
            }
        }

    }

    /**
     * Clean a node of all elements of type "tag".
     * (Unless it's a youtube/vimeo video. People love movies.)
     *
     * TODO To be moved to Readability
     *
     * @param Element
     * @param string tag to clean
     * @return void
     **/
    public function _clean(DOMDocument $article, $tag)
    {
        $isEmbed = in_array($tag, ['object', 'embed', 'iframe']);

        foreach ($article->getElementsByTagName($tag) as $item) {
            // Allow youtube and vimeo videos through as people usually want to see those.
            if ($isEmbed) {
                $attributeValues = [];
                foreach ($item->attributes as $name => $value) {
                    $attributeValues[] = $value->nodeValue;
                }
                $attributeValues = implode('|', $attributeValues);

                // First, check the elements attributes to see if any of them contain youtube or vimeo
                if (preg_match($this->regexps['videos'], $attributeValues)) {
                    continue;
                }

                // Then check the elements inside this element for the same.
                if (preg_match($this->regexps['videos'], $item->C14N())) {
                    continue;
                }
            }
            $this->removeNode($item);
        }
    }

    /**
     * Clean out spurious headers from an Element. Checks things like classnames and link density.
     *
     * TODO To be moved to Readability
     *
     * @param DOMDocument $article
     * @return void
     **/
    public function _cleanHeaders(DOMDocument $article)
    {
        for ($headerIndex = 1; $headerIndex < 3; $headerIndex++) {
            $headers = $article->getElementsByTagName('h' . $headerIndex);
            foreach ($headers as $header) {
                $header = new Readability($header);
                if ($header->getClassWeight() < 0) {
                    $this->removeNode($header->getDOMNode());
                }
            }
        }
    }

    /**
     * Remove the passed node
     *
     * TODO To be moved to Readability
     *
     * @param \DOMNode $node
     * @return void
     **/
    public function removeNode(\DOMNode $node)
    {
        $parent = $node->parentNode;
        if ($parent) {
            $parent->removeChild($node);
        }
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

            return (mb_strlen($byline) > 0) && (mb_strlen($text) < 100);
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
        // And there should be no text nodes with real content (param true on ->getChildren)
        if (count($children = $node->getChildren(true)) !== 1 || !$children[0]->tagNameEqualsTo('p')) {
            return false;
        }

        return true;
    }

    private function hasSingleChildBlockElement(Readability $node)
    {
        $result = false;
        if ($node->hasChildren()) {
            /** @var Readability $child */
            foreach ($node->getChildren() as $child) {
                if (in_array($child->getTagName(), $this->divToPElements)) {
                    $result = true;
                } else {
                    // If any of the hasSingleChildBlockElement calls return true, return true then.
                    $result = ($result || $this->hasSingleChildBlockElement($child));
                }
            }
        }

        return $result;
    }
}
