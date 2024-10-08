<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Misc;

use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\Directive as GraphQLDirective;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\DirectiveResolver;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Exceptions\DirectiveArgumentNotFound;
use LastDragon_ru\LaraASP\GraphQLPrinter\Exceptions\DirectiveDefinitionNotFound;
use LastDragon_ru\LaraASP\GraphQLPrinter\Exceptions\FieldArgumentNotFound;
use LastDragon_ru\LaraASP\GraphQLPrinter\Exceptions\FieldNotFound;
use LastDragon_ru\LaraASP\GraphQLPrinter\Exceptions\TypeNotFound;

use function array_key_exists;
use function array_merge;
use function assert;

/**
 * @internal
 */
class Context {
    /**
     * @var array<string, bool>
     */
    private array $allowedTypes = [];

    /**
     * @var array<string, bool>
     */
    private array $allowedTypesDefinitions = [];

    /**
     * @var array<string, bool>
     */
    private array $allowedDirectives = [];

    /**
     * @var array<string, bool>
     */
    private array $allowedDirectivesDefinitions = [];

    public function __construct(
        protected Settings $settings,
        protected ?DirectiveResolver $directiveResolver,
        protected ?Schema $schema,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function getSettings(): Settings {
        return $this->settings;
    }

    public function getDirectiveResolver(): ?DirectiveResolver {
        return $this->directiveResolver;
    }

    public function getSchema(): ?Schema {
        return $this->schema;
    }
    // </editor-fold>

    // <editor-fold desc="Schema">
    // =========================================================================
    /**
     * @return array{
     *      query: string,
     *      mutation: string,
     *      subscription: string,
     *      }
     */
    public function getOperationsDefaultTypes(): array {
        return [
            'query'        => 'Query',
            'mutation'     => 'Mutation',
            'subscription' => 'Subscription',
        ];
    }

    public function getOperationType(string $operation): (Type&NamedType)|null {
        $type = $this->getSchema()?->getOperationType($operation);

        if ($type === null && $this->getSchema() !== null) {
            throw new TypeNotFound($operation);
        }

        return $type;
    }
    // </editor-fold>

    // <editor-fold desc="Types">
    // =========================================================================
    /**
     * @return array<array-key, Type&NamedType>
     */
    public function getTypes(): array {
        return (array) $this->getSchema()?->getTypeMap();
    }

    public function getType(string $name): (Type&NamedType)|null {
        $type = $this->getSchema()?->getType($name);

        if ($type === null && $this->getSchema() !== null) {
            throw new TypeNotFound($name);
        }

        return $type;
    }

    public function isTypeAllowed(string $type): bool {
        // Schema?
        if ($this->getSchema() === null) {
            return true;
        }

        // Cached?
        if (isset($this->allowedTypes[$type])) {
            return $this->allowedTypes[$type];
        }

        // Allowed?
        $isAllowed = true;
        $filter    = $this->getSettings()->getTypeFilter();

        if ($filter !== null) {
            $isBuiltIn = $this->isTypeBuiltIn($type);
            $isAllowed = $filter->isAllowedType($type, $isBuiltIn);
        }

        // Cache
        $this->allowedTypes[$type] = $isAllowed;

        // Return
        return $isAllowed;
    }

    public function isTypeDefinitionAllowed(string $type): bool {
        // Cached?
        if (isset($this->allowedTypesDefinitions[$type])) {
            return $this->allowedTypesDefinitions[$type];
        }

        // Allowed?
        $isAllowed = $this->isTypeAllowed($type);

        if ($isAllowed) {
            $filter    = $this->getSettings()->getTypeDefinitionFilter();
            $isBuiltIn = $this->isTypeBuiltIn($type);
            $isAllowed = $isBuiltIn
                ? ($filter !== null && $filter->isAllowedType($type, $isBuiltIn))
                : ($filter === null || $filter->isAllowedType($type, $isBuiltIn));
        }

        // Cache
        $this->allowedTypesDefinitions[$type] = $isAllowed;

        // Return
        return $isAllowed;
    }

    protected function isTypeBuiltIn(string $type): bool {
        return array_key_exists($type, Type::builtInTypes());
    }

    public function getTypeName((TypeDefinitionNode&Node)|(TypeNode&Node)|Type $type): string {
        $name = null;

        if ($type instanceof WrappingType) {
            $type = $type->getInnermostType();
        }

        if ($type instanceof NamedType) {
            $name = $type->name();
        } elseif ($type instanceof TypeDefinitionNode) {
            $name = $type->getName()->value;
        } elseif ($type instanceof Node) {
            $name = match (true) {
                $type instanceof ListTypeNode,
                $type instanceof NonNullTypeNode
                    => $this->getTypeName($type->type),
                $type instanceof NamedTypeNode
                    => $this->getTypeName($type->name),
                $type instanceof NameNode
                    => $type->value,
                default
                    => null,
            };
        } else {
            // empty
        }

        assert($name !== null);

        return $name;
    }
    // </editor-fold>

    // <editor-fold desc="Directives">
    // =========================================================================
    /**
     * @return array<array-key, DirectiveDefinitionNode|Directive>
     */
    public function getDirectives(): array {
        return array_merge(
            (array) $this->getDirectiveResolver()?->getDefinitions(),
            (array) $this->getSchema()?->getDirectives(),
        );
    }

    public function getDirective(string $name): DirectiveDefinitionNode|Directive|null {
        $directive = $this->getSchema()?->getDirective($name)
            ?? $this->getDirectiveResolver()?->getDefinition($name);

        if ($directive === null && $this->getSchema() !== null) {
            throw new DirectiveDefinitionNotFound($name);
        }

        return $directive;
    }

    public function isDirectiveAllowed(string $directive): bool {
        // Cached?
        if (isset($this->allowedDirectives[$directive])) {
            return $this->allowedDirectives[$directive];
        }

        // Allowed?
        $isAllowed = true;
        $filter    = $this->getSettings()->getDirectiveFilter();

        if ($filter !== null) {
            $isBuiltIn = $this->isDirectiveBuiltIn($directive);
            $isAllowed = $filter->isAllowedDirective($directive, $isBuiltIn);
        }

        // Cache
        $this->allowedDirectives[$directive] = $isAllowed;

        // Return
        return $isAllowed;
    }

    public function isDirectiveDefinitionAllowed(string $directive): bool {
        // Cached?
        if (isset($this->allowedDirectivesDefinitions[$directive])) {
            return $this->allowedDirectivesDefinitions[$directive];
        }

        // Allowed?
        $settings  = $this->getSettings();
        $isAllowed = $this->isDirectiveAllowed($directive);

        if ($isAllowed) {
            $filter    = $settings->getDirectiveDefinitionFilter();
            $isBuiltIn = $this->isDirectiveBuiltIn($directive);
            $isAllowed = $isBuiltIn
                ? ($filter !== null && $filter->isAllowedDirective($directive, $isBuiltIn))
                : ($filter === null || $filter->isAllowedDirective($directive, $isBuiltIn));
        }

        // Cache
        $this->allowedDirectivesDefinitions[$directive] = $isAllowed;

        // Return
        return $isAllowed;
    }

    protected function isDirectiveBuiltIn(string $directive): bool {
        return isset(GraphQLDirective::getInternalDirectives()[$directive]);
    }

    public function getDirectiveArgument(DirectiveNode $object, string $name): InputValueDefinitionNode|Argument|null {
        // AST Node doesn't contain type of argument, but it can be
        // determined by Directive definition.
        $argument   = null;
        $directive  = $object->name->value;
        $definition = $this->getDirective($directive);

        if ($definition instanceof DirectiveDefinitionNode) {
            foreach ($definition->arguments as $arg) {
                if ($arg->name->value === $name) {
                    $argument = $arg;
                    break;
                }
            }
        } elseif ($definition instanceof GraphQLDirective) {
            foreach ($definition->args as $arg) {
                if ($arg->name === $name) {
                    $argument = $arg;
                    break;
                }
            }
        } else {
            // empty
        }

        if ($argument === null && $this->getSchema() !== null) {
            throw new DirectiveArgumentNotFound("@{$directive}", $name);
        }

        return $argument;
    }
    // </editor-fold>

    // <editor-fold desc="Fields">
    // =========================================================================
    public function getField((TypeNode&Node)|Type $object, string $name): InputObjectField|FieldDefinition|null {
        $field      = null;
        $type       = $this->getTypeName($object);
        $definition = $this->getType($type);

        if ($definition instanceof HasFieldsType || $definition instanceof InputObjectType) {
            $field = $definition->findField($name);
        }

        if ($field === null && $this->getSchema() !== null) {
            throw new FieldNotFound($type, $name);
        }

        return $field;
    }

    public function getFieldArgument((TypeNode&Node)|Type $object, string $field, string $name): ?Argument {
        $argument   = null;
        $definition = $this->getField($object, $field);

        if ($definition instanceof FieldDefinition) {
            foreach ($definition->args as $arg) {
                if ($arg->name === $name) {
                    $argument = $arg;
                    break;
                }
            }
        }

        if ($argument === null && $this->getSchema() !== null) {
            throw new FieldArgumentNotFound($this->getTypeName($object), $field, $name);
        }

        return $argument;
    }
    // </editor-fold>
}
