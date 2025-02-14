<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
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

use function assert;

/**
 * @internal
 */
#[CoversClass(Value::class)]
final class ValueTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    #[DataProvider('dataProviderSerialize')]
    public function testSerialize(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        ValueNode&Node $node,
        ?Schema $schema,
        ?Type $type,
    ): void {
        $collector = new Collector();
        $context   = new Context($settings, null, $schema);
        $actual    = (new Value($context, $node, $type))->serialize($collector, $level, $used);
        $parsed    = null;

        if ($expected !== '') {
            $parsed = Parser::valueLiteral($actual);
        }

        self::assertSame($expected, $actual);

        if ($parsed !== null && !$settings->isNormalizeArguments() && $settings->getTypeFilter() === null) {
            self::assertSame(
                Printer::doPrint($node),
                Printer::doPrint($parsed),
            );
        }
    }

    public function testStatistics(): void {
        $collector = new Collector();
        $context   = new Context(new TestSettings(), null, null);
        $literal   = Parser::valueLiteral('123');
        $block     = new Value($context, $literal, Type::int());
        $content   = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(['Int' => 'Int'], $collector->getUsedTypes());
        self::assertEquals([], $collector->getUsedDirectives());
    }

    public function testStatisticsObjectValue(): void {
        $schema    = BuildSchema::build(
            <<<'GRAPHQL'
            type A {
                a: Int
                b: Boolean
                c: C
            }

            type B {
                b: String
            }

            type C {
                a: Int
                b: B!
            }
            GRAPHQL,
        );
        $context   = new Context(new TestSettings(), null, $schema);
        $collector = new Collector();
        $literal   = Parser::valueLiteral('{ a: 123, b: true, c: { a: 123, b: { b: "b" } } }');
        $block     = new Value($context, $literal, $schema->getType('A'));
        $content   = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(
            [
                'Int'     => 'Int',
                'String'  => 'String',
                'Boolean' => 'Boolean',
                'A'       => 'A',
                'B'       => 'B',
                'C'       => 'C',
            ],
            $collector->getUsedTypes(),
        );
        self::assertEmpty($collector->getUsedDirectives());
    }

    public function testStatisticsObjectValueNoSchema(): void {
        $collector = new Collector();
        $context   = new Context(new TestSettings(), null, null);
        $literal   = Parser::valueLiteral('{ a: 123, b: true, c: { a: 123, b: { b: "b" } } }');
        $block     = new Value(
            $context,
            $literal,
            new InputObjectType([
                'name'   => 'A',
                'fields' => [
                    // empty
                ],
            ]),
        );
        $content = $block->serialize($collector, 0, 0);

        self::assertNotEmpty($content);
        self::assertEquals(
            [
                'A' => 'A',
            ],
            $collector->getUsedTypes(),
        );
        self::assertEmpty($collector->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, ValueNode&Node, ?Schema, ?Type}>
     */
    public static function dataProviderSerialize(): array {
        $settings = (new TestSettings())
            ->setNormalizeArguments(false);
        $object   = new InputObjectType([
            'name'   => 'A',
            'fields' => [
                'a' => [
                    'type' => Type::string(),
                ],
                'b' => [
                    'type' => Type::int(),
                ],
                'o' => [
                    'type' => static function () use (&$object): InputObjectType {
                        assert($object !== null);

                        return $object;
                    },
                ],
            ],
        ]);

        return [
            NullValueNode::class                                  => [
                'null',
                $settings,
                0,
                0,
                Parser::valueLiteral('null'),
                null,
                null,
            ],
            IntValueNode::class                                   => [
                '123',
                $settings,
                0,
                0,
                Parser::valueLiteral('123'),
                null,
                null,
            ],
            FloatValueNode::class                                 => [
                '123.45',
                $settings,
                0,
                0,
                Parser::valueLiteral('123.45'),
                null,
                null,
            ],
            BooleanValueNode::class                               => [
                'true',
                $settings,
                0,
                0,
                Parser::valueLiteral('true'),
                null,
                null,
            ],
            StringValueNode::class                                => [
                '"true"',
                $settings,
                0,
                0,
                Parser::valueLiteral('"true"'),
                null,
                null,
            ],
            EnumValueNode::class                                  => [
                'Value',
                $settings,
                0,
                0,
                Parser::valueLiteral('Value'),
                null,
                null,
            ],
            VariableNode::class                                   => [
                '$variable',
                $settings,
                0,
                0,
                Parser::valueLiteral('$variable'),
                null,
                null,
            ],
            ListValueNode::class.' (short)'                       => [
                '["a", "b", "c"]',
                $settings,
                0,
                0,
                Parser::valueLiteral('["a", "b", "c"]'),
                null,
                null,
            ],
            ListValueNode::class.' (with block string)'           => [
                <<<'STRING'
                [
                    "string"
                    """
                    Block
                        string
                    """
                ]
                STRING,
                $settings,
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    [
                        "string"
                        """
                        Block
                            string
                        """
                    ]
                    STRING,
                ),
                null,
                null,
            ],
            ListValueNode::class.' (with block string and level)' => [
                <<<'STRING'
                [
                        "string"
                        """
                        Block
                            string
                        """
                    ]
                STRING,
                $settings,
                1,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    [
                        "string"
                        """
                        Block
                            string
                        """
                    ]
                    STRING,
                ),
                null,
                null,
            ],
            ListValueNode::class.' (empty)'                       => [
                <<<'STRING'
                []
                STRING,
                $settings,
                1,
                0,
                Parser::valueLiteral('[]'),
                null,
                null,
            ],
            ObjectValueNode::class                                => [
                <<<'STRING'
                {
                    object: {
                        a: "a"
                        b: "b"
                    }
                }
                STRING,
                $settings,
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    {
                        object: {
                            a: "a"
                            b: "b"
                        }
                    }
                    STRING,
                ),
                null,
                null,
            ],
            ObjectValueNode::class.' (empty)'                     => [
                <<<'STRING'
                {}
                STRING,
                $settings,
                0,
                0,
                Parser::valueLiteral('{}'),
                null,
                null,
            ],
            ObjectValueNode::class.' (normalized)'                => [
                <<<'STRING'
                {
                    a: "a"
                    b: "b"
                }
                STRING,
                $settings
                    ->setNormalizeArguments(true),
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    {
                        b: "b"
                        a: "a"
                    }
                    STRING,
                ),
                null,
                null,
            ],
            ObjectValueNode::class.' (one line)'                  => [
                <<<'STRING'
                {object: {a: "a", b: "b"}}
                STRING,
                $settings
                    ->setAlwaysMultilineArguments(false),
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    {
                        object: {
                            a: "a"
                            b: "b"
                        }
                    }
                    STRING,
                ),
                null,
                null,
            ],
            'all'                                                 => [
                <<<'STRING'
                {
                    int: 123
                    bool: true
                    string: "string"
                    blockString: """
                        Block
                            string
                        """
                    array: [1, 2, 3]
                    object: {
                        a: "a"
                        b: {
                            b: null
                            array: [3]
                            nested: {
                                a: 123
                            }
                        }
                    }
                }
                STRING,
                $settings,
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                {
                    int: 123
                    bool: true
                    string: "string"
                    blockString: """
                        Block
                            string
                        """
                    array: [
                        1
                        2
                        3
                    ]
                    object: {
                        a: "a"
                        b: {
                            b: null
                            array: [
                                3
                            ]
                            nested: {
                                a: 123
                            }
                        }
                    }
                }
                STRING,
                ),
                null,
                null,
            ],
            'filter: '.ObjectValueNode::class                     => [
                <<<'STRING'
                {
                    b: 123
                    o: {
                        b: 123
                        o: {
                            b: 123
                        }
                    }
                }
                STRING,
                $settings
                    ->setTypeFilter(static function (string $type): bool {
                        return $type !== 'String';
                    }),
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    {
                        b: 123
                        a: "a"
                        o: {
                            a: "a"
                            b: 123
                            o: {
                                b: 123
                                a: "a"
                            }
                        }
                    }
                    STRING,
                ),
                new Schema([
                    'query' => new ObjectType([
                        'name'   => 'Query',
                        'fields' => [
                            'test' => [
                                'type' => $object,
                            ],
                        ],
                    ]),
                ]),
                $object,
            ],
            'filter: '.ObjectValueNode::class.' (no schema)'      => [
                <<<'STRING'
                {
                    b: 123
                    a: "a"
                    o: {
                        a: true
                        b: 123
                        o: {
                            b: 123
                            a: "a"
                        }
                    }
                }
                STRING,
                $settings
                    ->setTypeFilter(static function (string $type): bool {
                        return $type !== 'String';
                    }),
                0,
                0,
                Parser::valueLiteral(
                    <<<'STRING'
                    {
                        b: 123
                        a: "a"
                        o: {
                            a: true
                            b: 123
                            o: {
                                b: 123
                                a: "a"
                            }
                        }
                    }
                    STRING,
                ),
                null,
                null,
            ],
        ];
    }
    // </editor-fold>
}
