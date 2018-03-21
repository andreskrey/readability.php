# Change Log
All notable changes to this project will be documented in this file.

## Unreleased

## [v1.2.0](https://github.com/andreskrey/readability.php/releases/tag/v1.2.0)

- Merged PR#49 (Missing object when calling `->getContent()`)
- Imported all changes from Readability.js as of 2 March 2018 ([8525c6a](https://github.com/mozilla/readability/commit/8525c6af36d3badbe27c4672a6f2dd99ddb4097f)):
    - Check for `<base>` elements before converting URLs to absolute.
    - Clean `<link>` tags on `prepArticle()`
    - Attempt to return at least some text if all the algorithm runs fail (Check PR [#423](https://github.com/mozilla/readability/pull/423) on JS version)
    - Add new test cases for the previous changes
    - And all other changes reflected [in this diff](https://github.com/mozilla/readability/compare/c3ff1a2d2c94c1db257b2c9aa88a4b8fbeb221c5...8525c6af36d3badbe27c4672a6f2dd99ddb4097f)

## [v1.1.1](https://github.com/andreskrey/readability.php/releases/tag/v1.1.1)

- Switched from assertEquals to assertSame on unit testing to avoid weak comparisons. 
- Added a safe check to avoid sending the DOMDocument as a node when scanning for node ancestors.
- Fix issue #45: Small mistake in documentation
- Fix issue #46: Added `data-src` as a image source path
- Fixed bug when extracting all the image of the article (Was extracting images from the original DOM instead of the parsed one)
- Added the `->getDOMDocument()` getter to retrieve the fully parsed DOMDocument
- Merged PR #48 that allows passing an array as configuration (@topotru)

## [v1.1.0](https://github.com/andreskrey/readability.php/releases/tag/v1.1.0)

- Added 'data-orig' as an URL source for images
- Removed 'modal' as a negative property from classes
- Added option to inject a logger
- Removed all references to the `data-readability` tags that don't apply anymore to the new structure
- Merged PR #38 (Missing DOMEntityReference)

## [v1.0.0](https://github.com/andreskrey/readability.php/releases/tag/v1.0.0)

- Node encapsulation is gone. Pre v1 all nodes where encapsulated in a Readability class, which created lots of trouble with dependencies, responsibilities, and properties. Now all the encapsulation is gone: all the DOMNodes inside the Readability class are extensions of the original DOM classes, which allows the system to take advantage of the functions and properties of DOMDocument.
- HTMLParser is gone, Readability is the new main class. Switched things a bit for this release. Pre v1 you had to create an HTMLParser class to parse the HTML. Now you have to create a Readability class, feed it the text, and check the result.
- No more dumb arrays as a result. If you want to get the title, content, images, or anything else you'll have to use the getters of the Readability class.
- Environment class is gone. Now you have to create a configuration class and use setters to set your configuration options.
- Exceptions. Make sure you wrap your Readability class in a try catch block, because if it fails to parse your HTML, it will throw a `ParseException`.
- Minimum PHP version bumped to 5.6.

## [v0.3.1](https://github.com/andreskrey/readability.php/releases/tag/v0.3.1)

- Trim titles when detecting hierarchical separators to avoid false negatives on strings with spaces.
- Fix issue when converting divs to p nodes and never rating them (issue #29)
- Fix "Unsupported operand types" (PR #31) 
- Fix division by zero when no title was found (issue #32)
- New function to retrieve all images at once (PR #30)
- Get the title from the `<title>` tag before searching on the `<meta>` tags

## [v0.3.0](https://github.com/andreskrey/readability.php/releases/tag/v0.3.0)

- Merged PR #24. Fixes notice when trying to extract `og:image`
- Up to date to commit [eb221c5](https://github.com/mozilla/readability/commit/c3ff1a2d2c94c1db257b2c9aa88a4b8fbeb221c5) (2017-10-16), which includes the following changes:
  - New tags added to the unlikelyCandidates regex
  - Detection and removal of hierarchical separators in titles
  - Added more tags to clean after parsing the article (`button`, `textarea`, `select`, etc.)
  - New way to detect empty nodes (including a edge case where a node with a `&nsbp;` was detected as a node with content)
  - Better approach to find a top candidate (specially when a top candidate is the only child of a parent node, which allows a more accurate joining of sibling elements)
  - Detect text direction (`ltr` or `rtl`)
  - Detect and mark data tables to avoid removing them during final clean up
  - Major fixes when scanning and deleting nodes (no need to traverse backwards anymore)
  - Node cleaning via regex matches
  - Clean table attributes during final clean up.
- Added license

Next release after this one will be v1 and will be a major refactor around Readability and HTMLParser methods and responsibilities.

## [v0.2.2](https://github.com/andreskrey/readability.php/releases/tag/v0.2.2)

- Added a safecheck for really nasty HTML
- Added summonCthulhu option, to remove all script tags via regex

## [v0.2.1](https://github.com/andreskrey/readability.php/releases/tag/v0.2.1)

- Added `normalizeEntities` flag to convert UTF-8 characters to its HTML Entity equivalent. Fixes bugs on htmls with mixed encoding.
- Added more information to the readme.md file
- New way to create a backup DOM: not creating a backup. In the previous version, the system cloned the $this->dom object to keep it as a backup in order to restart the algorithm with other flags, if needed. This seemed to work until I realized that *sometimes* the backup changes even if we are not touching it. Seems that the `dom` and `backupdom` objects are linked and *some* changes on the dom object reach the bakcupdom object. The new approach consists in deleting the backupdom object and recreating from scratch the dom object. Of course this has a performance impact, but seems to be quite low.

## [v0.2.0](https://github.com/andreskrey/readability.php/releases/tag/v0.2.0)

100% complete port of Readability.js!
- Every test unit passes
- Readability.php produces the same exact output as Readability.js
- I'm happy :)

### Fixed
- Lots of bugs
- Merged PR by DavidFricker to avoid exceptions while grabbing the document content

### Added
- substituteEntities flag, to avoid replacing especial characters with HTML entities. There's nothing we can do about `&nbsp;`, that entity is replaced by libxml and there's no way to disable it.
- Named data sets so it's easier to detect which test case is failing.

### Removed

- Couple of test cases that involved broken JS. There's nothing we can do about JS spilling onto the text.

## [0.0.3-alpha](https://github.com/andreskrey/readability.php/releases/tag/v0.0.3v-alpha)

We are getting closer to be a 100% complete port of Readability.js!
- Added prepArticle to remove junk after selecting the top candidates.
- Added a function to restore score after selecting top candidates. This basically works by scanning the data-readability tag and restoring the score to the contentScore variable. This is an horrible hack and should be removed once we ditch the Element interface of html-to-markdown and start extending the DOMDocument object.
- Switched all strlen functions to mb_strlen
- Fixed lots of bugs and pretty sure that introduced a bunch of new ones.

## [0.0.2-alpha](https://github.com/andreskrey/readability.php/releases/tag/v0.0.2-alpha)
 - Last version I'm using master as the main development branch. All unreleased changes and main development will happen in the develop branch.
 
## [0.0.1-alpha](https://github.com/andreskrey/readability.php/releases/tag/v0.0.1-alpha)
 - Initial release
