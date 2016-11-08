# Readability.php
[![Latest Stable Version](https://poser.pugx.org/andreskrey/readability.php/v/stable)](https://packagist.org/packages/andreskrey/readability.php)

PHP port of *Mozilla's* **[Readability.js](https://github.com/mozilla/readability)**. Parses html text (usually news and other articles) and tries to return title, byline and text content. Analizes each text node, gives an score and orders them based on this calculation.

**Requires**: PHP 5.3+

**Lead Developer**: Andres Rey

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
    'article' => 'DOMDocument with the full article text, scored and parsed'
]
```

## Options

## Limitations

## Known Issues

## To-do

## Dependencies

Readability uses the Element interface and class from *The PHP League's* **[html-to-markdown](https://github.com/thephpleague/html-to-markdown/)**. The Readability object is an extension of the Element class. It overrides some methods but relies on it for basic DOMElement parsing.
