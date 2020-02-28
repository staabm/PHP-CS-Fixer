<?php

namespace PhpCsFixer\Runner;

final class FixFileTaskResult {
    /**
     * @var FixFileTaskException
     */
    public $lintingException;

    /**
     * @var FixFileTaskException
     */
    public $fixException;
    /**
     * @var FixFileTaskException
     */
    public $parseError;
    /**
     * @var FixFileTaskException
     */
    public $fixThrowable;

    /**
     * @var string[]
     */
    public $appliedFixers;
    /**
     * @var string
     */
    public $oldCode;
    /**
     * @var string
     */
    public $newCode;
    /**
     * @var boolean
     */
    public $hashChanged;
}
