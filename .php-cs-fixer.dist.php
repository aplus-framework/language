<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Language Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Framework\CodingStandard\Config;
use Framework\CodingStandard\Finder;

return (new Config())->setDefaultHeaderComment(
    'Aplus Framework Language Library',
    'Natan Felles <natanfelles@gmail.com>'
)->replaceRules([
    'non_printable_character' => false,
])->setFinder(
    Finder::create()->in(__DIR__)
);
