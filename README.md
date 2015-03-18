# Intelligent 404 Redirector for SilverStripe 3
An extension to add additional functionality to the existing 404 ErrorPage.
If a 404 page is called, it tries to guess the intended page by matching up the
last segments of the url to all SiteTree pages. It also uses soundex to match similar
sounding pages to find toher alternatives.

## How it works
If a 404 error is detected (**note:** does not work in dev mode!):

1. It will search SiteTree for all matching URLSegments, as well as any that sound the same
(using PHP's [soundex()](http://php.net/manual/en/function.soundex.php)).
2. Else if 1 **exact** match is found, a 301 redirect is sent.
3. Else if **no exact** match is found, and 1 **similar** page is found, a 301 redirect is sent
to the similar page.
4. Else if more than 1 exact or similar page is found, a regular 404 page is shown and the list of
possible options is shown (ie: "Were you looking for one of the following pages?") directly beneath it.
5. Else a regular 404 page is shown.

## Requirements
* SilverStripe 3+

## Usage
Copy the intelligent-404 directory to the root folder of your website installation. Please note
that this will only work if you website is **not in development mode** (ie: for testing and detecting actual 404 pages).

In your `mysite/_config/config.yml` you can optionally define different ignored ClassNames:
```
ErrorPage:
  intelligent_404_ignored_classes:
    - ErrorPage
    - RedirectorPage
    - VirtualPage
```
