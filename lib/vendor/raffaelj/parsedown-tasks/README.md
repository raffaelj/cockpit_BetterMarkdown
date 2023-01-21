# Parsedown Tasks

Tasks list (checkbox) extension for [Parsedown][2] 1.7.4, [ParsedownExtra][1] 0.8.1 and [ParsedownToc][4]

## Installation

```bash
composer require raffaelj/parsedown-tasks
```

## Example

```php
<?php
require_once(__DIR__.'/vendor/autoload.php');

$parsedown = new ParsedownTasklist();

echo $parsedown->text('
- [ ] Add a pull request
- [x] Check the issues
');
```

Prints :

```html
<ul>
<li>
<input type="checkbox" disabled /> Add a pull request
</li>
<li>
<input type="checkbox" disabled checked /> Check the issues
</li>
</ul>
```

- [ ] Add a pull request
- [x] Check the issues

## Copyright and License

Copyright 2023 Raffael Jesche under the MIT license.

See [LICENSE][3] for more information.

Inspired by [ParsedownCheckbox][5] by [Simon Leblanc][6], MIT licensed

[1]: https://github.com/erusev/parsedown-extra
[2]: http://parsedown.org/
[3]: https://codeberg.org/raffaelj/parsedown-tasks/src/branch/main/LICENSE
[4]: https://github.com/BenjaminHoegh/parsedownToc/
[5]: https://github.com/leblanc-simon/parsedown-checkbox
[6]: https://www.leblanc-simon.fr
