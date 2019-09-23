<?php namespace Tests\Language;

use Framework\Language\Language;
use PHPUnit\Framework\TestCase;

/**
 * Class LanguageTest.
 */
class LanguageTest extends TestCase
{
	/**
	 * @var Language
	 */
	protected $language;

	public function setUp() : void
	{
		$this->language = new Language('en', [
			__DIR__ . '/locales-1',
		]);
	}

	public function testCurrency()
	{
		$this->assertEquals('$10.50', $this->language->currency(10.5, 'USD'));
		$this->assertEquals('R$10.50', $this->language->currency(10.5, 'BRL'));
		$this->assertEquals('¥10', $this->language->currency(10.5, 'JPY'));
	}

	public function testCurrencyWithCustomLocale()
	{
		$this->assertEquals('US$ 10,50', $this->language->currency(10.5, 'USD', 'pt-br'));
		$this->assertEquals('R$ 10,50', $this->language->currency(10.5, 'BRL', 'pt-br'));
		$this->assertEquals('JP¥ 10', $this->language->currency(10.5, 'JPY', 'pt-br'));
	}

	public function testCurrentLocale()
	{
		$this->assertEquals('en', $this->language->getCurrentLocale());
		$this->language->setCurrentLocale('pt-br');
		$this->assertEquals('pt-br', $this->language->getCurrentLocale());
	}

	public function testDate()
	{
		$time = 1534160671; // 2018-08-13 08:44:31
		$this->assertEquals('8/13/18', $this->language->date($time));
		$this->assertEquals('8/13/18', $this->language->date($time, 'short'));
		$this->assertEquals('Aug 13, 2018', $this->language->date($time, 'medium'));
		$this->assertEquals('August 13, 2018', $this->language->date($time, 'long'));
		$this->assertEquals('Monday, August 13, 2018', $this->language->date($time, 'full'));
	}

	public function testDateWithCustomLocale()
	{
		$time = 1534160671; // 2018-08-13 08:44:31
		$this->assertEquals('13/08/2018', $this->language->date($time, null, 'pt-br'));
		$this->assertEquals('13/08/2018', $this->language->date($time, 'short', 'pt-br'));
		$this->assertEquals('13 de ago de 2018', $this->language->date($time, 'medium', 'pt-br'));
		$this->assertEquals('13 de agosto de 2018', $this->language->date($time, 'long', 'pt-br'));
		$this->assertEquals(
			'segunda-feira, 13 de agosto de 2018',
			$this->language->date($time, 'full', 'pt-br')
		);
	}

	public function testDateWithInvalidStyle()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->language->date(\time(), 'unknown');
	}

	public function testDirectories()
	{
		$this->assertEquals([
			__DIR__ . '/locales-1/',
		], $this->language->getDirectories());
		$this->language->setDirectories([
			__DIR__ . '/locales-2',
			__DIR__ . '/locales-1',
		]);
		$this->assertEquals([
			__DIR__ . '/locales-2/',
			__DIR__ . '/locales-1/',
		], $this->language->getDirectories());
		$this->language->setDirectories([]);
		$this->assertEquals([], $this->language->getDirectories());
		$this->language->addDirectory(__DIR__ . '/locales-1');
		$this->assertEquals([__DIR__ . '/locales-1/'], $this->language->getDirectories());
		$this->language->addDirectory(__DIR__ . '/locales-1');
		$this->assertEquals([__DIR__ . '/locales-1/'], $this->language->getDirectories());
	}

	public function testDirectoryNotFound()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->language->setDirectories([
			__DIR__ . '/unknow',
		]);
	}

	public function testSetDirectoryAfterScan()
	{
		$this->assertEquals('Bye!', $this->language->render('tests', 'bye'));
		$this->language->setDirectories([
			__DIR__ . '/locales-1',
			__DIR__ . '/locales-2',
		]);
		$this->assertEquals('Hasta la vista, baby.', $this->language->render('tests', 'bye'));
	}

	public function testFallbackLevel()
	{
		$this->assertEquals($this->language::FALLBACK_DEFAULT, $this->language->getFallbackLevel());
		$this->language->setFallbackLevel($this->language::FALLBACK_NONE);
		$this->assertEquals($this->language::FALLBACK_NONE, $this->language->getFallbackLevel());
	}

	public function testInvalidFallbackLevel()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->language->setFallbackLevel(999);
	}

	public function testLang()
	{
		$this->assertEquals('tests.unknown', $this->language->lang('tests.unknown'));
		$this->assertEquals('tests.unknown', $this->language->lang('tests.unknown', [], 'pt'));
		$this->assertEquals('Bye!', $this->language->lang('tests.bye'));
		$this->assertEquals('Bye!', $this->language->lang('tests.bye', [], 'pt'));
		$this->assertEquals('Hello, {0}!', $this->language->lang('tests.hello'));
		$this->assertEquals('Hello, Mary!', $this->language->lang('tests.hello', ['Mary']));
		$this->assertEquals('Hello, {0}!', $this->language->lang('tests.hello', [], 'pt'));
		$this->assertEquals('Hello, Mary!', $this->language->lang('tests.hello', ['Mary'], 'pt'));
		$this->language->setSupportedLocales(['pt']);
		$this->assertEquals('tests.unknown', $this->language->lang('tests.unknown', [], 'pt'));
		$this->assertEquals('Bye!', $this->language->lang('tests.bye', [], 'pt'));
		$this->assertEquals('Olá, Mary!', $this->language->lang('tests.hello', ['Mary'], 'pt'));
		$this->assertEquals('Olá, Mary!', $this->language->lang('tests.hello', ['Mary'], 'pt-br'));
	}

	public function testLines()
	{
		$this->assertEmpty($this->language->getLines());
		$this->language->render('tests', 'hello');
		$this->assertEquals([
			'en' => [
				'tests' => [
					'bye' => 'Bye!',
					'hello' => 'Hello, {0}!',
				],
			],
		], $this->language->getLines());
		$this->language->addLines('pt', 'tests', ['bye' => 'Tchau!']);
		$this->assertEquals([
			'en' => [
				'tests' => [
					'bye' => 'Bye!',
					'hello' => 'Hello, {0}!',
				],
			],
			'pt' => [
				'tests' => [
					'bye' => 'Tchau!',
				],
			],
		], $this->language->getLines());
		$this->language->addLines('en', 'tests', ['bye' => 'Good bye!']);
		$this->assertEquals([
			'en' => [
				'tests' => [
					'bye' => 'Good bye!',
					'hello' => 'Hello, {0}!',
				],
			],
			'pt' => [
				'tests' => [
					'bye' => 'Tchau!',
				],
			],
		], $this->language->getLines());
	}

	public function testOrdinal()
	{
		$this->assertEquals('1st', $this->language->ordinal(1));
		$this->assertEquals('2nd', $this->language->ordinal(2));
		$this->assertEquals('3rd', $this->language->ordinal(3));
		$this->assertEquals('4th', $this->language->ordinal(4));
	}

	public function testOrdinalWithCustomLocale()
	{
		$this->assertEquals('1º', $this->language->ordinal(1, 'pt-br'));
		$this->assertEquals('2º', $this->language->ordinal(2, 'pt-br'));
		$this->assertEquals('3º', $this->language->ordinal(3, 'pt-br'));
		$this->assertEquals('4º', $this->language->ordinal(4, 'pt-br'));
	}

	public function testRender()
	{
		$this->assertEquals('tests.unknown', $this->language->render('tests', 'unknown'));
		$this->assertEquals('tests.unknown', $this->language->render('tests', 'unknown', [], 'pt'));
		$this->assertEquals('Bye!', $this->language->render('tests', 'bye'));
		$this->assertEquals('Bye!', $this->language->render('tests', 'bye', [], 'pt'));
		$this->assertEquals('Hello, {0}!', $this->language->render('tests', 'hello'));
		$this->assertEquals('Hello, Mary!', $this->language->render('tests', 'hello', ['Mary']));
		$this->assertEquals('Hello, {0}!', $this->language->render('tests', 'hello', [], 'pt'));
		$this->assertEquals(
			'Hello, Mary!',
			$this->language->render('tests', 'hello', ['Mary'], 'pt')
		);
		$this->language->setSupportedLocales(['pt']);
		$this->assertEquals('tests.unknown', $this->language->render('tests', 'unknown', [], 'pt'));
		$this->assertEquals('Bye!', $this->language->render('tests', 'bye', [], 'pt'));
		$this->assertEquals(
			'Olá, Mary!',
			$this->language->render('tests', 'hello', ['Mary'], 'pt')
		);
		$this->assertEquals(
			'Olá, Mary!',
			$this->language->render('tests', 'hello', ['Mary'], 'pt-br')
		);
	}

	public function testSupportedLocales()
	{
		$this->assertEquals(['en'], $this->language->getSupportedLocales());
		$this->language->setSupportedLocales([
			'pt-br',
			'pt-br',
			'en',
			'pt-br',
			'es',
			'en',
			'de',
			'pt',
		]);
		$this->assertEquals(
			[
				'de',
				'en',
				'es',
				'pt',
				'pt-br',
			],
			$this->language->getSupportedLocales()
		);
		$this->language->setSupportedLocales(['jp']);
		$this->assertEquals(['en', 'jp'], $this->language->getSupportedLocales());
		$this->language->setSupportedLocales([]);
		$this->assertEquals(['en'], $this->language->getSupportedLocales());
		$this->language->setDefaultLocale('pt');
		$this->language->setSupportedLocales([]);
		$this->assertEquals(['pt'], $this->language->getSupportedLocales());
		$this->language->setCurrentLocale('pt-br');
		$this->language->setSupportedLocales([]);
		$this->assertEquals(['pt'], $this->language->getSupportedLocales());
		$this->language->setCurrentLocale('pt-br');
		$this->assertEquals(['pt', 'pt-br'], $this->language->getSupportedLocales());
	}
}
