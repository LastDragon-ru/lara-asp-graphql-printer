<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestSettings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(Type::class)]
class TypeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSerialize
     *
     * @param (TypeNode&Node)|(GraphQLType&(OutputType|InputType)) $type
     */
    public function testSerialize(
        string $expected,
        Settings $settings,
        int $level,
        int $used,
        TypeNode|GraphQLType $type,
        ?Schema $schema,
    ): void {
        $context = new Context($settings, null, $schema);
        $actual  = (new Type($context, $level, $used, $type))->serialize($level, $used);

        self::assertEquals($expected, $actual);
    }

    public function testStatistics(): void {
        $node    = new NonNull(
            new ObjectType([
                'name'   => 'Test',
                'fields' => [
                    'field' => [
                        'type' => GraphQLType::string(),
                    ],
                ],
            ]),
        );
        $context = new Context(new TestSettings(), null, null);
        $block   = new Type($context, 0, 0, $node);
        $type    = $node->getInnermostType()->name();
        $content = $block->serialize(0, 0);

        self::assertNotEmpty($content);
        self::assertEquals([$type => $type], $block->getUsedTypes());
        self::assertEquals([], $block->getUsedDirectives());

        $ast = new Type($context, 0, 0, Parser::typeReference($content));

        self::assertEquals($block->getUsedTypes(), $ast->getUsedTypes());
        self::assertEquals($block->getUsedDirectives(), $ast->getUsedDirectives());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string,Settings,int,int,(TypeNode&Node)|(GraphQLType&(OutputType|InputType)),?Schema}>
     */
    public static function dataProviderSerialize(): array {
        $settings = new TestSettings();
        $type     = new ObjectType([
            'name'   => 'Test',
            'fields' => [
                'field' => [
                    'type' => GraphQLType::string(),
                ],
            ],
        ]);

        return [
            'type: object'            => [
                'Test',
                $settings,
                0,
                0,
                $type,
                null,
            ],
            'type: non null'          => [
                'Test!',
                $settings,
                0,
                0,
                new NonNull($type),
                null,
            ],
            'type: non null list'     => [
                '[Test]!',
                $settings,
                0,
                0,
                new NonNull(new ListOfType($type)),
                null,
            ],
            'filter (no schema)'      => [
                'Test',
                $settings
                    ->setTypeFilter(static fn () => false),
                0,
                0,
                $type,
                null,
            ],
            'filter'                  => [
                '',
                $settings
                    ->setTypeFilter(static fn () => false),
                0,
                0,
                new NonNull(new ListOfType($type)),
                BuildSchema::build(
                    <<<'STRING'
                    scalar Test
                    STRING,
                ),
            ],
            'ast: object'             => [
                'Test',
                $settings,
                0,
                0,
                Parser::typeReference('Test'),
                null,
            ],
            'ast: non null'           => [
                'Test!',
                $settings,
                0,
                0,
                Parser::typeReference('Test!'),
                null,
            ],
            'ast: non null list'      => [
                '[Test]!',
                $settings,
                0,
                0,
                Parser::typeReference('[Test]!'),
                null,
            ],
            'ast: filter (no schema)' => [
                '[Test]!',
                $settings
                    ->setTypeFilter(static fn () => false),
                0,
                0,
                Parser::typeReference('[Test]!'),
                null,
            ],
            'ast: filter'             => [
                '',
                $settings
                    ->setTypeFilter(static fn () => false),
                0,
                0,
                Parser::typeReference('[Test]!'),
                BuildSchema::build(
                    <<<'STRING'
                    scalar Test
                    STRING,
                ),
            ],
        ];
    }
    // </editor-fold>
}
