<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\InterfaceTypeExtensionNode;
use GraphQL\Language\Parser;
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
#[CoversClass(InterfaceTypeExtension::class)]
final class InterfaceTypeExtensionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    #[DataProvider('dataProviderSerialize')]
    public function testSerialize(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        InterfaceTypeExtensionNode $definition,
        ?Schema $schema,
    ): void {
        $collector = new Collector();
        $context   = new Context($settings, null, $schema);
        $actual    = (new InterfaceTypeExtension($context, $definition))->serialize($collector, $level, $used);

        if ($expected !== '') {
            Parser::interfaceTypeExtension($actual);
        }

        self::assertSame($expected, $actual);
    }

    public function testStatistics(): void {
        $context    = new Context(new TestSettings(), null, null);
        $collector  = new Collector();
        $definition = Parser::interfaceTypeExtension(
            <<<'GRAPHQL'
            extend interface Test implements B & A @a {
                a: String
            }
            GRAPHQL,
        );
        $block      = new InterfaceTypeExtension($context, $definition);
        $content    = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(['B' => 'B', 'A' => 'A', 'String' => 'String'], $collector->getUsedTypes());
        self::assertEquals(['@a' => '@a'], $collector->getUsedDirectives());

        $astCollector = new Collector();
        $astBlock     = new InterfaceTypeExtension($context, Parser::interfaceTypeExtension($content));

        self::assertEquals($content, $astBlock->serialize($astCollector, 0, 0));
        self::assertEquals($collector->getUsedTypes(), $astCollector->getUsedTypes());
        self::assertEquals($collector->getUsedDirectives(), $astCollector->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, InterfaceTypeExtensionNode, ?Schema}>
     */
    public static function dataProviderSerialize(): array {
        $settings = (new TestSettings())
            ->setNormalizeFields(false)
            ->setNormalizeInterfaces(false)
            ->setAlwaysMultilineArguments(false)
            ->setAlwaysMultilineInterfaces(false);

        return [
            'directives'                                  => [
                <<<'GRAPHQL'
                extend interface Test
                @a
                @b
                @c
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                Parser::interfaceTypeExtension(
                    'extend interface Test @a @b @c',
                ),
                null,
            ],
            'fields'                                      => [
                <<<'GRAPHQL'
                extend interface Test {
                    """
                    Description
                    """
                    a(a: Int): A

                    b: B
                }
                GRAPHQL,
                $settings
                    ->setNormalizeFields(true),
                0,
                0,
                Parser::interfaceTypeExtension(
                    <<<'GRAPHQL'
                    extend interface Test {
                        b: B

                        "Description"
                        a(a: Int): A
                    }
                    GRAPHQL,
                ),
                null,
            ],
            'implements'                                  => [
                <<<'GRAPHQL'
                extend interface Test implements A & B
                GRAPHQL,
                $settings
                    ->setNormalizeInterfaces(true),
                0,
                0,
                Parser::interfaceTypeExtension(
                    'extend interface Test implements B & A',
                ),
                null,
            ],
            'implements(multiline) + directives + fields' => [
                <<<'GRAPHQL'
                extend interface Test
                implements
                    & B
                    & A
                @a
                {
                    a: String
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true),
                0,
                120,
                Parser::interfaceTypeExtension(
                    <<<'GRAPHQL'
                    extend interface Test implements B & A @a {
                        a: String
                    }
                    GRAPHQL,
                ),
                null,
            ],
            'indent'                                      => [
                <<<'GRAPHQL'
                extend interface Test implements B & A
                    @a
                    {
                        a: String
                    }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true),
                1,
                0,
                Parser::interfaceTypeExtension(
                    <<<'GRAPHQL'
                    extend interface Test implements B & A @a {
                        a: String
                    }
                    GRAPHQL,
                ),
                null,
            ],
            'implements always multiline'                 => [
                <<<'GRAPHQL'
                extend interface Test
                implements
                    & B
                GRAPHQL,
                $settings
                    ->setAlwaysMultilineInterfaces(true),
                0,
                0,
                Parser::interfaceTypeExtension(
                    'extend interface Test implements B',
                ),
                null,
            ],
            'filter: definition'                          => [
                '',
                $settings
                    ->setTypeDefinitionFilter(static fn () => false),
                0,
                0,
                Parser::interfaceTypeExtension(
                    'extend interface Test implements B',
                ),
                null,
            ],
            'filter (no schema)'                          => [
                <<<'GRAPHQL'
                extend interface Test implements B & A
                @a
                {
                    a: A
                    b: [B!]
                }
                GRAPHQL,
                $settings
                    ->setTypeFilter(static function (string $type): bool {
                        return $type !== 'B';
                    })
                    ->setDirectiveFilter(static function (string $directive): bool {
                        return $directive !== 'b';
                    }),
                0,
                0,
                Parser::interfaceTypeExtension(
                    <<<'GRAPHQL'
                    extend interface Test implements B & A @a @b {
                        a: A
                        b: [B!]
                    }
                    GRAPHQL,
                ),
                null,
            ],
            'filter'                                      => [
                <<<'GRAPHQL'
                extend interface Test implements A
                @a
                {
                    a: A
                }
                GRAPHQL,
                $settings
                    ->setTypeFilter(static function (string $type): bool {
                        return $type !== 'B';
                    })
                    ->setDirectiveFilter(static function (string $directive): bool {
                        return $directive !== 'b';
                    }),
                0,
                0,
                Parser::interfaceTypeExtension(
                    <<<'GRAPHQL'
                    extend interface Test implements B & A @a @b {
                        a: A
                        b: [B!]
                    }
                    GRAPHQL,
                ),
                BuildSchema::build(
                    <<<'GRAPHQL'
                    scalar A
                    scalar B
                    GRAPHQL,
                ),
            ],
        ];
    }
    // </editor-fold>
}
