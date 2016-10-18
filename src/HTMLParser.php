<?php

namespace andreskrey\Readability;

use DOMDocument;

class HTMLParser
{

    private $dom = null;

    private $metadata = [];
    private $title = [];
    private $elementsToScore = [];
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
        'hasContent' => '/\S$/'
    ];

    public function __construct()
    {
        $this->dom = new DOMDocument('1.0', 'utf-8');
        libxml_use_internal_errors(true);
    }

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

    private function loadHTML($html)
    {
        $this->dom->loadHTML($html);
        $this->dom->encoding = 'utf-8';
    }

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

    private function getMetadata()
    {
        $metadata = [];
        foreach ($this->dom->getElementsByTagName('meta') as $meta) {
            $name = $meta->getAttribute('name');
            $property = $meta->getAttribute('property');

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

    private function getNodes(DOMElementInterface $node)
    {
        $matchString = $node->getAttribute('class') . ' ' . $node->getAttribute('id');

        if (
            preg_match($this->regexps['unlikelyCandidates'], $matchString) &&
            !preg_match($this->regexps['okMaybeItsACandidate'], $matchString) &&
            !$node->tagNameEqualsTo('body') &&
            !$node->tagNameEqualsTo('a')
        ) {
            return;
        }

        if ($node->hasChildren()) {
            foreach ($node->getChildren() as $child) {
                $this->getNodes($child);
            }
        }

        if ($node->hasSinglePNode()) {
            $pNode = $node->getChildren();
            $node = $pNode[0];
        }

        if (trim($node->getValue())) {
            $this->elementsToScore[] = $node;
        }
    }

    /**
     * @param array $nodes
     */
    private function rateNodes($nodes)
    {
        $candidates = [];

        foreach ($nodes as $node) {
            if (strlen($node->getValue()) < 25) {
                continue;
            }

            $ancestors = $node->getNodeAncestors();
            if ($ancestors < 3) {
                continue;
            }

            // Start with a point for the paragraph itself as a base.
            $contentScore = 1;

            // Add points for any commas within this paragraph.
            $contentScore += count(explode(', ', $node->getValue()));

            // For every 100 characters in this paragraph, add another point. Up to 3 points.
            $contentScore += min(floor(strlen($node->getValue()) / 100), 3);

            foreach ($ancestors as $ancestor) {
                $readability = new Readability($ancestor);
                $candidates[] = $readability->initializeNode();
            }

        }
    }
}
