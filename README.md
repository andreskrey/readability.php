# Readability.php
[![Latest Stable Version](https://poser.pugx.org/andreskrey/readability.php/v/stable)](https://packagist.org/packages/andreskrey/readability.php) [![StyleCI](https://styleci.io/repos/71042668/shield?branch=master)](https://styleci.io/repos/71042668) [![Build Status](https://travis-ci.org/andreskrey/readability.php.svg?branch=master)](https://travis-ci.org/andreskrey/readability.php)

PHP port of *Mozilla's* **[Readability.js](https://github.com/mozilla/readability)**. Parses html text (usually news and other articles) and tries to return title, byline and text content without nav bars, ads, footers, or anything that isn't the main body of the text. Analizes each text node, gives an score and orders them based on this calculation.

**Requires**: PHP 5.4+ & DOMDocument (libxml)

**Lead Developer**: Andres Rey

## Status

Current status is stable. Not _suuuuper_ stable but stable enough.

## How to use it

First you have to require the library using composer:

`composer require andreskrey/readability.php`

Then, create and HTMLParser object with your preferences, feed the `parse()` function with your HTML and check the resulting array:

```php 
use andreskrey\Readability\HTMLParser;

$readability = new HTMLParser();

$html = file_get_contents('http://your.favorite.newspaper/article.html');

$result = $readability->parse($html);
```

The `$result` variable now will hold the following information:

```
$result = [
    'title' => 'Title of the article',
    'author' => 'Name of the author of the article',
    'image' => 'Main image of the article',
    'article' => 'DOMDocument with the full article text, scored and parsed'
]
```

If the parsing process was unsuccessful the HTMLParser will return `false`

## Options

- **maxTopCandidates**: default value `5`, max amount of top level candidates.
- **articleByLine**: default value `false`, search for the article byline. 
- **stripUnlikelyCandidates**: default value `true`, remove nodes that are unlikely to have relevant information. Useful for debugging or parsing complex or non-standard articles. 
- **cleanConditionally**: default value `true`, remove certain nodes after parsing to return a cleaner result. 
- **weightClasses**: default value `true`, weight classes during the rating phase. 
- **removeReadabilityTags**: default value `true`, remove the data-readability tags inside the nodes that are added during the rating phase. 
- **fixRelativeURLs**: default value `false`, convert relative URLs to absolute. Like `/test` to `http://host/test`. 
- **substituteEntities**: default value `false`, disables the `substituteEntities` flag of libxml. Will avoid substituting HTML entities. Like `&aacute;` to á.
- **normalizeEntities**: default value `false`, converts UTF-8 characters to its HTML Entity equivalent. Useful to parse HTML with mixed encoding.
- **originalURL**: default value `http://fakehost`, original URL from the article used to fix relative URLs.
- **summonCthulhu**: default value `false`, remove all `<script>` nodes via regex. This is not ideal as it might break things, but might be the only solution to [libxml problems with unescaped javascript](https://github.com/andreskrey/readability.php#known-issues).

## Limitations

Of course the main limitation is PHP. Websites that load the content through lazy loading, AJAX, or any type of javascript fueled call will be ignored (actually, *not ran*) and the resulting text will be incorrect, compared to the readability.js results. All the articles you want to parse with readability.php will need to be complete and all the content should be in the HTML already.  

## Known Issues

DOMDocument has some issues while parsing javascript with unescaped HTML on strings. Consider the following code:

```html
<div> <!-- Offending div without closing tag -->
<script type="text/javascript">
       var test = '</div>';
       // I should not appear on the result
</script>
```

If you would like to remove the scripts of the HTML (like readability does), you would expect ending up with just one div and one comment on the final HTML. The problem is that libxml takes that closing div tag inside the javascript string as a HTML tag, effectively closing the unclosed tag and leaving the rest of the javascript as a string withing a P tag. If you save that node, the final HTML will end up like this:

```html
<div> <!-- Offending div without closing tag -->
<p>';
       // I should not appear on the result
</p></div>
```

This is a libxml issue and not a Readability.php bug.

There's a workaround for this: using the summonCthulhu option. This will remove all script tags via regex, which is not ideal because you may end up summoning [the lord of darkness](https://stackoverflow.com/a/1732454).

## Dependencies

Readability uses the Element interface and class from *The PHP League's* **[html-to-markdown](https://github.com/thephpleague/html-to-markdown/)**. The Readability object is an extension of the Element class. It overrides some methods but relies on it for basic DOMElement parsing.

## To-do

- Right now the Readability object is an extension of the Element object of html-to-markdown. This is a problem because you lose context. The scoring when creating a new Readability object must be reloaded manually. The DOMDocument object is consistent across the same document. You change one value here and that will update all other nodes in other variables. By using the element interface you lose that reference and the score must be restored manually. Ideally, the Readability object should be an extension of the DOMDocument or DOMElement objects, the score should be saved within that object and no restoration or recalculation would be needed.
- There are a lot of problems with responsabilities. Right now there are two classes: HTMLParser and Readability. HTMLParser does a lot of things that should be a responsibility of Readability. It also does a lot of things that should be part of another class, specially when building the final article DOMDocument.

## How it works

Readability parses all the text with DOMDocument, scans the text nodes and gives the a score, based on the amount of words, links and type of element. Then it selects the highest scoring element and creates a new DOMDocument with all its siblings. Each sibling is scored to discard useless elements, like nav bars, empty nodes, etc.

## Code porting

Current version follows the latest version of readability.js as of [05 May 2017](https://github.com/mozilla/readability/commit/f0edc77cb58ef52890e3065cf2b0e334d940feb2).
 
### TO-DOs of the current port:

 - Port `_cleanStyles` to avoid style attributes inside other tags (like `<p style="hello   ">`) 