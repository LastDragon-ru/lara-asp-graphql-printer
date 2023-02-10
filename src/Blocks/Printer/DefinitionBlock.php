<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Printer;

use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Definitions\DirectiveDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Definitions\EnumTypeDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Definitions\InputObjectTypeDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Definitions\InterfaceTypeDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Definitions\ObjectTypeDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\NamedBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types\ScalarTypeDefinitionBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types\SchemaDefinitionBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types\UnionTypeDefinitionBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Exceptions\TypeUnsupported;

/**
 * @internal
 */
class DefinitionBlock extends Block implements NamedBlock {
    private Block $block;

    public function __construct(
        Settings $settings,
        int $level,
        private Schema|Type|Directive $definition,
    ) {
        parent::__construct($settings, $level);

        $this->block = $this->getDefinitionBlock($definition);
    }

    public function getName(): string {
        $name  = '';
        $block = $this->getBlock();

        if ($block instanceof NamedBlock) {
            $name = $block->getName();
        }

        return $name;
    }

    public function getDefinition(): Type|Schema|Directive {
        return $this->definition;
    }

    protected function getBlock(): Block {
        return $this->block;
    }

    protected function content(): string {
        return (string) $this->addUsed($this->getBlock());
    }

    protected function getDefinitionBlock(Schema|Type|Directive $definition): Block {
        $block = null;

        if ($definition instanceof ObjectType) {
            $block = new ObjectTypeDefinition(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof InputObjectType) {
            $block = new InputObjectTypeDefinition(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof ScalarType) {
            $block = new ScalarTypeDefinitionBlock(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof InterfaceType) {
            $block = new InterfaceTypeDefinition(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof UnionType) {
            $block = new UnionTypeDefinitionBlock(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof EnumType) {
            $block = new EnumTypeDefinition(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof Directive) {
            $block = new DirectiveDefinition(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } elseif ($definition instanceof Schema) {
            $block = new SchemaDefinitionBlock(
                $this->getSettings(),
                $this->getLevel(),
                $this->getUsed(),
                $definition,
            );
        } else {
            throw new TypeUnsupported($definition);
        }

        return $block;
    }
}
