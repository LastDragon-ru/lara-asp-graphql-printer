<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types;

use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Language\AST\TypeSystemExtensionNode;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document\DirectiveDefinition;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Document\Directives;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\NamedBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Collector;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;
use Override;

use function is_iterable;
use function is_string;
use function json_encode;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;
use function property_exists;

/**
 * @internal
 *
 * @template TDefinition of Node|Type|FieldDefinition|EnumValueDefinition|Argument|Directive|InputObjectField|Schema
 */
abstract class DefinitionBlock extends Block implements NamedBlock {
    /**
     * @param TDefinition $definition
     */
    public function __construct(
        Context $context,
        private Node|Type|FieldDefinition|EnumValueDefinition|Argument|Directive|InputObjectField|Schema $definition,
    ) {
        parent::__construct($context);
    }

    #[Override]
    public function getName(): string {
        $name   = $this->name();
        $prefix = (string) $this->prefix();

        if ($prefix !== '' && $name !== '') {
            $space = $this->space();
            $name  = "{$prefix}{$space}{$name}";
        } elseif ($prefix !== '') {
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

    #[Override]
    protected function content(Collector $collector, int $level, int $used): string {
        // Allowed?
        if (!$this->isDefinitionAllowed()) {
            return '';
        }

        // Prepare
        $eol          = $this->eol();
        $space        = $this->space();
        $spaceLength  = mb_strlen($space);
        $indent       = $this->indent($level);
        $indentLength = mb_strlen($indent);
        $content      = '';
        $used         = $used + $indentLength + mb_strlen($content);
        $multiline    = $this->isStringMultiline($content);

        // Description
        $description = $this->description()?->serialize($collector, $level, $used);

        if ($description !== null && $description !== '') {
            $content .= "{$description}{$eol}{$indent}";
            $used     = $indentLength; // because new line has started
        }

        // Name
        $name     = $this->getName();
        $content .= $name;
        $used    += mb_strlen($name);

        // Arguments
        $arguments = $this->arguments($multiline);

        if ($arguments !== null) {
            $serialized = $arguments->serialize($collector, $level, $used);
            $content   .= $serialized;

            if ($this->isStringMultiline($serialized)) {
                $multiline = true;
                $used      = $this->getLastLineLength($serialized);
            } else {
                $used += mb_strlen($serialized);
            }
        }

        // Type
        $prefix = ":{$space}";
        $type   = $this->type($multiline);

        if ($type !== null) {
            $serialized = "{$prefix}{$type->serialize($collector, $level, $used + mb_strlen($prefix))}";
            $content   .= $serialized;

            if ($this->isStringMultiline($serialized)) {
                $multiline = true;
                $used      = $this->getLastLineLength($serialized);
            } else {
                $used += mb_strlen($serialized);
            }
        }

        // Value
        $prefix = "{$space}={$space}";
        $value  = $this->value($multiline);

        if ($value !== null) {
            $serialized = "{$prefix}{$value->serialize($collector, $level, $used + mb_strlen($prefix))}";
            $content   .= $serialized;

            if ($this->isStringMultiline($serialized)) {
                $multiline = true;
                $used      = $this->getLastLineLength($serialized);
            } else {
                $used += mb_strlen($serialized);
            }
        }

        // Body
        $body       = $this->body($multiline);
        $serialized = (string) $body?->serialize($collector, $level, $used + $spaceLength);
        $hasBody    = $body !== null && $serialized !== '';

        if ($hasBody) {
            if ($multiline || ($body instanceof UsageList && $this->isStringMultiline($serialized))) {
                $multiline = true;
                $content  .= "{$eol}{$indent}{$serialized}";
                $used      = $indentLength; // because new line has started
            } elseif ($this->isStringMultiline($serialized)) {
                $multiline = true;
                $content  .= "{$space}{$serialized}";
                $used      = $this->getLastLineLength($serialized);
            } else {
                $content .= "{$space}{$serialized}";
                $used    += mb_strlen($serialized) + $spaceLength;
            }
        }

        // Directives
        $directives    = $this->getSettings()->isPrintDirectives()
            ? $this->directives($multiline)
            : null;
        $serialized    = (string) $directives?->serialize($collector, $level, $indentLength);
        $hasDirectives = $directives !== null && $serialized !== '';

        if ($hasDirectives) {
            $multiline = true;
            $content  .= "{$eol}{$indent}{$serialized}";
            $used      = $indentLength; // because new line has started
        }

        // Fields
        $prefix     = $space;
        $fields     = $this->fields($multiline);
        $serialized = (string) $fields?->serialize($collector, $level, $used);

        if ($fields !== null && $serialized !== '') {
            if ($multiline && ($hasBody || $hasDirectives)) {
                $content .= "{$eol}{$indent}{$serialized}";
            } else {
                $content .= "{$prefix}{$serialized}";
            }
        }

        // Statistics
        if (!($this instanceof ExtensionDefinitionBlock) && !($this instanceof ExecutableDefinitionBlock)) {
            $name = $this->name();

            if ($name !== '') {
                if ($this instanceof DirectiveDefinition) {
                    $collector->addUsedDirective($name);
                } elseif ($this instanceof TypeDefinitionBlock) {
                    $collector->addUsedType($name);
                } else {
                    // empty
                }
            }
        }

        // Return
        return $content;
    }

    protected function prefix(): ?string {
        return null;
    }

    protected function name(): string {
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

    protected function arguments(bool $multiline): ?Block {
        return null;
    }

    protected function type(bool $multiline): ?Block {
        return null;
    }

    protected function value(bool $multiline): ?Block {
        return null;
    }

    protected function body(bool $multiline): ?Block {
        return null;
    }

    protected function fields(bool $multiline): ?Block {
        return null;
    }

    protected function directives(bool $multiline): ?Block {
        return new Directives($this->getContext(), $this->getDefinitionDirectives());
    }

    protected function description(): ?Block {
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
        /** @var NodeList<DirectiveNode> $directives */
        $directives = new NodeList([]);
        $definition = $this->getDefinition();

        // Unfortunately, directives exist only in AST :(
        // https://github.com/webonyx/graphql-php/issues/588
        $astNode = null;

        if ($definition instanceof Node) {
            $astNode = $definition;
        } elseif (property_exists($definition, 'astNode') && $definition->astNode instanceof Node) {
            $astNode = $definition->astNode;
        } else {
            // empty
        }

        if ($astNode !== null && property_exists($astNode, 'directives') && is_iterable($astNode->directives)) {
            foreach ($astNode->directives as $directive) {
                if ($directive instanceof DirectiveNode) {
                    $directives[] = $directive;
                }
            }
        }

        // Extensions nodes can also add directives
        $astExtensionNodes = property_exists($definition, 'extensionASTNodes') && is_iterable($definition->extensionASTNodes)
            ? $definition->extensionASTNodes
            : [];

        foreach ($astExtensionNodes as $astExtensionNode) {
            if (
                $astExtensionNode instanceof TypeSystemExtensionNode
                && property_exists($astExtensionNode, 'directives')
                && is_iterable($astExtensionNode->directives)
            ) {
                foreach ($astExtensionNode->directives as $directive) {
                    if ($directive instanceof DirectiveNode) {
                        $directives[] = $directive;
                    }
                }
            }
        }

        // Some directives converted into type/object property
        if (property_exists($definition, 'deprecationReason') && is_string($definition->deprecationReason)) {
            $deprecatedName      = Directive::DEPRECATED_NAME;
            $deprecatedReason    = $definition->deprecationReason;
            $deprecatedDirective = null;

            // todo(graphql): Is there a better way to create directive node?
            if ($deprecatedReason !== Directive::DEFAULT_DEPRECATION_REASON && $deprecatedReason !== '') {
                $deprecatedAttr      = Directive::REASON_ARGUMENT_NAME;
                $deprecatedReason    = json_encode($deprecatedReason);
                $deprecatedDirective = Parser::directive("@{$deprecatedName}({$deprecatedAttr}: {$deprecatedReason})");
            } else {
                $deprecatedDirective = Parser::directive("@{$deprecatedName}");
            }

            foreach ($directives as $key => $directive) {
                if ($directive->name->value === $deprecatedName) {
                    $directives[$key]    = $deprecatedDirective;
                    $deprecatedDirective = null;
                    break;
                }
            }

            if ($deprecatedDirective !== null) {
                $directives[] = $deprecatedDirective;
            }
        }

        // Return
        return $directives;
    }

    private function getLastLineLength(string $string): int {
        $eol    = $this->eol();
        $index  = mb_strrpos($string, $eol, -1);
        $length = $index !== false
            ? mb_strlen(mb_substr($string, $index + 1))
            : mb_strlen($string);

        return $length;
    }
}
