<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\PHPStan;

use PhpCsFixer\Preg;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Php\RegexArrayShapeMatcher;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\Type;

final class PregMatchParameterOutExtension implements StaticMethodParameterOutTypeExtension
{
    private RegexArrayShapeMatcher $regexShapeMatcher;

    public function __construct(
        RegexArrayShapeMatcher $regexShapeMatcher
    ) {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return
            Preg::class === $methodReflection->getDeclaringClass()->getName()
            && 'match' === $methodReflection->getName()
            && 'matches' === $parameter->getName();
    }

    public function getParameterOutTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        $patternArg = $args[0] ?? null;
        $matchesArg = $args[2] ?? null;
        $flagsArg = $args[3] ?? null;

        if (
            null === $patternArg || null === $matchesArg
        ) {
            return null;
        }

        $patternType = $scope->getType($patternArg->value);
        $flagsType = null;
        if (null !== $flagsArg) {
            $flagsType = $scope->getType($flagsArg->value);
        }

        return $this->regexShapeMatcher->matchType($patternType, $flagsType, TrinaryLogic::createMaybe());
    }
}