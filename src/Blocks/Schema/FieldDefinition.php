<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Schema;

use GraphQL\Type\Definition\FieldDefinition as GraphQLFieldDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types\DefinitionBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\GraphQLDefinition;

/**
 * @internal
 *
 * @extends DefinitionBlock<GraphQLFieldDefinition>
 */
#[GraphQLDefinition(GraphQLFieldDefinition::class)]
class FieldDefinition extends DefinitionBlock {
    public function __construct(
        Settings $settings,
        int $level,
        int $used,
        GraphQLFieldDefinition $definition,
    ) {
        parent::__construct($settings, $level, $used, $definition);
    }

    protected function type(): string|null {
        return null;
    }

    protected function body(int $used): Block|string|null {
        $definition = $this->getDefinition();
        $space      = $this->space();
        $type       = $this->addUsed(
            new Type(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition->getType(),
            ),
        );
        $args       = $this->addUsed(
            new ArgumentsDefinition(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition->args,
            ),
        );

        return "{$args}:{$space}{$type}";
    }

    protected function fields(int $used): Block|string|null {
        return null;
    }
}
