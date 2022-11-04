<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Language Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Language;

use Framework\Helpers\Isolation;
use Framework\Language\Debug\LanguageCollector;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

/**
 * Class Language.
 *
 * @see https://www.sitepoint.com/localization-demystified-understanding-php-intl/
 * @see https://unicode-org.github.io/icu-docs/#/icu4c/classMessageFormat.html
 *
 * @package language
 */
class Language
{
    /**
     * The current locale.
     */
    protected string $currentLocale;
    /**
     * The default locale.
     */
    protected string $defaultLocale;
    /**
     * List of directories to find for files.
     *
     * @var array<int,string>
     */
    protected array $directories = [];
    /**
     * The locale fallback level.
     */
    protected FallbackLevel $fallbackLevel = FallbackLevel::default;
    /**
     * List with locales of already scanned directories.
     *
     * @var array<int,string>
     */
    protected array $findedLocales = [];
    /**
     * Language lines.
     *
     * List of "locale" => "file" => "line" => "text"
     *
     * @var array<string,array<string,array<string,string>>>
     */
    protected array $languages = [];
    /**
     * Supported locales. Any other will be ignored.
     *
     * The default locale is always supported.
     *
     * @var array<int,string>
     */
    protected array $supportedLocales = [];
    protected LanguageCollector $debugCollector;

    /**
     * Language constructor.
     *
     * @param string $locale The default (and current) locale code
     * @param array<int,string> $directories List of directory paths to find for language files
     */
    public function __construct(string $locale = 'en', array $directories = [])
    {
        $this->setDefaultLocale($locale);
        $this->setCurrentLocale($locale);
        if ($directories) {
            $this->setDirectories($directories);
        }
    }

    /**
     * Adds a locale to the list of already scanned directories.
     *
     * @param string $locale
     *
     * @return static
     */
    protected function addFindedLocale(string $locale) : static
    {
        $this->findedLocales[] = $locale;
        return $this;
    }

    /**
     * Adds custom lines for a specific locale.
     *
     * Useful to set lines from a database or any parsed file.
     *
     * NOTE: This function will always replace the old lines, as given from files.
     *
     * @param string $locale The locale code
     * @param string $file The file name
     * @param array<string> $lines An array of "line" => "text"
     *
     * @return static
     */
    public function addLines(string $locale, string $file, array $lines) : static
    {
        if ( ! $this->isFindedLocale($locale)) {
            // Certify that all directories are scanned first
            // So, this method always have priority on replacements
            $this->getLine($locale, $file, '');
        }
        $this->languages[$locale][$file] = isset($this->languages[$locale][$file])
            ? \array_replace($this->languages[$locale][$file], $lines)
            : $lines;
        return $this;
    }

    /**
     * Gets a currency value formatted in a given locale.
     *
     * @param float $value The money value
     * @param string $currency The Currency code. i.e. USD, BRL, JPY
     * @param string|null $locale A custom locale or null to use the current
     *
     * @see https://en.wikipedia.org/wiki/ISO_4217#Active_codes
     *
     * @return string
     */
    public function currency(float $value, string $currency, string $locale = null) : string
    {
        // @phpstan-ignore-next-line
        return \NumberFormatter::create(
            $locale ?? $this->getCurrentLocale(),
            \NumberFormatter::CURRENCY
        )->formatCurrency($value, $currency);
    }

    /**
     * Gets a formatted date in a given locale.
     *
     * @param int $time An Unix timestamp
     * @param string|null $style One of: short, medium, long or full. Leave null to use short
     * @param string|null $locale A custom locale or null to use the current
     *
     * @throws InvalidArgumentException for invalid style format
     *
     * @return string
     */
    public function date(int $time, string $style = null, string $locale = null) : string
    {
        if ($style && ! \in_array($style, ['short', 'medium', 'long', 'full'], true)) {
            throw new InvalidArgumentException('Invalid date style format: ' . $style);
        }
        $style = $style ?: 'short';
        // @phpstan-ignore-next-line
        return \MessageFormatter::formatMessage(
            $locale ?? $this->getCurrentLocale(),
            "{time, date, {$style}}",
            ['time' => $time]
        );
    }

    /**
     * Find for absolute file paths from where language lines can be loaded.
     *
     * @param string $locale The required locale
     * @param string $file The required file
     *
     * @return array<int,string> a list of valid filenames
     */
    #[Pure]
    protected function findFilenames(string $locale, string $file) : array
    {
        $filenames = [];
        foreach ($this->getDirectories() as $directory) {
            $path = $directory . $locale . \DIRECTORY_SEPARATOR . $file . '.php';
            if (\is_file($path)) {
                $filenames[] = $path;
            }
        }
        return $filenames;
    }

    /**
     * Gets the current locale.
     *
     * @return string
     */
    #[Pure]
    public function getCurrentLocale() : string
    {
        return $this->currentLocale;
    }

    /**
     * Gets the current locale directionality.
     *
     * @return string 'ltr' for Left-To-Right ot 'rtl' for Right-To-Left
     */
    #[Pure]
    public function getCurrentLocaleDirection() : string
    {
        return static::getLocaleDirection($this->getCurrentLocale());
    }

    /**
     * Gets the default locale.
     *
     * @return string
     */
    #[Pure]
    public function getDefaultLocale() : string
    {
        return $this->defaultLocale;
    }

    /**
     * Gets the list of directories where language files can be finded.
     *
     * @return array<int,string>
     */
    #[Pure]
    public function getDirectories() : array
    {
        return $this->directories;
    }

    /**
     * Gets the Fallback Level.
     *
     * @return FallbackLevel
     */
    #[Pure]
    public function getFallbackLevel() : FallbackLevel
    {
        return $this->fallbackLevel;
    }

    /**
     * Gets a text line and locale according the Fallback Level.
     *
     * @param string $locale The locale to get his fallback line
     * @param string $file The file
     * @param string $line The line
     *
     * @return array<int,string|null> Two numeric keys containg the used locale and text
     */
    #[ArrayShape(['string', 'string|null'])]
    protected function getFallbackLine(string $locale, string $file, string $line) : array
    {
        $text = null;
        $level = $this->getFallbackLevel()->value;
        // Fallback to parent
        if ($level > FallbackLevel::none->value && \strpos($locale, '-') > 1) {
            [$locale] = \explode('-', $locale, 2);
            $text = $this->getLine($locale, $file, $line);
        }
        // Fallback to default
        if ($text === null
            && $level > FallbackLevel::parent->value
            && $locale !== $this->getDefaultLocale()
        ) {
            $locale = $this->getDefaultLocale();
            $text = $this->getLine($locale, $file, $line);
        }
        return [
            $locale,
            $text,
        ];
    }

    /**
     * @param string $filename
     *
     * @return array<int,string>
     */
    protected function getFileLines(string $filename) : array
    {
        return Isolation::require($filename);
    }

    /**
     * Gets a language line text.
     *
     * @param string $locale The required locale
     * @param string $file The required file
     * @param string $line The required line
     *
     * @return string|null The text of the line or null if the line is not found
     */
    protected function getLine(string $locale, string $file, string $line) : ?string
    {
        if (isset($this->languages[$locale][$file][$line])) {
            return $this->languages[$locale][$file][$line];
        }
        if ( ! \in_array($locale, $this->getSupportedLocales(), true)) {
            return null;
        }
        $this->addFindedLocale($locale);
        $this->findLines($locale, $file);
        return $this->languages[$locale][$file][$line] ?? null;
    }

    /**
     * Find and add lines.
     *
     * This method can be overridden to find lines in custom storage, such as
     * in a database table.
     *
     * @param string $locale
     * @param string $file
     *
     * @return static
     */
    protected function findLines(string $locale, string $file) : static
    {
        foreach ($this->findFilenames($locale, $file) as $filename) {
            $this->addLines($locale, $file, $this->getFileLines($filename));
        }
        return $this;
    }

    /**
     * Gets the list of available locales, lines and texts.
     *
     * @return array<string,array<string,array<string,string>>>
     */
    #[Pure]
    public function getLines() : array
    {
        return $this->languages;
    }

    public function resetLines() : static
    {
        $this->languages = [];
        return $this;
    }

    /**
     * Gets the list of Supported Locales.
     *
     * @return array<int,string>
     */
    #[Pure]
    public function getSupportedLocales() : array
    {
        return $this->supportedLocales;
    }

    /**
     * Tells if a locale already was found in the directories.
     *
     * @param string $locale The locale
     *
     * @see \Framework\Language\Language::getLine()
     *
     * @return bool
     */
    #[Pure]
    protected function isFindedLocale(string $locale) : bool
    {
        return \in_array($locale, $this->findedLocales, true);
    }

    /**
     * Renders a language file line with dot notation format.
     *
     * E.g. home.hello matches home for file and hello for line.
     *
     * @param string $line The dot notation file line
     * @param array<mixed> $args The arguments to be used in the formatted text
     * @param string|null $locale A custom locale or null to use the current
     *
     * @return string|null The rendered text or null if not found
     */
    public function lang(string $line, array $args = [], string $locale = null) : ?string
    {
        [$file, $line] = \explode('.', $line, 2);
        return $this->render($file, $line, $args, $locale);
    }

    /**
     * Gets an ordinal number in a given locale.
     *
     * @param int $number The number to be converted to ordinal
     * @param string|null $locale A custom locale or null to use the current
     *
     * @return string
     */
    public function ordinal(int $number, string $locale = null) : string
    {
        // @phpstan-ignore-next-line
        return \MessageFormatter::formatMessage(
            $locale ?? $this->getCurrentLocale(),
            '{number, ordinal}',
            ['number' => $number]
        );
    }

    /**
     * Renders a language file line.
     *
     * @param string $file The file
     * @param string $line The file line
     * @param array<mixed> $args The arguments to be used in the formatted text
     * @param string|null $locale A custom locale or null to use the current
     *
     * @return string The rendered text or file.line expression
     */
    public function render(
        string $file,
        string $line,
        array $args = [],
        string $locale = null
    ) : string {
        if (isset($this->debugCollector)) {
            $start = \microtime(true);
            $rendered = $this->getRenderedLine($file, $line, $args, $locale);
            $end = \microtime(true);
            $this->debugCollector->adddata([
                'start' => $start,
                'end' => $end,
                'file' => $file,
                'line' => $line,
                'locale' => $rendered['locale'],
                'message' => $rendered['message'],
            ]);
            return $rendered['message'];
        }
        return $this->getRenderedLine($file, $line, $args, $locale)['message'];
    }

    /**
     * @param string $file
     * @param string $line
     * @param array<mixed> $args
     * @param string|null $locale
     *
     * @return array<string,string>
     */
    #[ArrayShape(['locale' => 'string', 'message' => 'string'])]
    protected function getRenderedLine(
        string $file,
        string $line,
        array $args = [],
        string $locale = null
    ) : array {
        $locale ??= $this->getCurrentLocale();
        $text = $this->getLine($locale, $file, $line);
        if ($text === null) {
            [$locale, $text] = $this->getFallbackLine($locale, $file, $line);
        }
        if ($text !== null) {
            $text = $this->formatMessage($text, $args, $locale);
        }
        return [
            'locale' => $locale,
            'message' => $text ?? ($file . '.' . $line),
        ];
    }

    /**
     * Checks if Language has a line.
     *
     * @param string $file The file
     * @param string $line The file line
     * @param string|null $locale A custom locale or null to use the current
     *
     * @return bool True if the line is found, otherwise false
     */
    public function hasLine(string $file, string $line, string $locale = null) : bool
    {
        $locale ??= $this->getCurrentLocale();
        $text = $this->getLine($locale, $file, $line);
        if ($text === null) {
            $text = $this->getFallbackLine($locale, $file, $line)[1];
        }
        return $text !== null;
    }

    /**
     * @param string $text
     * @param array<mixed> $args
     * @param string|null $locale
     *
     * @return string
     */
    public function formatMessage(string $text, array $args = [], string $locale = null) : string
    {
        $args = \array_map(static function ($arg) : string {
            return (string) $arg;
        }, $args);
        $locale ??= $this->getCurrentLocale();
        return \MessageFormatter::formatMessage($locale, $text, $args) ?: $text;
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The current locale. This automatically is set as
     * one of Supported Locales.
     *
     * @return static
     */
    public function setCurrentLocale(string $locale) : static
    {
        $this->currentLocale = $locale;
        $locales = $this->getSupportedLocales();
        $locales[] = $locale;
        $this->setSupportedLocales($locales);
        return $this;
    }

    /**
     * Sets the default locale.
     *
     * @param string $locale The default locale. This automatically is set as
     * one of Supported Locales.
     *
     * @return static
     */
    public function setDefaultLocale(string $locale) : static
    {
        $this->defaultLocale = $locale;
        $locales = $this->getSupportedLocales();
        $locales[] = $locale;
        $this->setSupportedLocales($locales);
        return $this;
    }

    /**
     * Sets a list of directories where language files can be found.
     *
     * @param array<string> $directories a list of valid directory paths
     *
     * @throws InvalidArgumentException if a directory path is inaccessible
     *
     * @return static
     */
    public function setDirectories(array $directories) : static
    {
        $dirs = [];
        foreach ($directories as $directory) {
            $path = \realpath($directory);
            if ( ! $path || ! \is_dir($path)) {
                throw new InvalidArgumentException('Directory path inaccessible: ' . $directory);
            }
            $dirs[] = $path . \DIRECTORY_SEPARATOR;
        }
        $this->directories = $dirs ? \array_unique($dirs) : [];
        $this->reindex();
        return $this;
    }

    /**
     * @param string $directory
     *
     * @return static
     */
    public function addDirectory(string $directory) : static
    {
        $this->setDirectories(\array_merge([
            $directory,
        ], $this->getDirectories()));
        return $this;
    }

    protected function reindex() : void
    {
        $this->findedLocales = [];
        foreach ($this->languages as $locale => $files) {
            foreach (\array_keys($files) as $file) {
                $this->findLines($locale, $file);
            }
            $this->addFindedLocale($locale);
        }
    }

    /**
     * Sets the Fallback Level.
     *
     * @param FallbackLevel $level
     *
     * @return static
     */
    public function setFallbackLevel(FallbackLevel $level) : static
    {
        $this->fallbackLevel = $level;
        return $this;
    }

    /**
     * Sets a list of Supported Locales.
     *
     * NOTE: the default locale always is supported. But the current can be exclude
     * if this function is called after {@see Language::setCurrentLocale()}.
     *
     * @param array<string> $locales the supported locales
     *
     * @return static
     */
    public function setSupportedLocales(array $locales) : static
    {
        $locales[] = $this->getDefaultLocale();
        $locales = \array_unique($locales);
        \sort($locales);
        $this->supportedLocales = $locales;
        $this->reindex();
        return $this;
    }

    public function setDebugCollector(LanguageCollector $debugCollector) : static
    {
        $this->debugCollector = $debugCollector;
        $this->debugCollector->setLanguage($this);
        return $this;
    }

    /**
     * Gets text directionality based on locale.
     *
     * @param string $locale The locale code
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/dir
     * @see https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
     *
     * @return string 'ltr' for Left-To-Right ot 'rtl' for Right-To-Left
     */
    #[Pure]
    public static function getLocaleDirection(string $locale) : string
    {
        $locale = \strtolower($locale);
        $locale = \strtr($locale, ['_' => '-']);
        if (\in_array($locale, [
            'ar',
            'arc',
            'ckb',
            'dv',
            'fa',
            'ha',
            'he',
            'khw',
            'ks',
            'ps',
            'ur',
            'uz-af',
            'yi',
        ], true)) {
            return 'rtl';
        }
        return 'ltr';
    }
}
