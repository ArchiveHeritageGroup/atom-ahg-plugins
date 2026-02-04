<?php

namespace AhgGraphQLPlugin\GraphQL\Security;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;

class ComplexityAnalyzer extends ValidationRule
{
    private int $maxComplexity;
    private array $fragments = [];

    private array $fieldCosts = [
        'children' => 10,
        'relatedItems' => 10,
        'holdings' => 10,
        'ancestors' => 5,
        'dates' => 2,
        'subjects' => 2,
        'places' => 2,
        'creators' => 3,
        'digitalObjects' => 3,
        'terms' => 3,
        'search' => 15,
    ];

    public function __construct(int $maxComplexity = 1000)
    {
        $this->maxComplexity = $maxComplexity;
    }

    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::DOCUMENT => [
                'enter' => function ($node) {
                    foreach ($node->definitions as $definition) {
                        if ($definition->kind === NodeKind::FRAGMENT_DEFINITION) {
                            $this->fragments[$definition->name->value] = $definition;
                        }
                    }
                },
            ],
            NodeKind::OPERATION_DEFINITION => function (OperationDefinitionNode $operationDefinition) use ($context) {
                $complexity = $this->calculateComplexity($operationDefinition->selectionSet, 1, $context);

                if ($complexity > $this->maxComplexity) {
                    $context->reportError(
                        new \GraphQL\Error\Error(
                            sprintf(
                                'Query complexity of %d exceeds maximum allowed complexity of %d',
                                $complexity,
                                $this->maxComplexity
                            ),
                            [$operationDefinition]
                        )
                    );
                }
            },
        ];
    }

    private function calculateComplexity(SelectionSetNode $selectionSet, int $multiplier, QueryValidationContext $context): int
    {
        $complexity = 0;

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                // Skip introspection fields in complexity calculation
                if (strpos($selection->name->value, '__') === 0) {
                    continue;
                }

                $fieldName = $selection->name->value;
                $fieldCost = $this->fieldCosts[$fieldName] ?? 1;

                // Get first/limit argument to multiply complexity
                $fieldMultiplier = 1;
                if ($selection->arguments) {
                    foreach ($selection->arguments as $arg) {
                        if ($arg->name->value === 'first' && $arg->value->kind === NodeKind::INT) {
                            $fieldMultiplier = min((int) $arg->value->value, 100);
                            break;
                        }
                    }
                }

                $nodeCost = $fieldCost * $multiplier;
                $complexity += $nodeCost;

                // Recurse into nested selection sets
                if ($selection->selectionSet !== null) {
                    $nestedMultiplier = $multiplier;

                    // Connection fields multiply the nested complexity
                    if (in_array($fieldName, ['children', 'relatedItems', 'holdings', 'items', 'actors', 'repositories', 'search'])) {
                        $nestedMultiplier = $multiplier * $fieldMultiplier;
                    }

                    $complexity += $this->calculateComplexity($selection->selectionSet, $nestedMultiplier, $context);
                }
            } elseif ($selection instanceof FragmentSpreadNode) {
                $fragmentName = $selection->name->value;
                if (isset($this->fragments[$fragmentName])) {
                    $fragment = $this->fragments[$fragmentName];
                    $complexity += $this->calculateComplexity($fragment->selectionSet, $multiplier, $context);
                }
            } elseif ($selection instanceof InlineFragmentNode) {
                if ($selection->selectionSet !== null) {
                    $complexity += $this->calculateComplexity($selection->selectionSet, $multiplier, $context);
                }
            }
        }

        return $complexity;
    }
}
