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
use andreskrey\Readability\NodeClass\DOMNode;
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
     * @var array
     */
    private $regexps = [
        'unlikelyCandidates' => '/banner|breadcrumbs|combx|comment|community|cover-wrap|disqus|extra|foot|header|legends|menu|modal|related|remark|replies|rss|shoutbox|sidebar|skyscraper|social|sponsor|supplemental|ad-break|agegate|pagination|pager|popup|yom-remote/i',
        'okMaybeItsACandidate' => '/and|article|body|column|main|shadow/i',
        'extraneous' => '/print|archive|comment|discuss|e[\-]?mail|share|reply|all|login|sign|single|utility/i',
        'byline' => '/byline|author|dateline|writtenby|p-author/i',
        'replaceFonts' => '/<(\/?)font[^>]*>/gi',
        'normalize' => '/\s{2,}/',
        'videos' => '/\/\/(www\.)?(dailymotion|youtube|youtube-nocookie|player\.vimeo)\.com/i',
        'nextLink' => '/(next|weiter|continue|>([^\|]|$)|»([^\|]|$))/i',
        'prevLink' => '/(prev|earl|old|new|<|«)/i',
        'whitespace' => '/^\s*$/',
        'hasContent' => '/\S$/',
        // \x{00A0} is the unicode version of &nbsp;
        'onlyWhitespace' => '/\x{00A0}|\s+/u'
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


    private $dom;

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
        $this->dom = $this->loadHTML($html);

        $this->metadata = $this->getMetadata();

        $this->metadata['image'] = $this->getMainImage();

        // Checking for minimum HTML to work with.
        if (!($root = $this->dom->getElementsByTagName('body')->item(0)) || !$root->firstChild) {
            return false;
        }

        $parseSuccessful = true;
        while (true) {
            $root = $root->firstChild;

            $elementsToScore = $this->getNodes($root);

            $result = $this->rateNodes($elementsToScore);

            /*
             * Now that we've gone through the full algorithm, check to see if
             * we got any meaningful content. If we didn't, we may need to re-run
             * grabArticle with different flags set. This gives us a higher likelihood of
             * finding the content, and the sieve approach gives us a higher likelihood of
             * finding the -right- content.
             */

            // TODO Better way to count resulting text. Textcontent usually has alt titles and that stuff
            // that doesn't really count to the quality of the result.
            $length = 0;
            foreach ($result->getElementsByTagName('p') as $p) {
                $length += mb_strlen($p->textContent);
            }
            if ($result && mb_strlen(preg_replace('/\s/', '', $result->textContent)) < $this->getConfig()->getOption('wordThreshold')) {
                $this->dom = $this->loadHTML($html);
                $root = $this->dom->getElementsByTagName('body')->item(0);

                if ($this->getConfig()->getOption('stripUnlikelyCandidates')) {
                    $this->getConfig()->setOption('stripUnlikelyCandidates', false);
                } elseif ($this->getConfig()->getOption('weightClasses')) {
                    $this->getConfig()->setOption('weightClasses', false);
                } elseif ($this->getConfig()->getOption('cleanConditionally')) {
                    $this->getConfig()->setOption('cleanConditionally', false);
                } else {
                    $parseSuccessful = false;
                    break;
                }
            } else {
                break;
            }
        }

        if (!$parseSuccessful) {
            return false;
        }

        $result = $this->postProcessContent($result);

        // Todo, fix return, check for values, maybe create a function to create the return object
        return [
            'title' => isset($this->metadata['title']) ? $this->metadata['title'] : null,
            'author' => isset($this->metadata['author']) ? $this->metadata['author'] : null,
            'image' => isset($this->metadata['image']) ? $this->metadata['image'] : null,
            'images' => $this->getImages(),
            'article' => $result,
            'html' => $result->C14N(),
            'dir' => isset($this->metadata['articleDir']) ? $this->metadata['articleDir'] : null,
        ];
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
        $dom->registerNodeClass('DOMNode', DOMNode::class);
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
     * Tries to guess relevant info from metadata of the html.
     *
     * @return array Metadata info. May have title, excerpt and or byline.
     */
    private function getMetadata()
    {
        $metadata = $values = [];
        // Match "description", or Twitter's "twitter:description" (Cards)
        // in name attribute.
        $namePattern = '/^\s*((twitter)\s*:\s*)?(description|title|image)\s*$/i';

        // Match Facebook's Open Graph title & description properties.
        $propertyPattern = '/^\s*og\s*:\s*(description|title|image)\s*$/i';

        foreach ($this->dom->getElementsByTagName('meta') as $meta) {
            /* @var DOMNode $meta */
            $elementName = $meta->getAttribute('name');
            $elementProperty = $meta->getAttribute('property');

            if (in_array('author', [$elementName, $elementProperty])) {
                $metadata['byline'] = $meta->getAttribute('content');
                continue;
            }

            $name = null;
            if (preg_match($namePattern, $elementName)) {
                $name = $elementName;
            } elseif (preg_match($propertyPattern, $elementProperty)) {
                $name = $elementProperty;
            }

            if ($name) {
                $content = $meta->getAttribute('content');
                if ($content) {
                    // Convert to lowercase and remove any whitespace
                    // so we can match below.
                    $name = preg_replace('/\s/', '', strtolower($name));
                    $values[$name] = trim($content);
                }
            }
        }
        if (array_key_exists('description', $values)) {
            $metadata['excerpt'] = $values['description'];
        } elseif (array_key_exists('og:description', $values)) {
            // Use facebook open graph description.
            $metadata['excerpt'] = $values['og:description'];
        } elseif (array_key_exists('twitter:description', $values)) {
            // Use twitter cards description.
            $metadata['excerpt'] = $values['twitter:description'];
        }

        $metadata['title'] = $this->getTitle();

        if (!$metadata['title']) {
            if (array_key_exists('og:title', $values)) {
                // Use facebook open graph title.
                $metadata['title'] = $values['og:title'];
            } elseif (array_key_exists('twitter:title', $values)) {
                // Use twitter cards title.
                $metadata['title'] = $values['twitter:title'];
            }
        }

        if (array_key_exists('og:image', $values) || array_key_exists('twitter:image', $values)) {
            $metadata['image'] = array_key_exists('og:image', $values) ? $values['og:image'] : $values['twitter:image'];
        } else {
            $metadata['image'] = null;
        }

        return $metadata;
    }

    /**
     * Tries to get the main article image. Will only update the metadata if the getMetadata function couldn't
     * find a correct image.
     *
     * @return bool|string URL of the top image or false if unsuccessful.
     */
    public function getMainImage()
    {
        $imgUrl = false;

        if ($this->metadata['image'] !== null) {
            $imgUrl = $this->metadata['image'];
        }

        if (!$imgUrl) {
            foreach ($this->dom->getElementsByTagName('link') as $link) {
                /** @var \DOMElement $link */
                /*
                 * Check for the rel attribute, then check if the rel attribute is either img_src or image_src, and
                 * finally check for the existence of the href attribute, which should hold the image url.
                 */
                if ($link->hasAttribute('rel') && ($link->getAttribute('rel') === 'img_src' || $link->getAttribute('rel') === 'image_src') && $link->hasAttribute('href')) {
                    $imgUrl = $link->getAttribute('href');
                    break;
                }
            }
        }

        if (!empty($imgUrl) && $this->configuration->getFixRelativeURLs()) {
            $imgUrl = $this->toAbsoluteURI($imgUrl);
        }

        return $imgUrl;
    }

    private function toAbsoluteURI($uri)
    {
        list($pathBase, $scheme, $prePath) = $this->getPathInfo($this->configuration->getOriginalURL());

        // If this is already an absolute URI, return it.
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9\+\-\.]*:/', $uri)) {
            return $uri;
        }

        // Scheme-rooted relative URI.
        if (substr($uri, 0, 2) === '//') {
            return $scheme . '://' . substr($uri, 2);
        }

        // Prepath-rooted relative URI.
        if (substr($uri, 0, 1) === '/') {
            return $prePath . $uri;
        }

        // Dotslash relative URI.
        if (strpos($uri, './') === 0) {
            return $pathBase . substr($uri, 2);
        }
        // Ignore hash URIs:
        if (substr($uri, 0, 1) === '#') {
            return $uri;
        }

        // Standard relative URI; add entire path. pathBase already includes a
        // trailing "/".
        return $pathBase . $uri;
    }

    /**
     * @param  string $url
     *
     * @return array  [$pathBase, $scheme, $prePath]
     */
    public function getPathInfo($url)
    {
        $pathBase = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . dirname(parse_url($url, PHP_URL_PATH)) . '/';
        $scheme = parse_url($pathBase, PHP_URL_SCHEME);
        $prePath = $scheme . '://' . parse_url($pathBase, PHP_URL_HOST);

        return [$pathBase, $scheme, $prePath];
    }


    /**
     * Gets nodes from the root element.
     *
     * @param $node DOMNode|DOMText
     *
     * @return array
     */
    private function getNodes($node)
    {
        $stripUnlikelyCandidates = $this->configuration->getStripUnlikelyCandidates();

        $elementsToScore = [];

        /*
         * First, node prepping. Trash nodes that look cruddy (like ones with the
         * class name "comment", etc), and turn divs into P tags where they have been
         * used inappropriately (as in, where they contain no other block level elements.)
         */

        while ($node) {
            $matchString = $node->getAttribute('class') . ' ' . $node->getAttribute('id');

            // Remove DOMComments nodes as we don't need them and mess up children counting
            if ($node->nodeTypeEqualsTo(XML_COMMENT_NODE)) {
                $node = NodeUtility::removeAndGetNext($node);
                continue;
            }

            // Check to see if this node is a byline, and remove it if it is.
            if ($this->checkByline($node, $matchString)) {
                $node = NodeUtility::removeAndGetNext($node);
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
                    $node = NodeUtility::removeAndGetNext($node);
                    continue;
                }
            }

            // Remove DIV, SECTION, and HEADER nodes without any content(e.g. text, image, video, or iframe).
            if (($node->tagNameEqualsTo('div') || $node->tagNameEqualsTo('section') || $node->tagNameEqualsTo('header') ||
                    $node->tagNameEqualsTo('h1') || $node->tagNameEqualsTo('h2') || $node->tagNameEqualsTo('h3') ||
                    $node->tagNameEqualsTo('h4') || $node->tagNameEqualsTo('h5') || $node->tagNameEqualsTo('h6') ||
                    $node->tagNameEqualsTo('p')) &&
                $node->isElementWithoutContent()) {
                $node = NodeUtility::removeAndGetNext($node);
                continue;
            }

            if (in_array(strtolower($node->nodeName), $this->defaultTagsToScore)) {
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
                if (NodeUtility::hasSinglePNode($node)) {
                    $pNode = $node->getChildren(true)[0];
                    $node->replaceChild($pNode, $node);
                    $node = $pNode;
                    $elementsToScore[] = $node;
                } elseif (!NodeUtility::hasSingleChildBlockElement($node)) {
                    NodeUtility::setNodeTag($node, 'p');
                    $elementsToScore[] = $node;
                } else {
                    // EXPERIMENTAL
                    foreach ($node->getChildren() as $child) {
                        /** @var $child DOMNode */
                        if ($child->nodeType === XML_TEXT_NODE && mb_strlen(trim(NodeUtility::getTextContent($child))) > 0) {
                            $newNode = $node->createNode($child, 'p');
                            $child->replaceChild($newNode, $child);
                        }
                    }
                }
            }

            $node = $node->getNextNode($node);
        }

        return $elementsToScore;
    }

    /**
     * Checks if the node is a byline.
     *
     * @param DOMNode $node
     * @param string $matchString
     *
     * @return bool
     */
    private function checkByline($node, $matchString)
    {
        if (!$this->configuration->getArticleByLine()) {
            return false;
        }

        /*
         * Check if the byline is already set
         */
        if (isset($this->metadata['byline'])) {
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