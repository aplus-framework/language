<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Language Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Language\Debug;

use Framework\Debug\Collector;
use Framework\Language\Language;

/**
 * Class LanguageCollector.
 *
 * @package language
 */
class LanguageCollector extends Collector
{
    protected Language $language;

    public function setLanguage(Language $language) : static
    {
        $this->language = $language;
        return $this;
    }

    public function getActivities() : array
    {
        $activities = [];
        foreach ($this->getData() as $index => $data) {
            $activities[] = [
                'collector' => $this->getName(),
                'class' => static::class,
                'description' => 'Render message ' . ($index + 1),
                'start' => $data['start'],
                'end' => $data['end'],
            ];
        }
        return $activities;
    }

    public function getContents() : string
    {
        if ( ! isset($this->language)) {
            return '<p>A Language instance has not been set on this collector.</p>';
        }
        \ob_start(); ?>
        <p><strong>Default Locale:</strong> <?=
            \htmlentities($this->language->getDefaultLocale())
        ?></p>
        <p><strong>Current Locale:</strong> <?=
        \htmlentities($this->language->getCurrentLocale())
        ?></p>
        <p><strong>Supported Locales:</strong> <?=
        \htmlentities(\implode(', ', $this->language->getSupportedLocales()))
        ?></p>
        <p><strong>Fallback Level:</strong> <?php
        $level = $this->language->getFallbackLevel();
        echo "{$level->value} ({$level->name})"; ?></p>
        <h1>Rendered Messages</h1>
        <?= $this->renderRenderedMessages() ?>
        <h1>Directories</h1>
        <?= $this->renderDirectories() ?>
        <h1>Available Messages</h1>
        <?php
        echo $this->renderLines();
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderRenderedMessages() : string
    {
        if ( ! $this->hasData()) {
            return '<p>No message has been rendered.</p>';
        }
        $count = \count($this->getData());
        \ob_start(); ?>
        <p><?= $count ?> message<?= $count === 1 ? '' : 's' ?> has been rendered.</p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Line</th>
                <th>Message</th>
                <th>Locale</th>
                <th title="Seconds">Time</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->getData() as $index => $data): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= \htmlentities($data['file']) ?></td>
                    <td><?= \htmlentities($data['line']) ?></td>
                    <td>
                        <pre><code class="language-html"><?= \htmlentities($data['message']) ?></code></pre>
                    </td>
                    <td><?= \htmlentities($data['locale']) ?></td>
                    <td><?= \round($data['end'] - $data['start'], 6) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderDirectories() : string
    {
        $directories = $this->language->getDirectories();
        if (empty($directories)) {
            return '<p>No directory set for this Language instance.</p>';
        }
        $count = \count($directories);
        \ob_start(); ?>
        <p>There <?= $count === 1 ? 'is 1 directory' : "are {$count} directories" ?> set.</p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Directory</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->language->getDirectories() as $index => $directory): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= \htmlentities($directory) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderLines() : string
    {
        $lines = $this->getLines();
        if (empty($lines)) {
            return '<p>No file lines available for this Language instance.</p>';
        }
        $count = \count($lines);
        \ob_start(); ?>
        <p>There <?= $count === 1 ? 'is 1 message line' : "are {$count} message lines"
        ?> available to the current locale (<?= $this->language->getCurrentLocale() ?>).
        </p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Line</th>
                <th>Message Pattern</th>
                <th>Locale</th>
                <th>Fallback</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lines as $index => $line): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= \htmlentities($line['file']) ?></td>
                    <td><?= \htmlentities($line['line']) ?></td>
                    <td>
                        <pre><code class="language-icu-message-format"><?= \htmlentities($line['message']) ?></code></pre>
                    </td>
                    <td><?= \htmlentities($line['locale']) ?></td>
                    <td><?= \htmlentities($line['fallback']) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    /**
     * @return array<int,array<string,string>>
     */
    protected function getLines() : array
    {
        $allLines = $this->language->getLines();
        $this->language->resetLines();
        $files = [];
        foreach ($this->language->getDirectories() as $directory) {
            foreach ((array) \glob($directory . '*/*.php') as $file) {
                $file = (string) $file;
                $pos = \strrpos($file, \DIRECTORY_SEPARATOR);
                $file = \substr($file, $pos + 1, -4);
                $files[$file] = true;
            }
        }
        $files = \array_keys($files);
        foreach ($files as $file) {
            $this->language->render($file, '.·*·.');
        }
        $result = [];
        foreach ($this->language->getLines() as $locale => $lines) {
            \ksort($lines);
            foreach ($lines as $file => $messages) {
                \ksort($messages);
                foreach ($messages as $line => $message) {
                    foreach ($result as $data) {
                        if ($data['file'] === $file && $data['line'] === $line) {
                            continue 2;
                        }
                    }
                    $result[] = [
                        'file' => $file,
                        'line' => $line,
                        'message' => $message,
                        'locale' => $locale,
                        'fallback' => $this->getFallbackName($locale),
                    ];
                }
            }
        }
        \usort($result, static function ($str1, $str2) {
            $cmp = \strcmp($str1['file'], $str2['file']);
            if ($cmp === 0) {
                $cmp = \strcmp($str1['line'], $str2['line']);
            }
            return $cmp;
        });
        foreach ($allLines as $locale => $lines) {
            foreach ($lines as $file => $messages) {
                $this->language->addLines($locale, $file, $messages);
            }
        }
        return $result;
    }

    protected function getFallbackName(string $locale) : string
    {
        $currentLocale = $this->language->getCurrentLocale();
        if ($locale === $currentLocale) {
            return 'none';
        }
        $parentLocale = \explode('-', $currentLocale)[0];
        if ($locale === $parentLocale) {
            return 'parent';
        }
        if ($locale === $this->language->getDefaultLocale()) {
            return 'default';
        }
        return '';
    }
}
