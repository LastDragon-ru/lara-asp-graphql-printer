<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document;

use GraphQL\Language\AST\EnumValueDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumValueDefinition as GraphQLEnumValueDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestSettings;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(EnumValueDefinition::class)]
class EnumValueDefinitionTest extends TestCase {
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
        EnumValueDefinitionNode|GraphQLEnumValueDefinition $type,
    ): void {
        $context = new Context($settings, null, null);
        $actual  = (string) (new EnumValueDefinition($context, $level, $used, $type));

        Parser::enumValueDefinition($actual);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string, Settings, int, int, EnumValueDefinitionNode|GraphQLEnumValueDefinition}>
     */
    public static function dataProviderToString(): array {
        $settings = new TestSettings();

        return [
            'value'                     => [
                <<<'STRING'
                A
                STRING,
                $settings,
                0,
                0,
                new GraphQLEnumValueDefinition([
                    'name'  => 'A',
                    'value' => 'A',
                ]),
            ],
            'indent'                    => [
                <<<'STRING'
                A
                STRING,
                $settings,
                1,
                0,
                new GraphQLEnumValueDefinition([
                    'name'  => 'A',
                    'value' => 'A',
                ]),
            ],
            'deprecationReason (empty)' => [
                <<<'STRING'
                A
                @deprecated
                STRING,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                new GraphQLEnumValueDefinition([
                    'name'              => 'A',
                    'value'             => 'A',
                    'deprecationReason' => '',
                ]),
            ],
            'deprecationReason'         => [
                <<<'STRING'
                A
                @deprecated(
                    reason: "test"
                )
                STRING,
                $settings
                    ->setPrintDirectives(true),
                0,
                0,
                new GraphQLEnumValueDefinition([
                    'name'              => 'A',
                    'value'             => 'A',
                    'deprecationReason' => 'test',
                    'astNode'           => Parser::enumValueDefinition(
                        'A @deprecated(reason: "should be ignored")',
                    ),
                ]),
            ],
            'ast'                       => [
                <<<'STRING'
                A
                STRING,
                $settings,
                0,
                0,
                Parser::enumValueDefinition('A'),
            ],
        ];
    }
    // </editor-fold>
}
