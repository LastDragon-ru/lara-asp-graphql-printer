<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Collector;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\TestSettings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(EnumTypeDefinition::class)]
#[CoversClass(EnumValuesDefinition::class)]
class EnumTypeDefinitionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSerialize
     */
    public function testSerialize(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        EnumTypeDefinitionNode|EnumType $type,
    ): void {
        $collector = new Collector();
        $context   = new Context($settings, null, null);
        $actual    = (new EnumTypeDefinition($context, $type))->serialize($collector, $level, $used);

        if ($expected) {
            Parser::enumTypeDefinition($actual);
        }

        self::assertEquals($expected, $actual);
    }

    public function testStatistics(): void {
        $context    = new Context(new TestSettings(), null, null);
        $collector  = new Collector();
        $definition = new EnumType([
            'name'    => 'Test',
            'values'  => ['A'],
            'astNode' => Parser::enumTypeDefinition(
                'enum Test @a { A }',
            ),
        ]);
        $block      = new EnumTypeDefinition($context, $definition);
        $content    = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(['Test' => 'Test'], $collector->getUsedTypes());
        self::assertEquals(['@a' => '@a'], $collector->getUsedDirectives());

        $astBlock     = new EnumTypeDefinition($context, Parser::enumTypeDefinition($content));
        $astCollector = new Collector();

        self::assertEquals($content, $astBlock->serialize($astCollector, 0, 0));
        self::assertEquals($collector->getUsedTypes(), $astCollector->getUsedTypes());
        self::assertEquals($collector->getUsedDirectives(), $astCollector->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, EnumTypeDefinitionNode|EnumType}>
     */
    public static function dataProviderSerialize(): array {
        $settings = (new TestSettings())
            ->setNormalizeEnums(false);

        return [
            'enum'         => [
                <<<'STRING'
                enum Test {
                    C
                    B
                    A
                }
                STRING,
                $settings,
                0,
                0,
                new EnumType([
                    'name'   => 'Test',
                    'values' => ['C', 'B', 'A'],
                ]),
            ],
            'indent'       => [
                <<<'STRING'
                enum Test {
                        C
                        B
                        A
                    }
                STRING,
                $settings,
                1,
                0,
                new EnumType([
                    'name'   => 'Test',
                    'values' => ['C', 'B', 'A'],
                ]),
            ],
            'normalized'   => [
                <<<'STRING'
                enum Test {
                    A
                    B
                    C
                }
                STRING,
                $settings->setNormalizeEnums(true),
                0,
                0,
                new EnumType([
                    'name'   => 'Test',
                    'values' => ['C', 'B', 'A'],
                ]),
            ],
            'directives'   => [
                <<<'STRING'
                enum Test
                @a
                @b
                @c
                {
                    A
                }
                STRING,
                $settings,
                0,
                0,
                new EnumType([
                    'name'              => 'Test',
                    'values'            => ['A'],
                    'astNode'           => Parser::enumTypeDefinition(
                        <<<'STRING'
                        enum Test @a { A }
                        STRING,
                    ),
                    'extensionASTNodes' => [
                        Parser::enumTypeExtension('extend enum Test @b'),
                        Parser::enumTypeExtension('extend enum Test @c'),
                    ],
                ]),
            ],
            'filter'       => [
                '',
                $settings
                    ->setTypeDefinitionFilter(static fn () => false),
                0,
                0,
                new EnumType([
                    'name'   => 'Test',
                    'values' => ['A'],
                ]),
            ],
            'ast'          => [
                <<<'STRING'
                enum Test
                @a
                {
                    A
                }
                STRING,
                $settings,
                0,
                0,
                Parser::enumTypeDefinition(
                    'enum Test @a { A }',
                ),
            ],
            'ast + filter' => [
                '',
                $settings
                    ->setTypeDefinitionFilter(static fn () => false),
                0,
                0,
                Parser::enumTypeDefinition(
                    'enum Test @a { A }',
                ),
            ],
        ];
    }
    // </editor-fold>
}
