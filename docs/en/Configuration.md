# Configuration

By default Intelligent-404 will just try match 404 pages with *all pages* in your SiteTree, except for pages with the classnames:

- `SilverStripe\CMS\Model\ErrorPage`
- `SilverStripe\CMS\Model\RedirectorPage`
- `SilverStripe\CMS\Model\VirtualPage`

This means that any page types you add should autmatically be included, and can optionally be excluded as well.

## Generic options
By default Intelligent-404 will not work in dev mode (to help you spot issues). You can turn this on by setting `allow_in_dev_mode: true`.

By default the module will also redirect is either 1 exact match or one potential match is found (eg: the page has been recreated elsewhere).
You can change this by setting `redirect_on_single_match: false`.

```yml
Axllent\Intelligent404\Intelligent404:
  allow_in_dev_mode: true           # allow this to work in dev mode (default false)
  redirect_on_single_match: false   # do not auto-redirect if one exact match is found (default true)
```

## Adding other DataObjects

It is possible to add other DataObjects to the Intelligent-404 matching (such as a product database), provided that the DataObject has a `Link()` function. To add your own DataObjects, create a yml config (eg: `mysite/_config/intelligent404.yml`) with the following syntax:

```yml
Axllent\Intelligent404\Intelligent404:
  data_objects:
    \Product:                   # must include the trailing \ for namespacing
      group: Products           # optional group to include the results list into (default 'Pages')
      filter:                   # optionally filter which results to include (see below)
        Stock:GreaterThan: 0
      exclude:                  # optional filter out results (see below)
        Expired: 1
```

## Notes:

### - Namespacing

Due to namespacing in SilverStripe 4, `data_objects` listed in your yaml files should include a training backslash `\`,
so if your DataObject is `Product`, it should be references in your yaml config as `\Product`.

### Group (`group:`)

By default, all results will get passed on to your template as results of a `$Pages` ArrayList. You can optionally split your
suggestion results (for instance for different ordering or templating) by adding a `grouping: <groupname>`
as in the example above. Then in your template you would do a `<% loop $Products %>` in addition your your `$Pages`.

If you add your own grouping, please be sure to create your own `templates/Intelligent404Options.ss` to allow for those.

### Filter results (`filter:`)

You are able to filter the database field matches, for instance (in the example) products that have a `Stock` greater than `0`.

Please refer to the [Searchfilters documentation](https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/) for more information.

### Exclude results (`exclude:`)

You can also exclude database field matches, for instance (in the example) products that have a `Expired` of `1`.
