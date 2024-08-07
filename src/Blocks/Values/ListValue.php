<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Values;

use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\Type;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document\Value;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\ListBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;
use Override;

/**
 * @internal
 * @extends ListBlock<Value, array-key, ValueNode&Node>
 */
class ListValue extends ListBlock {
    public function __construct(
        Context $context,
        ListValueNode $definition,
        private (TypeNode&Node)|Type|null $type,
    ) {
        parent::__construct($context, $definition->values);
    }

    #[Override]
    protected function getPrefix(): string {
        return '[';
    }

    #[Override]
    protected function getSuffix(): string {
        return ']';
    }

    #[Override]
    protected function getEmptyValue(): string {
        return "{$this->getPrefix()}{$this->getSuffix()}";
    }

    #[Override]
    protected function block(string|int $key, mixed $item): Block {
        return new Value($this->getContext(), $item, $this->type);
    }
}
