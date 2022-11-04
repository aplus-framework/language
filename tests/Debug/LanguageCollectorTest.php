<?php
/*
 * This file is part of Aplus Framework Language Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Language\Debug;

use Framework\Language\Debug\LanguageCollector;
use Framework\Language\FallbackLevel;
use Framework\Language\Language;
use PHPUnit\Framework\TestCase;

final class LanguageCollectorTest extends TestCase
{
    protected LanguageCollector $collector;

    protected function setUp() : void
    {
        $this->collector = new LanguageCollector();
    }

    protected function makeLanguage() : Language
    {
        $language = new Language('en', [
            __DIR__ . '/../locales-1',
            __DIR__ . '/../locales-2',
        ]);
        $language->setDebugCollector($this->collector);
        return $language;
    }

    public function testNoLanguage() : void
    {
        self::assertStringContainsString(
            'A Language instance has not been set',
            $this->collector->getContents()
        );
    }

    public function testFallbackLevels() : void
    {
        $language = $this->makeLanguage();
        self::assertStringContainsString(
            '2 (default)',
            $this->collector->getContents()
        );
        $language->setFallbackLevel(FallbackLevel::parent);
        self::assertStringContainsString(
            '1 (parent)',
            $this->collector->getContents()
        );
        $language->setFallbackLevel(FallbackLevel::none);
        self::assertStringContainsString(
            '0 (none)',
            $this->collector->getContents()
        );
    }

    public function testNoDirectories() : void
    {
        $language = new Language();
        $language->setDebugCollector($this->collector);
        self::assertStringContainsString(
            'No directory set for this Language instance',
            $this->collector->getContents()
        );
    }

    public function testDirectories() : void
    {
        $this->makeLanguage();
        self::assertStringContainsString(
            \realpath(__DIR__ . '/../locales-2') . \DIRECTORY_SEPARATOR,
            $this->collector->getContents()
        );
    }

    public function testNoAvailableMessages() : void
    {
        $language = new Language();
        $language->setDebugCollector($this->collector);
        self::assertStringContainsString(
            'No file lines available for this Language instance',
            $this->collector->getContents()
        );
    }

    public function testAvailableMessages() : void
    {
        $language = $this->makeLanguage();
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'There are 4 message lines available to the current locale (en)',
            $contents
        );
        self::assertStringContainsString(
            '<td>none</td>',
            $contents
        );
        self::assertStringNotContainsString(
            '<td>default</td>',
            $contents
        );
        self::assertStringNotContainsString(
            '<td>parent</td>',
            $contents
        );
        $language->setCurrentLocale('pt');
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'There are 4 message lines available to the current locale (pt)',
            $contents
        );
        self::assertStringContainsString(
            '<td>default</td>',
            $contents
        );
        self::assertStringNotContainsString(
            '<td>parent</td>',
            $contents
        );
        self::assertStringContainsString(
            '<td>none</td>',
            $contents
        );
        $language->setCurrentLocale('pt-br');
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'There are 4 message lines available to the current locale (pt-br)',
            $contents
        );
        self::assertStringContainsString(
            '<td>default</td>',
            $contents
        );
        self::assertStringContainsString(
            '<td>parent</td>',
            $contents
        );
        self::assertStringContainsString(
            '<td>none</td>',
            $contents
        );
    }

    public function testNoRenderedMessages() : void
    {
        $this->makeLanguage();
        self::assertStringContainsString(
            'No message has been rendered',
            $this->collector->getContents()
        );
    }

    public function testFallbackName() : void
    {
        $collector = new class() extends LanguageCollector {
            public function getFallbackName(string $locale) : string
            {
                return parent::getFallbackName($locale);
            }
        };
        $language = $this->makeLanguage()->setDebugCollector($collector);
        self::assertSame('none', $collector->getFallbackName('en'));
        $language->setCurrentLocale('pt');
        self::assertSame('default', $collector->getFallbackName('en'));
        $language->setCurrentLocale('pt-br');
        self::assertSame('parent', $collector->getFallbackName('pt'));
        self::assertSame('', $collector->getFallbackName('fr'));
    }

    public function testActivities() : void
    {
        $language = $this->makeLanguage();
        self::assertEmpty($this->collector->getActivities());
        $language->render('foo', 'bar');
        self::assertSame(
            [
                'collector',
                'class',
                'description',
                'start',
                'end',
            ],
            \array_keys($this->collector->getActivities()[0]) // @phpstan-ignore-line
        );
    }
}
