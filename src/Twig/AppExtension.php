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

use Flight\Routing\RouteUri as GeneratedUri;
use Flight\Routing\Interfaces\UrlGeneratorInterface;
use Rade\DI\Attribute\Inject;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * See https://symfony.com/doc/current/templating/twig_extension.html.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AppExtension extends AbstractExtension
{
    private array $localeCodes;
    private ?array $locales = null;

    // The $locales argument is injected thanks to the service container.
    public function __construct(
        #[Inject('enabled_locales', Inject::PARAMETER)] string $localeCodes,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $localeCodes = \explode('|', $localeCodes);
        \sort($localeCodes);

        $this->localeCodes = $localeCodes;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('locales', [$this, 'getLocales']),
            new TwigFunction('path', [$this, 'generateUri'], ['is_safe' => ['all'], 'needs_context' => true]),
        ];
    }

    /**
     * Generates a path for a given route.
     */
    public function generateUri(
        array $context,
        string $routeName,
        array $parameters = [],
        int $referenceType = GeneratedUri::ABSOLUTE_PATH
    ): GeneratedUri {
        if (isset($context['app'])) {
            $parameters += ['_locale' => $context['app']->getRequest()->getLocale()];
        }

        return $this->urlGenerator->generateUri($routeName, $parameters, $referenceType);
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.).
     */
    public function getLocales(): array
    {
        if (null !== $this->locales) {
            return $this->locales;
        }

        $this->locales = [];

        foreach ($this->localeCodes as $localeCode) {
            $this->locales[] = ['code' => $localeCode, 'name' => Locales::getName($localeCode, $localeCode)];
        }

        return $this->locales;
    }
}
