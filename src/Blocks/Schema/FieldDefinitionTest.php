<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Schema;

use GraphQL\Language\Parser;
use GraphQL\Type\Definition\FieldDefinition as GraphQLFieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestSettings;

/**
 * @internal
 * @covers \LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Schema\FieldDefinition
 * @covers \LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Schema\ArgumentsDefinition
 */
class FieldDefinitionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderToString
     */
    public function testToString(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        GraphQLFieldDefinition $definition,
    ): void {
        $actual = (string) (new FieldDefinition($settings, $level, $used, $definition));

        Parser::fieldDefinition($actual);

        self::assertEquals($expected, $actual);
    }

    public function testStatistics(): void {
        $settings   = new TestSettings();
        $definition = GraphQLFieldDefinition::create([
            'name'    => 'A',
            'type'    => new NonNull(
                new ObjectType([
                    'name' => 'A',
                ]),
            ),
            'astNode' => Parser::fieldDefinition('a: A @a'),
        ]);
        $block      = new FieldDefinition($settings, 0, 0, $definition);

        self::assertNotEmpty((string) $block);
        self::assertEquals(['A' => 'A'], $block->getUsedTypes());
        self::assertEquals(['@a' => '@a'], $block->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, GraphQLFieldDefinition}>
     */
    public static function dataProviderToString(): array {
        $settings = (new TestSettings())
            ->setNormalizeArguments(false)
            ->setAlwaysMultilineArguments(false);

        return [
            'without args'               => [
                <<<'STRING'
                """
                Description
                """
                test: Test!
                @a
                STRING,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                GraphQLFieldDefinition::create([
                    'name'        => 'test',
                    'type'        => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'astNode'     => Parser::fieldDefinition('test: Test! @a'),
                    'description' => 'Description',
                ]),
            ],
            'with args (short)'          => [
                <<<'STRING'
                """
                Description
                """
                test(a: [String!] = ["aaaaaaaaaaaaaaaaaaaaaaaaaa"], b: Int): Test!
                STRING,
                $settings,
                0,
                0,
                GraphQLFieldDefinition::create([
                    'name'        => 'test',
                    'type'        => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args'        => [
                        'a' => [
                            'type'         => new ListOfType(new NonNull(Type::string())),
                            'defaultValue' => [
                                'aaaaaaaaaaaaaaaaaaaaaaaaaa',
                            ],
                        ],
                        'b' => [
                            'type' => Type::int(),
                        ],
                    ],
                    'description' => 'Description',
                ]),
            ],
            'with args (long)'           => [
                <<<'STRING'
                test(
                    b: Int

                    """
                    Description
                    """
                    a: String! = "aaaaaaaaaaaaaaaaaaaaaaaaaa"
                ): Test!
                STRING,
                $settings,
                0,
                0,
                GraphQLFieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type'         => new NonNull(Type::string()),
                            'description'  => 'Description',
                            'defaultValue' => 'aaaaaaaaaaaaaaaaaaaaaaaaaa',
                        ],
                    ],
                ]),
            ],
            'with args normalized'       => [
                <<<'STRING'
                test(a: String, b: Int): Test!
                STRING,
                $settings->setNormalizeArguments(true),
                0,
                0,
                GraphQLFieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
            'with args always multiline' => [
                <<<'STRING'
                test(
                    b: Int
                    a: String
                ): Test!
                STRING,
                $settings->setAlwaysMultilineArguments(true),
                0,
                0,
                GraphQLFieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
            'indent'                     => [
                <<<'STRING'
                test(
                        a: String
                        b: Int
                    ): Test!
                STRING,
                $settings->setNormalizeArguments(true),
                1,
                120,
                GraphQLFieldDefinition::create([
                    'name' => 'test',
                    'type' => new NonNull(
                        new ObjectType([
                            'name' => 'Test',
                        ]),
                    ),
                    'args' => [
                        'b' => [
                            'type' => Type::int(),
                        ],
                        'a' => [
                            'type' => Type::string(),
                        ],
                    ],
                ]),
            ],
        ];
    }
    // </editor-fold>
}
