# Language Library *documentation*

The Language Library makes it possible to translate an application into multiple languages.

Loading the library can be like this:

```php
use Framework\Language\Language;

$language = new Language('en'); // Default locale is "en", english
```

Several may be the translation directories. In each of them there should be
folders with the accepted language names.

```php
$language->setDirectories([
    __DIR__ . '/languages'
]);
```

Inside `__DIR__. '/ languages'` there should be folders named like `en`,
`en-us`, `es`, etc..

Within each folder should be the translation files.

The contents of each file should look like this:

```php
// File: languages/en/home.php
return [
    'hello' => 'Hello',
    'welcome' => 'Welcome, {0}!',
];
```

Where keys are names and values are translations.

```php
echo $language->render('home', 'hello'); // prints "Hello"
echo $language->render('home', 'welcome', ['Natan']); // prints "Welcome, Natan!"
// Or
echo $language->lang('home.hello');  // prints "Hello"
echo $language->lang('home.welcome', ['Natan']);  // prints "Welcome, Natan!"
```

### Currency

The currency format can be translated with the `currency` method:

```php
echo $language->currency(10.5, 'USD'); // US$ 10,50
echo $language->currency(10.5, 'BRL'); // R$ 10,50
echo $language->currency(10.5, 'JPY'); // JP¥ 10
```

## Date

Dates can be localized in several formats:

```php
$time = 1534160671;
echo $language->date($time); // 8/13/18
echo $language->date($time, 'short'); // 8/13/18
echo $language->date($time, 'medium'); // Aug 13, 2018
echo $language->date($time, 'long'); // August 13, 2018
echo $language->date($time, 'full'); // Monday, August 13, 2018
// Custom locale:
echo $language->date($time, 'full', 'pt-br'); // segunda-feira, 13 de agosto de 2018
```

# Ordinal

You can also sort numbers with the `ordinal` method:

```php
$language->ordinal(1); // 1st
$language->ordinal(2); // 2nd
$language->ordinal(3); // 3rd
$language->ordinal(4); // 4th
$language->ordinal(1, 'pt-br'); // 1º
```
