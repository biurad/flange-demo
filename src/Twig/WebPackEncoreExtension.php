<?php declare(strict_types=1);

/*
 * This file is part of RadePHP Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Biurad\UI\Html\HtmlElement;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Allows to use the Symfony Webpack Encore Bundle in Twig templates.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class WebPackEncoreExtension extends AbstractExtension
{
    private ?array $entryLookup = null;
    private array $loadedEntries = [];

    public function __construct(private string $entryPointFile, private Packages $packages, private ?CacheItemPoolInterface $cachePool = null)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encore_entry_js_files', [$this, 'getWebpackJsFiles']),
            new TwigFunction('encore_entry_css_files', [$this, 'getWebpackCssFiles']),
            new TwigFunction('encore_entry_script_tags', [$this, 'renderWebpackScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('encore_entry_link_tags', [$this, 'renderWebpackLinkTags'], ['is_safe' => ['html']]),
        ];
    }

    public function getWebpackJsFiles(string $entryName): array
    {
        return $this->getEntrypointLookup($entryName, 'js');
    }

    public function getWebpackCssFiles(string $entryName): array
    {
        return $this->getEntrypointLookup($entryName, 'css');
    }

    public function renderWebpackScriptTags(string $entryName, string $packageName = null, array $attributes = []): string
    {
        $loadedEntries = '';
        $entryFiles = $this->getEntrypointLookup($entryName, 'js', $integrityHashes);

        foreach ($entryFiles as $entryFile) {
            $attr = [];
            $attr['src'] = $this->packages->getUrl($entryFile, $packageName);

            if (isset($integrityHashes[$entryFile])) {
                $attr['integrity'] = $integrityHashes[$entryFile];
            }

            $loadedEntries .= \sprintf('<script %s></script>', HtmlElement::renderAttributes(\array_merge($attributes, $attr)));
        }

        return $loadedEntries;
    }

    public function renderWebpackLinkTags(string $entryName, string $packageName = null, array $attributes = []): string
    {
        $loadedEntries = '';
        $entryFiles = $this->getEntrypointLookup($entryName, 'css', $integrityHashes);

        foreach ($entryFiles as $entryFile) {
            $attr = [];
            $attr['rel'] = 'stylesheet';
            $attr['href'] = $this->packages->getUrl($entryFile, $packageName);

            if (isset($integrityHashes[$entryFile])) {
                $attr['integrity'] = $integrityHashes[$entryFile];
            }

            $loadedEntries .= \sprintf('<link %s>', HtmlElement::renderAttributes(\array_merge($attributes, $attr)));
        }

        return $loadedEntries;
    }

    private function getEntrypointLookup(string $entrypointName, string $type, array &$integrityHashes = null): array
    {
        if (null === $this->entryLookup) {
            if ($hasCache = null !== $this->cachePool) {
                $this->entryLookup = $this->cachePool->getItem('encore.entrypoint_lookup')->get();
            }

            if (null === $this->entryLookup) {
                $this->entryLookup = \json_decode(\file_get_contents($this->entryPointFile), true);

                if ($hasCache) {
                    $this->cachePool->save(
                        $this->cachePool->getItem('encore.entrypoint_lookup')
                            ->expiresAfter(\DateInterval::createFromDateString('1 day'))
                            ->set($this->entryLookup)
                    );
                }
            }
        }

        $entryData = $this->entryLookup['entrypoints'][$entrypointName] ?? [];
        $integrityHashes = $this->entryLookup['integrity'] ?? [];

        if (!isset($entryData[$type])) {
            return []; // If we don't find the file type then just send back nothing.
        }

        // make sure to not return the same file multiple times
        $entryFiles = $entryData[$type];
        $newFiles = \array_values(\array_diff($entryFiles, $this->loadedEntries));
        $this->loadedEntries = \array_merge($this->loadedEntries, $newFiles);

        return $newFiles;
    }
}
