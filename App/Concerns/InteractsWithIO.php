<?php

namespace RockShell\Concerns;

/**
 * Trait for handling input/output operations and styled messages
 */
trait InteractsWithIO
{
    /**
     * Output info message
     */
    protected function info(string $message)
    {
        $this->output->writeln("<info>$message</info>");
    }

    /**
     * Output error message
     */
    protected function error(string $message)
    {
        $this->output->writeln("<error>$message</error>");
    }

    /**
     * Output warning message
     */
    protected function warn(string $message)
    {
        $this->output->writeln("<comment>$message</comment>");
    }

    /**
     * Output alert message
     */
    protected function alert(string $message)
    {
        $length = strlen($message) + 4;
        $border = str_repeat('*', $length);
        
        $this->output->writeln('');
        $this->output->writeln("<comment>$border</comment>");
        $this->output->writeln("<comment>*  $message  *</comment>");
        $this->output->writeln("<comment>$border</comment>");
        $this->output->writeln('');
    }

    /**
     * Output comment message
     */
    protected function comment(string $message)
    {
        $this->output->writeln("<comment>$message</comment>");
    }

    /**
     * Output question message
     */
    protected function question(string $message)
    {
        $this->output->writeln("<question>$message</question>");
    }

    /**
     * Write success message to output
     */
    public function success($str)
    {
        $this->info($this->str($str));
    }

    /**
     * Write string to output
     */
    public function write($str)
    {
        $str = $this->str($str);
        $this->output->writeln($str);
    }

    /**
     * Output a blank line
     */
    protected function newLine($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->output->writeln('');
        }
    }
}