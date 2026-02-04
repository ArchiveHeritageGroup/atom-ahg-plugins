<?php

namespace AhgGraphQLPlugin\GraphQL\Security;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;

class DepthLimitRule extends ValidationRule
{
    private int $maxDepth;
    private array $fragments = [];

    public function __construct(int $maxDepth = 10)
    {
        $this->maxDepth = $maxDepth;
    }

    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::DOCUMENT => [
                'enter' => function ($node) {
                    // Collect fragment definitions
                    foreach ($node->definitions as $definition) {
                        if ($definition->kind === NodeKind::FRAGMENT_DEFINITION) {
                            $this->fragments[$definition->name->value] = $definition;
                        }
                    }
                },
            ],
            NodeKind::OPERATION_DEFINITION => function (OperationDefinitionNode $operationDefinition) use ($context) {
                $depth = $this->calculateDepth($operationDefinition->selectionSet, 0, $context);

                if ($depth > $this->maxDepth) {
                    $context->reportError(
                        new \GraphQL\Error\Error(
                            sprintf(
                                'Query depth of %d exceeds maximum allowed depth of %d',
                                $depth,
                                $this->maxDepth
                            ),
                            [$operationDefinition]
                        )
                    );
                }
            },
        ];
    }

    private function calculateDepth(SelectionSetNode $selectionSet, int $currentDepth, QueryValidationContext $context): int
    {
        $maxDepth = $currentDepth;

        foreach ($selectionSet->selections as $selection) {
            $depth = $currentDepth;

            if ($selection instanceof FieldNode) {
                // Skip introspection fields
                if (strpos($selection->name->value, '__') === 0) {
                    continue;
                }

                $depth = $currentDepth + 1;

                if ($selection->selectionSet !== null) {
                    $depth = $this->calculateDepth($selection->selectionSet, $depth, $context);
                }
            } elseif ($selection instanceof FragmentSpreadNode) {
                $fragmentName = $selection->name->value;
                if (isset($this->fragments[$fragmentName])) {
                    $fragment = $this->fragments[$fragmentName];
                    $depth = $this->calculateDepth($fragment->selectionSet, $currentDepth, $context);
                }
            } elseif ($selection instanceof InlineFragmentNode) {
                if ($selection->selectionSet !== null) {
                    $depth = $this->calculateDepth($selection->selectionSet, $currentDepth, $context);
                }
            }

            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }
}
