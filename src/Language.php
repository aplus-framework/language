<?php declare(strict_types=1);
/*
 * This file is part of The Framework Language Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Language;

use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Class Language.
 *
 * @see https://www.sitepoint.com/localization-demystified-understanding-php-intl/
 * @see http://icu-project.org/apiref/icu4c/classMessageFormat.html#details
 */
class Language
{
	/**
	 * Fallback to default language.
	 *
	 * If the parent language is not found, try to use the default language.
	 */
	public const FALLBACK_DEFAULT = 2;
	/**
	 * Disable fallback.
	 *
	 * Use language lines only from the given language.
	 */
	public const FALLBACK_NONE = 0;
	/**
	 * Fallback to parent language.
	 *
	 * If the given language is pt-BR and a line is not found, try to use the line of pt.
	 *
	 * NOTE: The parent locale must be set in the Supported Locales to this fallback work.
	 */
	public const FALLBACK_PARENT = 1;
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
	protected int $fallbackLevel = Language::FALLBACK_DEFAULT;
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
	 * @var array<string,array>
	 */
	protected array $languages = [];
	/**
	 * Supported locales. Any other will be ignored.
	 *
	 * The default locale always is supported.
	 *
	 * @var array<int,string>
	 */
	protected array $supportedLocales = [];

	/**
	 * Language constructor.
	 *
	 * @param string $locale The default (and current) locale code
	 * @param array<int,string> $directories List of directory paths to find for language files
	 */
	public function __construct(string $locale, array $directories = [])
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
	 * NOTE: This function always will replace the old lines, as given from files.
	 *
	 * @param string $locale The locale code
	 * @param string $file The file name
	 * @param array<int|string,string> $lines An array of "line" => "text"
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
	 * @return string
	 */
	public function currency(float $value, string $currency, string $locale = null) : string
	{
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
		return (string) \MessageFormatter::formatMessage(
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
	public function getCurrentLocale() : string
	{
		return $this->currentLocale;
	}

	/**
	 * Gets the current locale directionality.
	 *
	 * @return string 'ltr' for Left-To-Right ot 'rtl' for Right-To-Left
	 */
	public function getCurrentLocaleDirection() : string
	{
		return static::getLocaleDirection($this->getCurrentLocale());
	}

	/**
	 * Gets the default locale.
	 *
	 * @return string
	 */
	public function getDefaultLocale() : string
	{
		return $this->defaultLocale;
	}

	/**
	 * Gets the list of directories where language files can be finded.
	 *
	 * @return array<int,string>
	 */
	public function getDirectories() : array
	{
		return $this->directories;
	}

	/**
	 * Gets the Fallback Level.
	 *
	 * @return int One of the FALLBACK_* constants
	 */
	public function getFallbackLevel() : int
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
	protected function getFallbackLine(string $locale, string $file, string $line) : array
	{
		$text = null;
		$level = $this->getFallbackLevel();
		// Fallback to parent
		if ($level > static::FALLBACK_NONE && \strpos($locale, '-') > 1) {
			[$locale] = \explode('-', $locale, 2);
			$text = $this->getLine($locale, $file, $line);
		}
		// Fallback to default
		if ($text === null
			&& $level > static::FALLBACK_PARENT
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
		return require_isolated($filename);
	}

	/**
	 * Gets a language line text.
	 *
	 * @param string $locale The required locale
	 * @param string $file The require file
	 * @param string $line The require line
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
		foreach ($this->findFilenames($locale, $file) as $filename) {
			$this->addLines($locale, $file, $this->getFileLines($filename));
		}
		return $this->languages[$locale][$file][$line] ?? null;
	}

	/**
	 * Gets the list of available locales, lines and texts.
	 *
	 * @return array<string,array>
	 */
	public function getLines() : array
	{
		return $this->languages;
	}

	/**
	 * Gets the list of Supported Locales.
	 *
	 * @return array<int,string>
	 */
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
	 * @param array<int,string> $args The arguments to be used in the formatted text
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
		return (string) \MessageFormatter::formatMessage(
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
	 * @param array<int,string> $args The arguments to be used in the formatted text
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
		$locale = $locale ?? $this->getCurrentLocale();
		$text = $this->getLine($locale, $file, $line);
		if ($text === null) {
			[$locale, $text] = $this->getFallbackLine($locale, $file, $line);
		}
		if ($text !== null) {
			$new_text = \MessageFormatter::formatMessage(
				$locale,
				$text,
				$args
			);
			// If formatter fail, use the non-formatted text
			$text = $new_text ?: $text;
		}
		return $text ?: $file . '.' . $line;
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
	 * @param array<int,string> $directories a list of valid directory paths
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
				foreach ($this->findFilenames($locale, $file) as $filename) {
					$this->addLines($locale, $file, $this->getFileLines($filename));
				}
			}
			$this->addFindedLocale($locale);
		}
	}

	/**
	 * Sets the Fallback Level.
	 *
	 * @param int $level one of the FALLBACK_* constants
	 *
	 * @return static
	 */
	public function setFallbackLevel(
		#[ExpectedValues([
			Language::FALLBACK_NONE,
			Language::FALLBACK_PARENT,
			Language::FALLBACK_DEFAULT,
		])] int $level
	) : static {
		if ( ! \in_array($level, [
			static::FALLBACK_NONE,
			static::FALLBACK_PARENT,
			static::FALLBACK_DEFAULT,
		], true)) {
			throw new InvalidArgumentException('Invalid fallback level: ' . $level);
		}
		$this->fallbackLevel = $level;
		return $this;
	}

	/**
	 * Sets a list of Supported Locales.
	 *
	 * NOTE: the default locale always is supported. But the current can be exclude
	 * if this function is called after {@see Language::setCurrentLocale()}.
	 *
	 * @param array<int,string> $locales the supported locales
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
