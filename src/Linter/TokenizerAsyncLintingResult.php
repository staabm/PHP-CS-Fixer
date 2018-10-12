<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Linter;

use PhpCsFixer\Tokenizer\CodeHasher;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class TokenizerAsyncLintingResult implements LintingResultInterface
{
    /**
     * @var null|\ParseError
     */
    private $error;

    private $promise;

    /**
     * @param null|\ParseError $error
     */
    public function __construct(\ParseError $error = null, \Amp\Promise $promise = null)
    {
        $this->error = $error;
        $this->promise = $promise;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if (null !== $this->error) {
            throw new LintingException(
                sprintf('PHP Parse error: %s on line %d.', $this->error->getMessage(), $this->error->getLine()),
                $this->error->getCode(),
                $this->error
            );
        }

        $source = \Amp\Promise\wait($this->promise);

        try {
            // To lint, we will parse the source into Tokens.
            // During that process, it might throw ParseError.
            // If it won't, cache of tokenized version of source will be kept, which is great for Runner.
            // Yet, first we need to clear already existing cache to not hit it and lint the code indeed.
            $codeHash = CodeHasher::calculateCodeHash($source);
            Tokens::clearCache($codeHash);
            Tokens::fromCode($source);
        } catch (\ParseError $e) {
            throw new LintingException(
                sprintf('PHP Parse error: %s on line %d.', $e->getMessage(), $e->getLine()),
                $e->getCode(),
                $e
            );
        }
    }
}
