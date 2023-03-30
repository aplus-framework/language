Language
========

.. image:: image.png
    :alt: Aplus Framework Language Library

Aplus Framework Language Library

- `Installation`_
- `Getting Started`_
- `Adding Language lines`_
- `Rendering messages`_
- `Fallback`_
- `File Language Directories`_
- `Localization for web pages`_
- `Currencies, dates and ordinals`_
- `Using Language and HTTP libraries together`_
- `Database Integration`_
- `Conclusion`_

Installation
------------

The installation of this library can be done with Composer:

.. code-block::

    composer require aplus/language

Getting Started
---------------

The Language library is built to manage texts used for
`Internationalization and Localization <https://en.wikipedia.org/wiki/Internationalization_and_localization>`_
of applications.

An object of the Language class can be instantiated like this:

.. code-block:: php

    use Framework\Language\Language;

    $language = new Language();

The default locale is ``en``.

A different locale can be passed in the first argument of the constructor and an
array of `File Language Directories`_ in the second:

.. code-block:: php

    $language = new Language('jp', [
        __DIR__ . '/Languages',
        __DIR__ . '/foo',
        '/usr/share/app/languages',
    ]);

Supported Locales
^^^^^^^^^^^^^^^^^

Message lines will only be rendered in supported locales.

They can be set as in this example:

.. code-block:: php

    $language->setSupportedLocales([
        'es',
        'pt',
        'pt-br',
    ]); // static

And you can get them like this:

.. code-block:: php

    $locales = $language->getSupportedLocales(); // array

Adding Language lines
---------------------

Message lines can be added at any time.

Let's see an example adding messages to the ``contact`` file, in the ``en`` and
``pt-br`` locales:

.. code-block:: php

    $language->addLines('en', 'contact', [
        'name' => 'Name',
        'message' => 'Message',
        'send' => 'Send',
        'thanks' => 'Thanks for contacting us, {name}!'
    ])->addLines('pt-br', 'contact', [
        'name' => 'Nome',
        'message' => 'Mensagem',
        'send' => 'Enviar',
        'thanks' => 'Obrigado por nos contatar, {name}!',
    ]); // static

Rendering messages
------------------

It is possible to get the value of a line through the ``Language::render`` method.

In the first parameter, enter the name of the file and in the second, the name
of the line. As in the example below:

.. code-block:: php

    echo $language->render('contact', 'name');

If the current locale is ``en`` it will print:

.. code-block::

    Name

Rendering with arguments
^^^^^^^^^^^^^^^^^^^^^^^^

The ``thanks`` line has the ``{name}`` placeholder. 
With placeholders you can add custom values. Like, for example, to show a name:

.. code-block:: php

    $name = 'John';
    echo $language->render('contact', 'thanks', ['name' => $name]);

Will print:

.. code-block::

    Thanks for contacting us, John!

Rendering messages with custom locales on the fly
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In the fourth parameter of the ``render`` method, it is possible to set the name
of a supported locale:

.. code-block:: php

    echo $language->render('contact', 'message', [], 'pt-br');

Will print:

.. code-block::

    Mensagem

Using placeholders:

.. code-block:: php

    $name = 'João';
    echo $language->render('contact', 'thanks', ['name' => $name], 'pt-br');

Will print:

.. code-block::

    Obrigado por nos contatar, João!

Current Locale
^^^^^^^^^^^^^^

If the application has a default locale, but it is necessary to get the lines
from a different locale several times, it is more advantageous to use the
``Language::setCurrentLocale`` method.

Once the current locale is set, all the next message lines will come to that
locale, if the line is available, otherwise it will enter the `Fallback`_ system.

In the example below, the default locale is still ``en``. But by calling the
``setCurrentLocale`` method it is no longer necessary to set the fourth parameter:

.. code-block:: php

    $language->setCurrentLocale('pt-br'); // static

    echo $language->render('contact', 'name');

Will print:

.. code-block::

    Nome

.. code-block:: php

    echo $language->render('contact', 'thanks', ['name' => 'Johnny Bravo']);

Will print:

.. code-block::

    Obrigado por nos contatar, Johnny Bravo!

lang
^^^^

To render messages, you can also use the ``lang`` method. Which does the same
thing as the ``render`` method, but the file and line name must be concatenated
with a dot:

.. code-block:: php

    echo $language->lang('contact.thanks', ['name' => $name]);

Fallback
--------

The fallback system allows rendering a non-existing line in the current locale
with a line from the parent locale, the default locale, or none.

The fallback levels are present in the enum **Framework\Language\FallbackLevel**.

- `Fallback to None`_
- `Fallback to Parent Locale`_
- `Fallback to Default Locale`_

Fallback to None
^^^^^^^^^^^^^^^^

You can disable fallback with:

.. code-block:: php

    $language->setFallbackLevel(FallbackLevel::none); // static

This way, lines not found in the current locale will return a string. 
For example: ``contact.thanks``.

Fallback to Parent Locale
^^^^^^^^^^^^^^^^^^^^^^^^^

Parent locales are, for example: ``en`` to ``en-us`` and ``pt`` to ``pt-br``.

.. code-block:: php

    $language->setFallbackLevel(FallbackLevel::parent); // static

In the example below, only lines to the ``pt`` locale will be added,
and calls to ``pt-br`` will work:

.. code-block:: php

    $language->addLines('pt', 'words', [
        'beautifulDay' => 'Dia bonito.',
        'busName' => 'Nós chamamos "bus" de autocarro.',
    ]); // static

    echo $language->render('beautifulDay', 'words', 'pt-br') . '<br>'; 
    echo $language->render('busName', 'words', 'pt-br') . '<br>'; 

.. code-block::

    Dia bonito.<br>
    Nós chamamos "bus" de autocarro.<br>

Some child languages have differences from the parent language.

This happens in Brazilian Portuguese, where some words have different
interpretations than Portuguese from Portugal.

For example, "bus" in Brazil is ``ônibus`` and in Portugal it is ``autocarro``.

You can add specific lines for child locales. Let's see:

.. code-block:: php

    $language->addLines('pt-br', 'words', [
        'busName' => 'Nós chamamos "bus" de ônibus.',
    ]); // static

    echo $language->render('beautifulDay', 'words', 'pt-br') . '<br>'; 
    echo $language->render('busName', 'words', 'pt-br') . '<br>'; 

Will render the message of ``words.beautifulDay`` found in the parent locale
``pt`` and ``words.busName`` directly from ``pt-br``:

.. code-block::

    Dia bonito.<br>
    Nós chamamos "bus" de ônibus.<br>

Fallback to Default Locale
^^^^^^^^^^^^^^^^^^^^^^^^^^

Language's behavior is to fetch the file from the current location. 
If the file is not found, it looks for the parent locale. 
If not found, it looks for the default locale. 
If not found, the file and line names will be returned.

If the Fallback Level has been changed, you can set it like this:

.. code-block:: php

    $language->setFallbackLevel(FallbackLevel::default); // static

File Language Directories
-------------------------

Language lines can be loaded automatically.

To do this, add a base directory:

.. code-block:: php

    $directory = __DIR__ . '/Languages';
    $language->addDirectory($directory); // static

Inside the base directory there should be subdirectories with locale names and
inside them there should be language files that return an array.

Let's see an example with the language file **Languages/en/contact.php**:

.. code-block:: php

    return [
        'name' => 'Name',
        'message' => 'Message',
        'send' => 'Send',
    ];

Then you can call them with the render methods:

.. code-block:: php

    echo $language->lang('contact.message');

File loading may vary depending on the
`Case Sensitivity <https://en.wikipedia.org/wiki/Case_sensitivity#In_filesystems>`_
of the operating systems file system.

For example, these two paths can be considered the same on Windows:

- **Languages/en-us/contact.php**
- **Languages/en-US/contact.php**

But they are different on Linux.

For greater compatibility, we advise using lowercase locale directory names and
hyphenated separations. 
Lowercase,
`camel case <https://en.wikipedia.org/wiki/Camel_case#Programming_and_coding>`_
or `snake case <https://en.wikipedia.org/wiki/Snake_case>`_
for filenames and array keys.

Localization for web pages
--------------------------

HTML documents can have the language specified by the ``lang`` attribute.

Let's look at an example, showing the Arabic language, ``ar``.

.. code-block:: php

    <html lang="<?= $language->getCurrentLocale() ?>">

Output:

.. code-block:: html

    <html lang="ar">

It is also possible to specify the text direction through the ``dir`` attribute.

The Language class is able to identify the directionality of the current locale
automatically.

Let's see:

.. code-block:: php

    <html lang="<?= $language->getCurrentLocale() ?>" dir="<?= 
    $language->getCurrentLocaleDirection() ?>">

Will show:

.. code-block:: html

    <html lang="ar" dir="ltr">

Currencies, dates and ordinals
------------------------------

Currencies
^^^^^^^^^^

Numbers with currency symbols can be obtained with the ``Language::currency`` method.

For example:

.. code-block:: php

    echo $language->currency(10.5, 'USD'); // US$ 10,50
    echo $language->currency(10.5, 'JPY'); // JP¥ 10

Note that the Language class does not do any currency conversion. Just format.

Dates
^^^^^

Dates can be rendered in multiple locales, in the following formats:

- `Short Dates`_
- `Medium Dates`_
- `Long Dates`_
- `Full Dates`_

Short Dates
###########

Second argument by default is ``'short'``:

.. code-block:: php

    echo $language->date(time());

It will print as in the example below:

.. code-block::

    8/13/18

Medium Dates
############

Second argument can be ``'medium'``:

.. code-block:: php

    echo $language->date(time(), 'medium');

It will print as in the example below:

.. code-block::

    Aug 13, 2018

Long Dates
##########

Second argument can be ``'long'``:

.. code-block:: php

    echo $language->date(time(), 'long');

It will print as in the example below:

.. code-block::

    August 13, 2018

Full Dates
##########

Second argument can be ``'full'``:

.. code-block:: php

    echo $language->date(time(), 'full');

It will print as in the example below:

.. code-block::

    Monday, August 13, 2018

Ordinals
^^^^^^^^

Ordinal numbers in English:

.. code-block:: php

    $language->ordinal(1); // 1st
    $language->ordinal(2); // 2nd
    $language->ordinal(5); // 5th

Ordinal numbers in different locales:

.. code-block:: php

    $language->ordinal(1, 'de'); // 1º
    $language->ordinal(2, 'fr'); // 2º
    $language->ordinal(5, 'id'); // 3º

Using Language and HTTP libraries together
------------------------------------------

Let's create a small structure to show an automatically localized page,
negotiating the User-Agent language using the
`HTTP Library <https://gitlab.com/aplus-framework/libraries/http>`_.

Go to the terminal and run:

.. code-block::

    mkdir -p app/{languages/{en,es,pt-br},public}
    cd app
    composer require aplus/{http,language}

This is the directory tree created:

.. code-block::

    app
    ├── languages
    │   ├── en
    │   ├── es
    │   └── pt-br
    ├── public
    └── vendor

Create the **public/index.php** file:

.. code-block:: php

    <?php
    
    require __DIR__ . '/../vendor/autoload.php';

    use Framework\HTTP\Request;
    use Framework\Language\Language;

    $request = new Request();

    $supported = ['en', 'es', 'pt-br'];

    $negotiated = $request->negotiateLanguage($supported);

    $language = new Language();
    $language->addDirectory(__DIR__ . '/../languages')
             ->setSupportedLocales($supported)
             ->setCurrentLocale($negotiated);
    ?>
    <h1>Aplus App</h1>
    <p><?= $language->lang('home.welcome') ?></p>

If the language negotiated in the HTTP request is, for example, ``es``, which
has been set as a supported locale, the Language object will try to get the value
of the ``welcome`` key from the array returned in the **languages/es/home.php** file:

This is the content of  **languages/es/home.php**:

.. code-block:: php

    <?php

    return [
        'welcome' => '¡Bienvenido!'
    ];

Once this is done, up the PHP development server:

.. code-block::

    php -S localhost:8080 -t public/

In another terminal, make a request with curl:

.. code-block::

    curl -H "Accept-Language: es" http://localhost:8080

The content of the HTTP response will be:

.. code-block:: html

    <h1>Aplus App</h1>
    <p>¡Bienvenido!</p>

Challenge:
""""""""""

- Make requests with other languages.
- Add the default locale file.
- Implement an `RTL <https://en.wikipedia.org/wiki/Right-to-left_script>`_ page with HTML attributes and access it with a web browser.

Database Integration
--------------------

We will see how to fetch language messages in a database.

In this example, we will use the `Database Library <https://gitlab.com/aplus-framework/libraries/database>`_
and we will extend the Language class.

First, we create the database schema called **app** and in it we will create the
table **Language** and we will insert some lines for testing:

.. code-block:: php

    use Framework\Database\Database;
    use Framework\Database\Definition\Table\TableDefinition;
    
    $database = new Database('root', 'password');
    $database->createSchema('app')->run();
    $database->use('app');

    $database->createTable('Languages')->definition(function (TableDefinition $def) {
        $def->column('locale')->varchar(5);
        $def->column('file')->varchar(32);
        $def->column('line')->varchar(64);
        $def->column('message')->varchar(255);
    })->run();

    $database->insert('Languages')->values([
        ['en', 'home', 'welcome', 'Welcome!'],
        ['es', 'home', 'welcome', '¡Bienvenido!'],
        ['pt-br', 'home', 'welcome', 'Bem-vindo!'],
    ])->run();

Once that's done, we'll extend the Language class, adding functionality to
interact with the database:

.. code-block:: php

    use Framework\Language\Language;
    
    class DatabaseLanguage extends Language
    {
        protected Database $database;
        protected string $databaseTable = 'Languages';
    
        public function setDatabase(Database $database) : static
        {
            $this->database = $database;
            return $this;
        }
    
        protected function findLines(string $locale, string $file) : static
        {
            parent::findLines($locale, $file);
            if (isset($this->database)) {
                $result = $this->database->select()
                    ->from($this->databaseTable)
                    ->whereEqual('locale', $locale)
                    ->whereEqual('file', $file)
                    ->run();
                $lines = [];
                while ($row = $result->fetch()) {
                    $lines[$row->line] = $row->message;
                }
                $this->addLines($locale, $file, $lines);
            }
            return $this;
        }
    }

So we can render the messages directly from the database:

.. code-block:: php

    $database = new Database('root', 'password');
    $database->use('app');
    
    $language = new DatabaseLanguage();
    $language->setDatabase($database);
    
    echo $language->render('home', 'welcome');

Conclusion
----------

Aplus Language Library is an easy-to-use tool for, beginners and experienced, PHP developers. 
It is perfect for adapting applications to different languages. 
The more you use it, the more you will learn.

.. note::
    Did you find something wrong? 
    Be sure to let us know about it with an
    `issue <https://gitlab.com/aplus-framework/libraries/language/issues>`_. 
    Thank you!
