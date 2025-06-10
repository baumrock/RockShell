<?php

namespace RockShell\Concerns;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Trait for handling user interaction and prompts
 */
trait InteractsWithQuestions
{
    /**
     * Ask user for input
     */
    protected function ask(string $question, $default = null)
    {
        $helper = $this->getHelper('question');
        $prompt = $default ? " [<comment>$default</comment>]" : '';
        $question = new Question("\n <info>$question</info>$prompt:\n > ", $default);
        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * Ask user to choose from options
     */
    protected function choice(string $question, array $choices, $default = null)
    {
        $helper = $this->getHelper('question');
        $defaultText = $default !== null ? " [$default]" : '';
        $question = new ChoiceQuestion("\n <info>$question</info>$defaultText:", $choices, $default);
        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * Ask user for confirmation
     */
    protected function confirm(string $question, bool $default = true)
    {
        $helper = $this->getHelper('question');
        $defaultText = $default ? 'yes' : 'no';
        $question = new ConfirmationQuestion("\n <info>$question</info> (yes/no) [<comment>$defaultText</comment>]:\n > ", $default);
        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * Ask with autocomplete support
     */
    protected function askWithCompletion(string $question, array $autocomplete = [], $default = null)
    {
        $helper = $this->getHelper('question');
        $prompt = $default !== null ? " [<comment>$default</comment>]" : '';
        $question = new Question("\n <info>$question</info>$prompt:\n > ", $default);
        if (!empty($autocomplete)) {
            $question->setAutocompleterValues(array_values($autocomplete));
        }
        return $helper->ask($this->input, $this->output, $question);
    }
}