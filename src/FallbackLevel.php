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

/**
 * Enum FallbackLevel.
 *
 * @package language
 */
enum FallbackLevel : int
{
    /**
     * Disable fallback.
     *
     * Use language lines only from the given language.
     */
    case none = 0;
    /**
     * Fallback to parent language.
     *
     * If the given language is pt-BR and a line is not found, try to use the line of pt.
     *
     * NOTE: The parent locale must be set in the Supported Locales to this fallback work.
     */
    case parent = 1;
    /**
     * Fallback to default language.
     *
     * If the parent language is not found, try to use the default language.
     */
    case default = 2;
}
