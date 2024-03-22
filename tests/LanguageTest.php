<?php
/*
 * This file is part of Aplus Framework Language Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Language;

use Framework\Language\FallbackLevel;
use Framework\Language\Language;
use PHPUnit\Framework\TestCase;

/**
 * Class LanguageTest.
 */
final class LanguageTest extends TestCase
{
    protected Language $language;

    public function setUp() : void
    {
        $this->language = new Language(directories: [
            __DIR__ . '/locales-1',
        ]);
    }

    public function testCurrency() : void
    {
        self::assertSame('$10.50', $this->language->currency(10.5, 'USD'));
        self::assertSame('R$10.50', $this->language->currency(10.5, 'BRL'));
        self::assertSame('¥10', $this->language->currency(10.5, 'JPY'));
    }

    public function testCurrencyWithCustomLocale() : void
    {
        self::assertSame('US$ 10,50', $this->language->currency(10.5, 'USD', 'pt-br'));
        self::assertSame('R$ 10,50', $this->language->currency(10.5, 'BRL', 'pt-br'));
        self::assertSame('JP¥ 10', $this->language->currency(10.5, 'JPY', 'pt-br'));
    }

    public function testCurrentLocale() : void
    {
        self::assertSame('en', $this->language->getCurrentLocale());
        $this->language->setCurrentLocale('pt-br');
        self::assertSame('pt-br', $this->language->getCurrentLocale());
    }

    public function testCurrentLocaleDirection() : void
    {
        self::assertSame('en', $this->language->getCurrentLocale());
        self::assertSame('ltr', $this->language->getCurrentLocaleDirection());
        $this->language->setCurrentLocale('pt-br');
        self::assertSame('pt-br', $this->language->getCurrentLocale());
        self::assertSame('ltr', $this->language->getCurrentLocaleDirection());
        $this->language->setCurrentLocale('uz_AF');
        self::assertSame('uz_AF', $this->language->getCurrentLocale());
        self::assertSame('rtl', $this->language->getCurrentLocaleDirection());
    }

    public function testDate() : void
    {
        $time = 1534160671; // 2018-08-13 08:44:31
        self::assertSame('8/13/18', $this->language->date($time));
        self::assertSame('8/13/18', $this->language->date($time, 'short'));
        self::assertSame('Aug 13, 2018', $this->language->date($time, 'medium'));
        self::assertSame('August 13, 2018', $this->language->date($time, 'long'));
        self::assertSame('Monday, August 13, 2018', $this->language->date($time, 'full'));
    }

    public function testDateWithCustomLocale() : void
    {
        $time = 1534160671; // 2018-08-13 08:44:31
        self::assertSame('13/08/2018', $this->language->date($time, null, 'pt-br'));
        self::assertSame('13/08/2018', $this->language->date($time, 'short', 'pt-br'));
        self::assertSame('13 de ago. de 2018', $this->language->date($time, 'medium', 'pt-br'));
        self::assertSame('13 de agosto de 2018', $this->language->date($time, 'long', 'pt-br'));
        self::assertSame(
            'segunda-feira, 13 de agosto de 2018',
            $this->language->date($time, 'full', 'pt-br')
        );
    }

    public function testDateWithInvalidStyle() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->language->date(\time(), 'unknown');
    }

    public function testDirectories() : void
    {
        self::assertSame([
            __DIR__ . '/locales-1/',
        ], $this->language->getDirectories());
        $this->language->setDirectories([
            __DIR__ . '/locales-2',
            __DIR__ . '/locales-1',
        ]);
        self::assertSame([
            __DIR__ . '/locales-2/',
            __DIR__ . '/locales-1/',
        ], $this->language->getDirectories());
        $this->language->setDirectories([]);
        self::assertSame([], $this->language->getDirectories());
        $this->language->addDirectory(__DIR__ . '/locales-1');
        self::assertSame([__DIR__ . '/locales-1/'], $this->language->getDirectories());
        $this->language->addDirectory(__DIR__ . '/locales-1');
        self::assertSame([__DIR__ . '/locales-1/'], $this->language->getDirectories());
    }

    public function testDirectoryNotFound() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->language->setDirectories([
            __DIR__ . '/unknow',
        ]);
    }

    public function testSetDirectoryAfterScan() : void
    {
        self::assertSame('Bye!', $this->language->render('tests', 'bye'));
        $this->language->setDirectories([
            __DIR__ . '/locales-1',
            __DIR__ . '/locales-2',
        ]);
        self::assertSame('Hasta la vista, baby.', $this->language->render('tests', 'bye'));
    }

    public function testFallbackLevel() : void
    {
        self::assertSame(FallbackLevel::default, $this->language->getFallbackLevel());
        $this->language->setFallbackLevel(FallbackLevel::none);
        self::assertSame(FallbackLevel::none, $this->language->getFallbackLevel());
        $this->language->setFallbackLevel(1);
        self::assertSame(FallbackLevel::parent, $this->language->getFallbackLevel());
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage(
            '5 is not a valid backing value for enum Framework\Language\FallbackLevel'
        );
        $this->language->setFallbackLevel(5);
    }

    public function testLang() : void
    {
        self::assertSame('tests.unknown', $this->language->lang('tests.unknown'));
        self::assertSame('tests.unknown', $this->language->lang('tests.unknown', [], 'pt'));
        self::assertSame('Bye!', $this->language->lang('tests.bye'));
        self::assertSame('Bye!', $this->language->lang('tests.bye', [], 'pt'));
        self::assertSame('Hello, {0}!', $this->language->lang('tests.hello'));
        self::assertSame('Hello, Mary!', $this->language->lang('tests.hello', ['Mary']));
        self::assertSame('Hello, {0}!', $this->language->lang('tests.hello', [], 'pt'));
        self::assertSame('Hello, Mary!', $this->language->lang('tests.hello', ['Mary'], 'pt'));
        $this->language->setSupportedLocales(['pt']);
        self::assertSame('tests.unknown', $this->language->lang('tests.unknown', [], 'pt'));
        self::assertSame('Bye!', $this->language->lang('tests.bye', [], 'pt'));
        self::assertSame('Olá, Mary!', $this->language->lang('tests.hello', ['Mary'], 'pt'));
        self::assertSame('Olá, Mary!', $this->language->lang('tests.hello', ['Mary'], 'pt-br'));
    }

    public function testLines() : void
    {
        self::assertEmpty($this->language->getLines());
        $this->language->render('tests', 'hello');
        self::assertSame([
            'en' => [
                'tests' => [
                    'bye' => 'Bye!',
                    'hello' => 'Hello, {0}!',
                ],
            ],
        ], $this->language->getLines());
        $this->language->addLines('pt', 'tests', ['bye' => 'Tchau!']);
        self::assertSame([
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
        self::assertSame([
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

    public function testResetLines() : void
    {
        self::assertEmpty($this->language->getLines());
        $this->language->render('tests', 'hello');
        self::assertNotEmpty($this->language->getLines());
        $this->language->resetLines();
        self::assertEmpty($this->language->getLines());
    }

    public function testOrdinal() : void
    {
        self::assertSame('1st', $this->language->ordinal(1));
        self::assertSame('2nd', $this->language->ordinal(2));
        self::assertSame('3rd', $this->language->ordinal(3));
        self::assertSame('4th', $this->language->ordinal(4));
    }

    public function testOrdinalWithCustomLocale() : void
    {
        self::assertSame('1º', $this->language->ordinal(1, 'pt-br'));
        self::assertSame('2º', $this->language->ordinal(2, 'pt-br'));
        self::assertSame('3º', $this->language->ordinal(3, 'pt-br'));
        self::assertSame('4º', $this->language->ordinal(4, 'pt-br'));
    }

    public function testRender() : void
    {
        self::assertSame('tests.unknown', $this->language->render('tests', 'unknown'));
        self::assertSame('tests.unknown', $this->language->render('tests', 'unknown', [], 'pt'));
        self::assertSame('Bye!', $this->language->render('tests', 'bye'));
        self::assertSame('Bye!', $this->language->render('tests', 'bye', [], 'pt'));
        self::assertSame('Hello, {0}!', $this->language->render('tests', 'hello'));
        self::assertSame('Hello, Mary!', $this->language->render('tests', 'hello', ['Mary']));
        self::assertSame('Hello, {0}!', $this->language->render('tests', 'hello', [], 'pt'));
        self::assertSame(
            'Hello, Mary!',
            $this->language->render('tests', 'hello', ['Mary'], 'pt')
        );
        $this->language->setSupportedLocales(['pt']);
        self::assertSame('tests.unknown', $this->language->render('tests', 'unknown', [], 'pt'));
        self::assertSame('Bye!', $this->language->render('tests', 'bye', [], 'pt'));
        self::assertSame(
            'Olá, Mary!',
            $this->language->render('tests', 'hello', ['Mary'], 'pt')
        );
        self::assertSame(
            'Olá, Mary!',
            $this->language->render('tests', 'hello', ['Mary'], 'pt-br')
        );
    }

    public function testRenderWithMixedArgsKeys() : void
    {
        $this->language->addLines('en', 'hello', [
            'int' => 'Hello, {0}!',
            'string' => 'Hello, {name}!',
        ]);
        self::assertSame(
            'Hello, Mary!',
            $this->language->render('hello', 'int', ['Mary'])
        );
        self::assertSame(
            'Hello, {0}!',
            $this->language->render('hello', 'int', ['name' => 'Mary'])
        );
        self::assertSame(
            'Hello, Mary!',
            $this->language->render('hello', 'string', ['name' => 'Mary'])
        );
        self::assertSame(
            'Hello, {name}!',
            $this->language->render('hello', 'string', ['Mary'])
        );
    }

    public function testRenderWithStringableArgs() : void
    {
        $stringable = new class() {
            public function __toString() : string
            {
                return 'Mary Jane';
            }
        };
        self::assertSame(
            'Hello, Mary Jane!',
            $this->language->render('tests', 'hello', [$stringable])
        );
    }

    public function testRenderWithMessageFormatterPattern() : void
    {
        $this->language->addLines('en', 'time', [
            'daysAgo' => '{0, plural, =1{# day} other{# days}} ago',
        ]);
        self::assertSame(
            '0 days ago',
            $this->language->render('time', 'daysAgo', [0])
        );
        self::assertSame(
            '1 day ago',
            $this->language->render('time', 'daysAgo', [1])
        );
        self::assertSame(
            '2 days ago',
            $this->language->render('time', 'daysAgo', [2])
        );
    }

    public function testHasLine() : void
    {
        self::assertFalse($this->language->hasLine('tests', 'unknown'));
        self::assertTrue($this->language->hasLine('tests', 'bye'));
    }

    public function testSupportedLocales() : void
    {
        self::assertSame(['en'], $this->language->getSupportedLocales());
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
        self::assertSame(
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
        self::assertSame(['en', 'jp'], $this->language->getSupportedLocales());
        $this->language->setSupportedLocales([]);
        self::assertSame(['en'], $this->language->getSupportedLocales());
        $this->language->setDefaultLocale('pt');
        $this->language->setSupportedLocales([]);
        self::assertSame(['pt'], $this->language->getSupportedLocales());
        $this->language->setCurrentLocale('pt-br');
        $this->language->setSupportedLocales([]);
        self::assertSame(['pt'], $this->language->getSupportedLocales());
        $this->language->setCurrentLocale('pt-br');
        self::assertSame(['pt', 'pt-br'], $this->language->getSupportedLocales());
    }
}
