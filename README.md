# Intelligent 404 Redirector for SilverStripe
An extension to add additional functionality to the existing 404 ErrorPage.
If a 404 page is called, it tries to guess the intended page by matching up the
last segment of the url to all SiteTree pages. It also uses soundex to match similar
sounding pages to find other alternatives.

Other DataObjects (eg: products) can be added too provided they contain a `Link()` function

## How it works
If a 404 error is detected (**note:** does not work in `dev` mode by default):

1. It will search SiteTree for all matching URLSegments, as well as any that sound the same
(using PHP's [soundex()](http://php.net/manual/en/function.soundex.php)).
2. If **1 exact** match is found, a 301 redirect is sent.
3. Else if **no exact** match is found, and **1 similar** page is found, a 301 redirect is sent
to the similar page.
4. Else if more than 1 exact or similar page is found, a regular 404 page is shown and the list of
possible options is shown (ie: "Were you looking for one of the following pages?") directly beneath it.
5. Else a regular 404 page is shown.

## Requirements
* SilverStripe 4

For SilverStripe 3, please refer to the [SilverStripe3 branch](https://github.com/axllent/silverstripe-intelligent-404/tree/silverstripe3).

## Installation
```bash
composer require axllent/silverstripe-intelligent-404
```

## Usage
Please see [Configuration.md](docs/en/Configuration.md) for configuration options and documentation.
