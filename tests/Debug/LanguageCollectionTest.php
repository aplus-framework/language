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

use Framework\Language\Debug\LanguageCollection;
use PHPUnit\Framework\TestCase;

final class LanguageCollectionTest extends TestCase
{
    protected LanguageCollection $collection;

    protected function setUp() : void
    {
        $this->collection = new LanguageCollection('Language');
    }

    public function testIcon() : void
    {
        self::assertStringStartsWith('<svg ', $this->collection->getIcon());
    }
}
