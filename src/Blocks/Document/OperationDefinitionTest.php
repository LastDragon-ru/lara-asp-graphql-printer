<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Collector;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\TestSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
#[CoversClass(OperationDefinition::class)]
final class OperationDefinitionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    #[DataProvider('dataProviderSerialize')]
    public function testSerialize(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        OperationDefinitionNode $definition,
        (TypeNode&Node)|Type|null $type,
        ?Schema $schema,
    ): void {
        $collector = new Collector();
        $context   = new Context($settings, null, $schema);
        $actual    = (new OperationDefinition($context, $definition, $type))->serialize($collector, $level, $used);

        if ($expected !== '') {
            Parser::operationDefinition($actual);
        }

        self::assertSame($expected, $actual);
    }

    public function testStatistics(): void {
        $schema     = BuildSchema::build(
            <<<'GRAPHQL'
            type Query {
                field(a: Int): A
            }

            type A {
                a: String
                b: Boolean
            }
            GRAPHQL,
        );
        $context    = new Context(new TestSettings(), null, $schema);
        $collector  = new Collector();
        $definition = Parser::operationDefinition('query($a: Int) @a { field(a: $a) { a } }');
        $type       = $schema->getType('Query');
        $block      = new OperationDefinition($context, $definition, $type);
        $content    = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(
            [
                'Query'  => 'Query',
                'A'      => 'A',
                'String' => 'String',
                'Int'    => 'Int',
            ],
            $collector->getUsedTypes(),
        );
        self::assertEquals(['@a' => '@a'], $collector->getUsedDirectives());

        $astCollector = new Collector();
        $astBlock     = new OperationDefinition($context, Parser::operationDefinition($content), $type);

        self::assertEquals($content, $astBlock->serialize($astCollector, 0, 0));
        self::assertEquals($collector->getUsedTypes(), $astCollector->getUsedTypes());
        self::assertEquals($collector->getUsedDirectives(), $astCollector->getUsedDirectives());
    }

    public function testStatisticsNoSchema(): void {
        $context    = new Context(new TestSettings(), null, null);
        $collector  = new Collector();
        $definition = Parser::operationDefinition('query($a: Int) @a { field(a: $a) { a } }');
        $type       = null;
        $block      = new OperationDefinition($context, $definition, $type);
        $content    = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(['Int' => 'Int'], $collector->getUsedTypes());
        self::assertEquals(['@a' => '@a'], $collector->getUsedDirectives());

        $astCollector = new Collector();
        $astBlock     = new OperationDefinition($context, Parser::operationDefinition($content), $type);

        self::assertEquals($content, $astBlock->serialize($astCollector, 0, 0));
        self::assertEquals($collector->getUsedTypes(), $astCollector->getUsedTypes());
        self::assertEquals($collector->getUsedDirectives(), $astCollector->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{
     *      string,
     *      Settings,
     *      int,
     *      int,
     *      OperationDefinitionNode,
     *      (TypeNode&Node)|Type|null,
     *      ?Schema,
     *      }>
     */
    public static function dataProviderSerialize(): array {
        $settings = (new TestSettings())
            ->setPrintDirectives(false)
            ->setNormalizeFields(false)
            ->setNormalizeArguments(false)
            ->setAlwaysMultilineArguments(false);

        return [
            'without variables'               => [
                <<<'GRAPHQL'
                query test
                @a
                {
                    b
                    a
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                Parser::operationDefinition(
                    'query test @a { b a }',
                ),
                null,
                null,
            ],
            'with variables (short)'          => [
                <<<'GRAPHQL'
                mutation test($a: [String!] = ["aaaaaaaaaaaaaaaaaaaaaaaaaa"], $b: Int)
                @a
                {
                    b
                    a
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                Parser::operationDefinition(
                    'mutation test($a: [String!] = ["aaaaaaaaaaaaaaaaaaaaaaaaaa"], $b: Int) @a { b a }',
                ),
                null,
                null,
            ],
            'with variables (long)'           => [
                <<<'GRAPHQL'
                query test(
                    $a: [String!] = [
                        "aaaaaaaaaaaaaaaaaaaaaaaaaa"
                    ]
                    $b: Int
                )
                @a
                {
                    b
                    a
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true)
                    ->setLineLength(51),
                0,
                120,
                Parser::operationDefinition(
                    'query test($a: [String!] = ["aaaaaaaaaaaaaaaaaaaaaaaaaa"], $b: Int) @a { b a }',
                ),
                null,
                null,
            ],
            'normalized'                      => [
                <<<'GRAPHQL'
                query($a: String, $b: Int)
                @a
                {
                    a
                    b
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true)
                    ->setNormalizeFields(true)
                    ->setNormalizeArguments(true),
                0,
                0,
                Parser::operationDefinition(
                    'query($b: Int, $a: String) @a { a b }',
                ),
                null,
                null,
            ],
            'with variables always multiline' => [
                <<<'GRAPHQL'
                query(
                    $b: Int
                    $a: String
                ) {
                    a
                    b
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(false)
                    ->setAlwaysMultilineArguments(true),
                0,
                0,
                Parser::operationDefinition(
                    'query($b: Int, $a: String) @a { a b }',
                ),
                null,
                null,
            ],
            'indent'                          => [
                <<<'GRAPHQL'
                query test($b: Int, $a: String) {
                        a
                        b
                    }
                GRAPHQL,
                $settings,
                1,
                0,
                Parser::operationDefinition(
                    'query test($b: Int, $a: String) { a b }',
                ),
                null,
                null,
            ],
            'filter (no schema)'              => [
                <<<'GRAPHQL'
                query test($b: Int, $a: String)
                @a
                {
                    a
                    b
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true)
                    ->setTypeFilter(static fn () => false)
                    ->setDirectiveFilter(static function (string $directive): bool {
                        return $directive !== 'b';
                    }),
                0,
                0,
                Parser::operationDefinition(
                    'query test($b: Int, $a: String) @a @b { a b }',
                ),
                Type::int(),
                null,
            ],
            'filter'                          => [
                '',
                $settings
                    ->setTypeFilter(static fn () => false),
                0,
                0,
                Parser::operationDefinition(
                    'query test($b: Int, $a: String) @a @b { a b }',
                ),
                Parser::typeReference('Query'),
                BuildSchema::build(
                    <<<'GRAPHQL'
                    type A {
                        test: String
                    }
                    GRAPHQL,
                ),
            ],
            'filter: type'                    => [
                <<<'GRAPHQL'
                query test($a: String)
                @a
                {
                    a
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true)
                    ->setTypeFilter(static function (string $type): bool {
                        return $type !== 'Int';
                    }),
                0,
                0,
                Parser::operationDefinition(
                    'query test($b: Int, $a: String) @a(a: 123) { a b }',
                ),
                Parser::typeReference('Query'),
                BuildSchema::build(
                    <<<'GRAPHQL'
                    type Query {
                        a: A
                        b: Int
                    }

                    type A {
                        a: Int
                        b: String
                    }

                    directive @a(a: Int) on FIELD
                    GRAPHQL,
                ),
            ],
            'filter: operation'               => [
                <<<'GRAPHQL'
                query test($a: String)
                @a
                {
                    a
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true)
                    ->setTypeFilter(static function (string $type): bool {
                        return $type !== 'Int';
                    }),
                0,
                0,
                Parser::operationDefinition(
                    'query test($b: Int, $a: String) @a(a: 123) { a b }',
                ),
                null,
                BuildSchema::build(
                    <<<'GRAPHQL'
                    type Query {
                        a: A
                        b: Int
                    }

                    type A {
                        a: Int
                        b: String
                    }

                    directive @a(a: Int) on FIELD
                    GRAPHQL,
                ),
            ],
        ];
    }
    // </editor-fold>
}
