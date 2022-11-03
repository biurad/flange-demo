<?php declare(strict_types=1);

$header = <<<'EOF'
This file is part of Flange Blog Demo Project

@copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
@license   https://opensource.org/licenses/MIT License

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__)
            ->append([__FILE__])
            ->exclude(['vendor', 'node_modules', 'var'])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    )
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP80Migration:risky' => true,
        '@PHPUnit84Migration:risky' => true,
        'protected_to_private' => false,
        'single_import_per_statement' => false,
        'blank_line_after_opening_tag' => false,
        'linebreak_after_opening_tag' => false,
        'single_class_element_per_statement' => false,
        'single_trait_insert_per_statement' => false,
        //'group_import' => true,
        'native_constant_invocation' => true,
        'comment_to_phpdoc' => true,
        'strict_param' => true,
        'no_unset_on_property' => true,
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => false],
        'ordered_imports' => ['imports_order' => ['class', 'const', 'function'], 'sort_algorithm' => 'alpha'],
        'header_comment' => ['header' => $header],
        'native_function_invocation' => [
            'include' => ['@compiler_optimized', '@internal'],
            'strict' => true,
        ],
        'general_phpdoc_tag_rename' => [
            'replacements' => [
                'inheritDocs' => 'inheritdoc',
                'inheritDoc' => 'inheritdoc',
            ],
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'do',
                'for',
                'foreach',
                'if',
                'return',
                'switch',
                'throw',
                'try',
                'while',
            ],
        ],
    ])
;
