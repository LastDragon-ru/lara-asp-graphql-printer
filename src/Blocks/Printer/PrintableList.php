<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Printer;

use ArrayAccess;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\ListBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Collector;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;

/**
 * @internal
 * @extends ListBlock<Block, array-key, Block>
 * @implements ArrayAccess<array-key, Block>
 */
class PrintableList extends ListBlock implements ArrayAccess {
    /**
     * @var array<array-key, Block>
     */
    private array $blocks = [];

    public function __construct(
        Context $context,
        protected bool $root = false,
        protected bool $eof = true,
    ) {
        parent::__construct($context, []);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function isRoot(): bool {
        return $this->root;
    }
    // </editor-fold>

    // <editor-fold desc="Settings">
    // =========================================================================
    protected function isWrapped(): bool {
        return true;
    }

    protected function isNormalized(): bool {
        return $this->getSettings()->isNormalizeSchema();
    }

    protected function isAlwaysMultiline(): bool {
        return true;
    }
    // </editor-fold>

    // <editor-fold desc="Content">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getItems(): iterable {
        return $this->blocks;
    }

    protected function content(Collector $collector, int $level, int $used): string {
        $content = parent::content($collector, $level, $used);

        if ($content && $this->isRoot()) {
            $content = "{$this->indent($level)}{$content}";

            if ($this->eof) {
                $content .= $this->getSettings()->getFileEnd();
            }
        }

        return $content;
    }

    protected function block(int|string $key, mixed $item): Block {
        return $item;
    }
    // </editor-fold>

    // <editor-fold desc="ArrayAccess">
    // =========================================================================
    /**
     * @param array-key $offset
     */
    public function offsetExists(mixed $offset): bool {
        return isset($this->blocks[$offset]);
    }

    /**
     * @param array-key $offset
     */
    public function offsetGet(mixed $offset): Block {
        return $this->blocks[$offset];
    }

    /**
     * @param array-key|null $offset
     * @param Block          $value
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        if ($offset !== null) {
            $this->blocks[$offset] = $value;
        } else {
            $this->blocks[] = $value;
        }

        parent::reset();
    }

    /**
     * @param array-key $offset
     */
    public function offsetUnset(mixed $offset): void {
        unset($this->blocks[$offset]);

        parent::reset();
    }
    // </editor-fold>
}
