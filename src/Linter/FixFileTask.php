<?php

namespace PhpCsFixer\Linter;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Error\Error;
use PhpCsFixer\FileReader;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFileProcessedEvent;
use PhpCsFixer\Runner\FixFileTaskException;
use PhpCsFixer\Runner\FixFileTaskResult;
use PhpCsFixer\Tokenizer\Tokens;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\SplFileInfo;

class FixFileTask implements Task
{
    /**
     * @param string $file
     */
    private $file;
    /**
     * @var FixerInterface[]
     */
    private $fixers;
    /**
     * @var LintingResultInterface
     */
    private $lintingResult;

    public function __construct($file, array $fixers, LintingResultInterface $lintingResult = null)
    {
        $this->file = $file;
        $this->fixers = $fixers;
        $this->lintingResult = $lintingResult;
    }

    /**
     * @inheritDoc
     * @return FixFileTaskResult
     */
    public function run(Environment $environment)
    {
        $result = new FixFileTaskResult();

        $file = new \SplFileInfo($this->file);
        $lintingResult = $this->lintingResult;

        try {
            $lintingResult->check();
        } catch (LintingException $e) {
            $result->lintingException = FixFileTaskException::fromThrowable($e);

            return $result;
        }

        $old = FileReader::createSingleton()->read($file->getRealPath());

        Tokens::setLegacyMode(false);

        $tokens = Tokens::fromCode($old);
        $oldHash = $tokens->getCodeHash();

        $newHash = $oldHash;
        $new = $old;

        $appliedFixers = [];

        try {
            foreach ($this->fixers as $fixer) {
                // for custom fixers we don't know is it safe to run `->fix()` without checking `->supports()` and `->isCandidate()`,
                // thus we need to check it and conditionally skip fixing
                if (
                    !$fixer instanceof AbstractFixer &&
                    (!$fixer->supports($file) || !$fixer->isCandidate($tokens))
                ) {
                    continue;
                }

                $fixer->fix($file, $tokens);

                if ($tokens->isChanged()) {
                    $tokens->clearEmptyTokens();
                    $tokens->clearChanged();
                    $appliedFixers[] = $fixer->getName();
                }
            }
        } catch (\Exception $e) {
            $result->fixException = FixFileTaskException::fromThrowable($e);

            return $result;
        } catch (\ParseError $e) {
            $result->parseError = FixFileTaskException::fromThrowable($e);

            return $result;
        } catch (\Throwable $e) {
            $result->fixThrowable = FixFileTaskException::fromThrowable($e);

            return $result;
        }

        if (!empty($appliedFixers)) {
            $new = $tokens->generateCode();
            $newHash = $tokens->getCodeHash();
        }

        // We need to check if content was changed and then applied changes.
        // But we can't simple check $appliedFixers, because one fixer may revert
        // work of other and both of them will mark collection as changed.
        // Therefore we need to check if code hashes changed.
        $result->hashChanged = $oldHash !== $newHash;
        $result->appliedFixers = $appliedFixers;
        $result->oldCode = $old;
        $result->newCode = $new;

        return $result;

        /*
                if ($result->hashChanged) {
                    $fixInfo = [
                        'appliedFixers' => $appliedFixers,
                        'diff' => $this->differ->diff($old, $new),
                    ];

                    try {
                        $this->linter->lintSource($new)->check();
                    } catch (LintingException $e) {
                        $this->dispatchEvent(
                            FixerFileProcessedEvent::NAME,
                            new FixerFileProcessedEvent(FixerFileProcessedEvent::STATUS_LINT)
                        );

                        $this->errorsManager->report(new Error(Error::TYPE_LINT, $name, $e, $fixInfo['appliedFixers'], $fixInfo['diff']));

                        return;
                    }

                    if (!$this->isDryRun) {
                        if (false === @file_put_contents($file->getRealPath(), $new)) {
                            $error = error_get_last();

                            throw new IOException(
                                sprintf('Failed to write file "%s", "%s".', $file->getPathname(), $error ? $error['message'] : 'no reason available'),
                                0,
                                null,
                                $file->getRealPath()
                            );
                        }
                    }
                }

                $this->cacheManager->setFile($name, $new);

                $this->dispatchEvent(
                    FixerFileProcessedEvent::NAME,
                    new FixerFileProcessedEvent($fixInfo ? FixerFileProcessedEvent::STATUS_FIXED : FixerFileProcessedEvent::STATUS_NO_CHANGES)
                );

        return $result;

        */
    }
}

;
