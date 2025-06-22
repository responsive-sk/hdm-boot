<?php

declare(strict_types=1);

namespace HdmBoot\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * PHPStan Rule: Detect dangerous path concatenation patterns.
 * 
 * This rule prevents security vulnerabilities by detecting
 * string concatenation that could lead to path traversal attacks.
 * 
 * @implements Rule<Concat>
 */
class PathConcatenationRule implements Rule
{
    public function getNodeType(): string
    {
        return Concat::class;
    }

    /**
     * @param Concat $node
     * @return array<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        // Check for dangerous path concatenation patterns
        if ($this->isDangerousPathConcatenation($node)) {
            $errors[] = RuleErrorBuilder::message(
                'Dangerous path concatenation detected. Use Paths service instead of string concatenation for file paths.'
            )
            ->tip('Replace with: $this->paths->getPath($baseDir, $relativePath)')
            ->build();
        }

        return $errors;
    }

    /**
     * Check if concatenation is a dangerous path pattern.
     */
    private function isDangerousPathConcatenation(Concat $node): bool
    {
        // Pattern 1: $var . '/' . $something
        if ($this->isVariableConcatSlash($node)) {
            return true;
        }

        // Pattern 2: __DIR__ . '/' . $something  
        if ($this->isDirConcatSlash($node)) {
            return true;
        }

        // Pattern 3: $path . DIRECTORY_SEPARATOR . $file
        if ($this->isDirectorySeparatorConcat($node)) {
            return true;
        }

        return false;
    }

    /**
     * Check for: $var . '/' . $something
     */
    private function isVariableConcatSlash(Concat $node): bool
    {
        // Check if left side is another concat
        if ($node->left instanceof Concat) {
            $leftConcat = $node->left;
            
            // Check if it's: $var . '/'
            if ($leftConcat->left instanceof Variable && 
                $leftConcat->right instanceof String_ &&
                $leftConcat->right->value === '/') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for: __DIR__ . '/' . $something
     */
    private function isDirConcatSlash(Concat $node): bool
    {
        // Check if left side is another concat
        if ($node->left instanceof Concat) {
            $leftConcat = $node->left;
            
            // Check if it's: __DIR__ . '/'
            if ($leftConcat->left instanceof Node\Scalar\MagicConst\Dir && 
                $leftConcat->right instanceof String_ &&
                $leftConcat->right->value === '/') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for: $path . DIRECTORY_SEPARATOR . $file
     */
    private function isDirectorySeparatorConcat(Concat $node): bool
    {
        // Check if left side is another concat
        if ($node->left instanceof Concat) {
            $leftConcat = $node->left;
            
            // Check if right side is DIRECTORY_SEPARATOR constant
            if ($leftConcat->right instanceof Node\Expr\ConstFetch &&
                $leftConcat->right->name->toString() === 'DIRECTORY_SEPARATOR') {
                return true;
            }
        }

        return false;
    }
}
