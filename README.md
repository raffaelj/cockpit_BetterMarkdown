# BetterMarkdown addon for Cockpit CMS

**This addon is not compatible with Cockpit CMS v2.**

See also [Cockpit CMS v1 docs](https://v1.getcockpit.com/documentation), [Cockpit CMS v1 repo](https://github.com/agentejo/cockpit) and [Cockpit CMS v2 docs](https://getcockpit.com/documentation/), [Cockpit CMS v2 repo](https://github.com/Cockpit-HQ/Cockpit).

---

Cache, task lists and ToC support for markdown conversion with [Cockpit CMS][1]

This addon replaces the current [Parsedown][2] and [ParsedownExtra][3] libraries with later versions and it adds two Parsedown extensions: [ParsedownToC][4] and [ParsedownCheckbox][5].

It also uses a cache of already converted content to speed things up. Cached files are in the `tmp` folder.

## Installation

Copy this repository into `/addons` and name it `BetterMarkdown` or use the cli.

### via git

```bash
cd path/to/cockpit
git clone https://github.com/raffaelj/cockpit_BetterMarkdown.git addons/BetterMarkdown
```

### via cp cli

```bash
cd path/to/cockpit
./cp install/addon --name BetterMarkdown --url https://github.com/raffaelj/cockpit_BetterMarkdown/archive/master.zip
```

### via composer

Make sure, that the path to cockpit addons is defined in your projects' `composer.json` file.

```json
{
    "name": "my/cockpit-project",
    "extra": {
        "installer-paths": {
            "addons/{$name}": ["type:cockpit-module"]
        }
    }
}
```

```bash
cd path/to/cockpit-root
composer create-project --ignore-platform-reqs aheinze/cockpit .
composer config extra.installer-paths.addons/{\$name} "type:cockpit-module"

composer require --ignore-platform-reqs raffaelj/cockpit-bettermarkdown
```

## Config

`path/to/cockpit/config/config.php`:

```php
<?php
return [
    'app.name' => 'markdown test',

    'bettermarkdown' => [
        'parser' => 'extended', // (string) parsedown|extra|extended - default: extended
        'cache' => false, // cache is still active, but rebuild is forced --> useful for debugging

        // change settings for ParsedownToC
        // see: https://github.com/BenjaminHoegh/parsedownToc#configuration
        'toc' => [ 
            'selectors' => ['h2', 'h3', 'h4', 'h5', 'h6'], // omit h1 from toc

            // blacklist existing ids in your frontend
            'blacklist' => [
                'nav', // turns to 'nav-1'
                'top',
            ],

            // array of regexes for text replacements - before heading ids are generated
            'replacements' => [ 
                '/^old-id$/' => 'new-id',
            ],
        ],

        'cached_toc_format' => 'tree', // (string) flat|tree - default: flat

        // transform the cached toc in tree format with cleaner output
        'tree_toc' => [
            'replace_keys' => [
                'text' => 'title',
            ],
            'unset_keys' => ['level', 'id'],
        ],
    ],
];
```

## Usage

Markdown:

```md
# Markdown Test

[toc]

## Usage

do something

## To do

* [x] test
* [ ] deploy
```

Conversion:

```php
$html = $app->module('cockpit')->markdown($md);
```

html output:

```html
<h1 id="markdown-test">Markdown Test</h1>
<div id="toc"><ul>
<li><a href="#usage">Usage</a></li>
<li><a href="#to-do">To do</a></li>
</ul></div>
<h2 id="usage">Usage</h2>
<p>do something</p>
<h2 id="to-do">To do</h2>
<ul>
<li class="parsedown-task-list parsedown-task-list-close"><input type="checkbox" checked disabled /> test</li>
<li class="parsedown-task-list parsedown-task-list-open"><input type="checkbox" disabled /> deploy</li>
</ul>
```

## Cache

After converting some markdown, two files are added to the tmp folder.

* `{$hash}.md.html`
* `{$hash}.md.toc.json`

```php
$html = $app->module('cockpit')->markdown($md);

$hash = md5($md);

$cachepath = "tmp:///{$hash}.md.toc.json";

if ($app->filestorage->has($cachepath)) {
    $toc = \json_decode($app->filestorage->read($cachepath), true);
}

print_r($toc);
```


## License, credits and third party resources

License: MIT, author: Raffael Jesche, www.rlj.me

Used libraries:

* "erusev/parsedown-extra": "0.8.0", MIT
* "erusev/parsedown": "^1.8.0-beta-7", MIT
* "benjaminhoegh/parsedown-toc": "^1.4.2", MIT
* "leblanc-simon/parsedown-checkbox": "^0.2.0", MIT



[1]: https://github.com/agentejo/cockpit
[2]: https://github.com/erusev/parsedown
[3]: https://github.com/erusev/parsedown-extra
[4]: https://github.com/BenjaminHoegh/parsedownToc
[5]: https://github.com/leblanc-simon/parsedown-checkbox
