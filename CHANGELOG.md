# Change Log
All notable changes to this project will be documented in this file.

## Unreleased

## [v0.2.1](https://github.com/andreskrey/readability.php/releases/tag/v0.2.1)

- Added `normalizeEntities` flag to convert UTF-8 characters to its HTML Entity equivalent. Fixes bugs on htmls with mixed encoding.
- Added more information to the readme.md file
- New way to create a backup DOM: not creating a backup. In the previous version, the system cloned the $this->dom object to keep it as a backup in order to restart the algorithm with other flags, if needed. This seemed to work until I realized that *sometimes* the backup changes even if we are not touching it. Seems that the `dom` and `backupdom` objects are linked and *some* changes on the dom object reach the bakcupdom object. The new approach consists in deleting the backupdom object and recreating from scratch the dom object. Of course this has a performance impact, but seems to be quite low.

## [v0.2.0](https://github.com/andreskrey/readability.php/releases/tag/v0.2.0)

We ARE a 100% complete port of Readability.js!
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
