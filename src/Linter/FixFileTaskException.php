<?php

namespace PhpCsFixer\Runner;

use Throwable;

class FixFileTaskException extends \Exception implements  \Serializablealizable
{
    static public function fromThrowable(Throwable $throwable) {
        $ex = new self();
        $ex->message = $throwable->getMessage();
        $ex->code = $throwable->getCode();
        $ex->file = $throwable->getFile();
        $ex->line = $throwable->getLine();
        return $ex;
    }

    public function serialize()
    {
        return serialize(array($this->message, $this->code, $this->file, $this->line));
    }

    public function unserialize($serialized)
    {
        list($this->message, $this->code, $this->file, $this->line) = unserialize($serialized);
    }
}
