<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types;

use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SchemaDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document\Directives;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\NamedBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;

use function is_string;
use function mb_strlen;
use function property_exists;

// @phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @internal
 *
 * @template TDefinition of Node|Type|FieldDefinition|EnumValueDefinition|Argument|Directive|InputObjectField|Schema|SchemaDefinitionNode
 */
abstract class DefinitionBlock extends Block implements NamedBlock {
    /**
     * @param TDefinition $definition
     */
    public function __construct(
        Context $context,
        int $level,
        int $used,
        private Node|Type|FieldDefinition|EnumValueDefinition|Argument|Directive|InputObjectField|Schema|SchemaDefinitionNode $definition,
    ) {
        parent::__construct($context, $level, $used);
    }

    public function getName(): string {
        $name   = $this->name();
        $prefix = $this->prefix();

        if ($prefix && $name) {
            $space = $this->space();
            $name  = "{$prefix}{$space}{$name}";
        } elseif ($prefix) {
            $name = $prefix;
        } else {
            // empty
        }

        return $name;
    }

    /**
     * @return TDefinition
     */
    protected function getDefinition(
        // empty
    ): Node|Type|FieldDefinition|EnumValueDefinition|Argument|Directive|InputObjectField|Schema {
        return $this->definition;
    }

    protected function content(int $level, int $used): string {
        // Allowed?
        if (!$this->isDefinitionAllowed()) {
            return '';
        }

        // Prepare
        $eol       = $this->eol();
        $space     = $this->space();
        $indent    = $this->indent($level);
        $content   = '';
        $used      = $used + mb_strlen($indent) + mb_strlen($content);
        $multiline = $this->isStringMultiline($content);

        // Description
        $description = $this->addUsed($this->description($level, $used))?->serialize($level, $used);

        if ($description) {
            $content .= "{$description}{$eol}{$indent}";
            $used     = mb_strlen($indent); // because new line has started
        }

        // Name
        $name     = $this->getName();
        $content .= $name;
        $used    += mb_strlen($name);

        // Arguments
        $arguments = $this->addUsed($this->arguments($level, $used, $multiline));

        if ($arguments && !$arguments->isEmpty()) {
            $multiline = $multiline || $arguments->isMultiline();
            $content  .= $arguments->serialize($level, $used);
            $used     += $arguments->getLength();
        }

        // Type
        $prefix = ":{$space}";
        $type   = $this->addUsed($this->type($level, $used + mb_strlen($prefix), $multiline));

        if ($type && !$type->isEmpty()) {
            $multiline = $multiline || $type->isMultiline();
            $content  .= "{$prefix}{$type->serialize($level, $used)}";
            $used     += $type->getLength() + mb_strlen($prefix);
        }

        // Value
        $prefix = "{$space}={$space}";
        $value  = $this->addUsed($this->value($level, $used + mb_strlen($prefix), $multiline));

        if ($value && !$value->isEmpty()) {
            $multiline = $multiline || $value->isMultiline();
            $content  .= "{$prefix}{$value->serialize($level, $used)}";
            $used     += $value->getLength() + mb_strlen($prefix);
        }

        // Body
        $prefix = $space;
        $body   = $this->addUsed($this->body($level, $used + mb_strlen($prefix), $multiline));

        if ($body && !$body->isEmpty()) {
            if ($multiline || ($body instanceof UsageList && $body->isMultiline())) {
                $multiline = true;
                $content  .= "{$eol}{$indent}{$body->serialize($level, $used)}";
                $used      = mb_strlen($indent); // because new line has started
            } else {
                $multiline = $body->isMultiline();
                $content  .= "{$prefix}{$body->serialize($level, $used)}";
                $used     += $body->getUsed() + mb_strlen($prefix);
            }
        }

        // Directives
        $directives = $this->getSettings()->isPrintDirectives()
            ? $this->addUsed($this->directives($level, $used, $multiline))
            : null;

        if ($directives && !$directives->isEmpty()) {
            $multiline = true;
            $content  .= "{$eol}{$indent}{$directives->serialize($level, $used)}";
            $used      = mb_strlen($indent); // because new line has started
        }

        // Fields
        $prefix = $space;
        $fields = $this->addUsed($this->fields($level, $used + mb_strlen($prefix), $multiline));

        if ($fields && !$fields->isEmpty()) {
            if ($multiline || ($directives && !$directives->isEmpty())) {
                // $multiline = true;
                $content .= "{$eol}{$indent}{$fields->serialize($level, $used)}";
                // $used      = mb_strlen($indent); // because new line has started
            } else {
                // $multiline = $fields->isMultiline();
                $content .= "{$prefix}{$fields->serialize($level, $used)}";
                // $used     += $fields->getUsed() + mb_strlen($prefix);
            }
        }

        // Return
        return $content;
    }

    protected function prefix(): ?string {
        return null;
    }

    public function name(): string {
        $definition = $this->getDefinition();
        $name       = '';

        if ($definition instanceof NamedType) {
            $name = $definition->name();
        } elseif (property_exists($definition, 'name')) {
            if ($definition->name instanceof NameNode) {
                $name = $definition->name->value;
            } elseif (is_string($definition->name)) {
                $name = $definition->name;
            } else {
                // empty
            }
        } elseif ($definition instanceof VariableDefinitionNode) {
            $name = $definition->variable->name->value;
        } else {
            // empty
        }

        return $name;
    }

    protected function arguments(int $level, int $used, bool $multiline): ?Block {
        return null;
    }

    protected function type(int $level, int $used, bool $multiline): ?Block {
        return null;
    }

    protected function value(int $level, int $used, bool $multiline): ?Block {
        return null;
    }

    protected function body(int $level, int $used, bool $multiline): ?Block {
        return null;
    }

    protected function fields(int $level, int $used, bool $multiline): ?Block {
        return null;
    }

    protected function directives(int $level, int $used, bool $multiline): ?Block {
        $definition = $this->getDefinition();
        $directives = new Directives(
            $this->getContext(),
            $level,
            $used,
            $this->getDefinitionDirectives(),
            $definition->deprecationReason ?? null,
        );

        return $directives;
    }

    protected function description(int $level, int $used): ?Block {
        // Description
        $definition  = $this->getDefinition();
        $description = null;

        if ($definition instanceof Schema) {
            // It is part of October2021 spec but not yet supported
            // https://github.com/webonyx/graphql-php/issues/1027
        } elseif ($definition instanceof NamedType) {
            $description = $definition->description();
        } elseif (property_exists($definition, 'description')) {
            if ($definition->description instanceof StringValueNode) {
                $description = $definition->description->value;
            } elseif (is_string($definition->description)) {
                $description = $definition->description;
            } else {
                // empty
            }
        } else {
            // empty
        }

        // Return
        return new DescriptionBlock(
            $this->getContext(),
            $level,
            $used,
            $description,
        );
    }

    protected function isDefinitionAllowed(): bool {
        $definition = $this->getDefinition();
        $allowed    = match (true) {
            $definition instanceof TypeDefinitionNode && $definition instanceof Node,
            $definition instanceof Type
                => $this->isTypeDefinitionAllowed($definition),
            $definition instanceof TypeExtensionNode
                => $this->isTypeDefinitionAllowed($definition->getName()->value),
            $definition instanceof DirectiveDefinitionNode
                => $this->isDirectiveDefinitionAllowed($definition->name->value),
            $definition instanceof Directive
                => $this->isDirectiveDefinitionAllowed($definition->name),
            default
                => true,
        };

        return $allowed;
    }

    /**
     * @return NodeList<DirectiveNode>
     */
    protected function getDefinitionDirectives(): NodeList {
        // Prepare
        $directives = new NodeList([]);
        $definition = $this->getDefinition();

        // Unfortunately directives exists only in AST :(
        // https://github.com/webonyx/graphql-php/issues/588
        $astNode = null;

        if ($definition instanceof Node) {
            $astNode = $definition;
        } elseif (property_exists($definition, 'astNode')) {
            $astNode = $definition->astNode;
        } else {
            // empty
        }

        if ($astNode) {
            $directives = $directives->merge($astNode->directives ?? []);
        }

        // Extensions nodes can also add directives
        $astExtensionNodes = property_exists($definition, 'extensionASTNodes')
            ? $definition->extensionASTNodes
            : [];

        foreach ($astExtensionNodes ?: [] as $astExtensionNode) {
            $directives = $directives->merge($astExtensionNode->directives ?? []);
        }

        // Return
        return $directives;
    }
}
