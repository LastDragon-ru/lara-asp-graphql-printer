<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
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
#[CoversClass(ObjectTypeDefinition::class)]
final class ObjectTypeDefinitionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    #[DataProvider('dataProviderSerialize')]
    public function testSerialize(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        ObjectTypeDefinitionNode|ObjectType $definition,
    ): void {
        $collector = new Collector();
        $context   = new Context($settings, null, null);
        $actual    = (new ObjectTypeDefinition($context, $definition))->serialize($collector, $level, $used);

        if ($expected !== '') {
            Parser::objectTypeDefinition($actual);
        }

        self::assertSame($expected, $actual);
    }

    public function testStatistics(): void {
        $context    = new Context(new TestSettings(), null, null);
        $collector  = new Collector();
        $definition = new ObjectType([
            'name'       => 'A',
            'fields'     => [
                'b' => [
                    'name'    => 'b',
                    'type'    => new ObjectType([
                        'name'   => 'B',
                        'fields' => [
                            'field' => [
                                'type' => Type::string(),
                            ],
                        ],
                    ]),
                    'args'    => [
                        'c' => [
                            'type'    => new ObjectType([
                                'name'   => 'C',
                                'fields' => [
                                    'field' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                            'astNode' => Parser::inputValueDefinition('c: C @c'),
                        ],
                    ],
                    'astNode' => Parser::fieldDefinition('b: B @b'),
                ],
            ],
            'interfaces' => [
                new InterfaceType([
                    'name'   => 'D',
                    'fields' => [
                        'field' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
            'astNode'    => Parser::objectTypeDefinition('type A @a'),
        ]);
        $block      = new ObjectTypeDefinition($context, $definition);
        $content    = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'], $collector->getUsedTypes());
        self::assertEquals(['@a' => '@a', '@b' => '@b', '@c' => '@c'], $collector->getUsedDirectives());

        $astCollector = new Collector();
        $astBlock     = new ObjectTypeDefinition($context, Parser::objectTypeDefinition($content));

        self::assertEquals($content, $astBlock->serialize($astCollector, 0, 0));
        self::assertEquals($collector->getUsedTypes(), $astCollector->getUsedTypes());
        self::assertEquals($collector->getUsedDirectives(), $astCollector->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, ObjectTypeDefinitionNode|ObjectType}>
     */
    public static function dataProviderSerialize(): array {
        $settings = (new TestSettings())
            ->setNormalizeFields(false)
            ->setNormalizeInterfaces(false)
            ->setAlwaysMultilineArguments(false)
            ->setAlwaysMultilineInterfaces(false);

        return [
            'description + directives'                    => [
                <<<'GRAPHQL'
                """
                Description
                """
                type Test
                @a
                @b
                @c
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                new ObjectType([
                    'name'              => 'Test',
                    'fields'            => [],
                    'astNode'           => Parser::objectTypeDefinition('type Test @a'),
                    'description'       => 'Description',
                    'extensionASTNodes' => [
                        Parser::objectTypeExtension('extend type Test @b'),
                        Parser::objectTypeExtension('extend type Test @c'),
                    ],
                ]),
            ],
            'description + directives + fields'           => [
                <<<'GRAPHQL'
                """
                Description
                """
                type Test
                @a
                {
                    c: C

                    """
                    Description
                    """
                    b(b: Int): B

                    a(a: Int): A
                }
                GRAPHQL,
                $settings->setPrintDirectives(true),
                0,
                0,
                new ObjectType([
                    'name'        => 'Test',
                    'astNode'     => Parser::objectTypeDefinition('type Test @a'),
                    'description' => 'Description',
                    'fields'      => [
                        [
                            'name' => 'c',
                            'type' => new ObjectType([
                                'name'   => 'C',
                                'fields' => [
                                    'field' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                        ],
                        [
                            'name'        => 'b',
                            'type'        => new ObjectType([
                                'name'   => 'B',
                                'fields' => [
                                    'field' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                            'args'        => [
                                'b' => [
                                    'type' => Type::int(),
                                ],
                            ],
                            'description' => 'Description',
                        ],
                        [
                            'name' => 'a',
                            'type' => new ObjectType([
                                'name'   => 'A',
                                'fields' => [
                                    'field' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                            'args' => [
                                'a' => [
                                    'type' => Type::int(),
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            'fields'                                      => [
                <<<'GRAPHQL'
                type Test {
                    a: String
                }
                GRAPHQL,
                $settings,
                0,
                0,
                new ObjectType([
                    'name'   => 'Test',
                    'fields' => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
            'implements + directives + fields'            => [
                <<<'GRAPHQL'
                type Test implements B & A
                @a
                {
                    a: String
                }
                GRAPHQL,
                $settings->setPrintDirectives(true),
                0,
                0,
                new ObjectType([
                    'name'       => 'Test',
                    'astNode'    => Parser::objectTypeDefinition('type Test @a'),
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                        new InterfaceType([
                            'name'   => 'A',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'implements(multiline) + directives + fields' => [
                <<<'GRAPHQL'
                type Test
                implements
                    & B
                    & A
                @a
                {
                    a: String
                }
                GRAPHQL,
                $settings->setPrintDirectives(true),
                0,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'astNode'    => Parser::objectTypeDefinition('type Test @a'),
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                        new InterfaceType([
                            'name'   => 'A',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'implements(multiline) + fields'              => [
                <<<'GRAPHQL'
                type Test
                implements
                    & B
                    & A
                {
                    a: String
                }
                GRAPHQL,
                $settings,
                0,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                        new InterfaceType([
                            'name'   => 'A',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'implements + fields'                         => [
                <<<'GRAPHQL'
                type Test implements B & A {
                    a: String
                }
                GRAPHQL,
                $settings,
                0,
                0,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                        new InterfaceType([
                            'name'   => 'A',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'implements(normalized) + fields'             => [
                <<<'GRAPHQL'
                type Test
                implements
                    & A
                    & B
                {
                    a: String
                }
                GRAPHQL,
                $settings->setNormalizeInterfaces(true),
                0,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                        new InterfaceType([
                            'name'   => 'A',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'indent'                                      => [
                <<<'GRAPHQL'
                type Test
                    implements
                        & A
                        & B
                    {
                        a: String
                    }
                GRAPHQL,
                $settings->setNormalizeInterfaces(true),
                1,
                120,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                        new InterfaceType([
                            'name'   => 'A',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'implements always multiline'                 => [
                <<<'GRAPHQL'
                type Test
                implements
                    & B
                {
                    a: String
                }
                GRAPHQL,
                $settings
                    ->setAlwaysMultilineInterfaces(true),
                0,
                0,
                new ObjectType([
                    'name'       => 'Test',
                    'fields'     => [
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                    'interfaces' => [
                        new InterfaceType([
                            'name'   => 'B',
                            'fields' => [
                                'field' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ]),
            ],
            'args always multiline'                       => [
                <<<'GRAPHQL'
                type Test {
                    """
                    Description
                    """
                    b(
                        b: Int
                    ): B

                    a(
                        a: Int
                    ): A
                }
                GRAPHQL,
                $settings->setAlwaysMultilineArguments(true),
                0,
                0,
                new ObjectType([
                    'name'   => 'Test',
                    'fields' => [
                        'b' => [
                            'type'        => new ObjectType([
                                'name'   => 'B',
                                'fields' => [
                                    'field' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                            'args'        => [
                                'b' => [
                                    'type' => Type::int(),
                                ],
                            ],
                            'description' => 'Description',
                        ],
                        'a' => [
                            'type' => new ObjectType([
                                'name'   => 'A',
                                'fields' => [
                                    'field' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                            'args' => [
                                'a' => [
                                    'type' => Type::int(),
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
            'filter'                                      => [
                '',
                $settings
                    ->setTypeDefinitionFilter(static fn () => false),
                0,
                0,
                new ObjectType([
                    'name'   => 'Test',
                    'fields' => [],
                ]),
            ],
            'ast'                                         => [
                <<<'GRAPHQL'
                """
                Description
                """
                type Test implements B & A
                @a
                {
                    a: String
                }
                GRAPHQL,
                $settings
                    ->setPrintDirectives(true)
                    ->setDirectiveFilter(static function (string $directive): bool {
                        return $directive !== 'b';
                    }),
                0,
                0,
                Parser::objectTypeDefinition(
                    '"Description" type Test implements B & A @a @b { a: String }',
                ),
            ],
            'ast + filter'                                => [
                '',
                $settings
                    ->setTypeDefinitionFilter(static fn () => false),
                0,
                0,
                Parser::objectTypeDefinition(
                    'type Test { a: String }',
                ),
            ],
        ];
    }
    // </editor-fold>
}
