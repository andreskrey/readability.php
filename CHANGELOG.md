# Change Log
All notable changes to this project will be documented in this file.

## Unreleased

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
