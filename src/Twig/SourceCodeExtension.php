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

use Twig\Extension\AbstractExtension;
use Twig\{Environment, TemplateWrapper, TwigFunction};
use function Symfony\Component\String\u;

/**
 * CAUTION: this is an extremely advanced Twig extension. It's used to get the
 * source code of the controller and the template used to render the current
 * page. If you are starting with Symfony, don't look at this code and consider
 * studying instead the code of the src/Twig/AppExtension.php extension.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class SourceCodeExtension extends AbstractExtension
{
    private ?\ReflectionFunctionAbstract $controller = null;

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('show_source_code', [$this, 'showSourceCode'], ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true]),
        ];
    }

    /**
     * @param string|TemplateWrapper|array $template
     */
    public function showSourceCode(Environment $twig, array $context, $template): string
    {
        $this->controller = $context['_route_reflection'] ?? null;

        return $twig->render('debug/source_code.html.twig', [
            'controller' => $this->getController(),
            'template' => $this->getTemplateSource($twig->resolveTemplate($template)),
        ]);
    }

    private function getController(): ?array
    {
        // this happens for example for exceptions (404 errors, etc.)
        if (null === $method = $this->controller) {
            return null;
        }

        $classCode = \file($method->getFileName());
        $methodCode = \array_slice($classCode, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
        $controllerCode = '    ' . $method->getDocComment() . "\n" . \implode('', $methodCode);

        return [
            'file_path' => $method->getFileName(),
            'starting_line' => $method->getStartLine(),
            'source_code' => $this->unindentCode($controllerCode),
        ];
    }

    private function getTemplateSource(TemplateWrapper $template): array
    {
        $templateSource = $template->getSourceContext();

        return [
            // Twig templates are not always stored in files (they can be stored
            // in a database for example). However, for the needs of the Symfony
            // Demo app, we consider that all templates are stored in files and
            // that their file paths can be obtained through the source context.
            'file_path' => $p = $templateSource->getPath(),
            'starting_line' => 1,
            'source_code' => $templateSource->getCode() ?: \file_get_contents($p),
        ];
    }

    /**
     * Utility method that "unindents" the given $code when all its lines start
     * with a tabulation of four white spaces.
     */
    private function unindentCode(string $code): string
    {
        $codeLines = u($code)->split("\n");

        $indentedOrBlankLines = \array_filter($codeLines, static function ($lineOfCode) {
            return u((string) $lineOfCode)->isEmpty() || u((string) $lineOfCode)->startsWith('    ');
        });

        $codeIsIndented = \count($indentedOrBlankLines) === \count($codeLines);

        if ($codeIsIndented) {
            $unindentedLines = \array_map(static function ($lineOfCode) {
                return u((string) $lineOfCode)->after('    ');
            }, $codeLines);
            $code = u("\n")->join($unindentedLines)->toString();
        }

        return $code;
    }
}
