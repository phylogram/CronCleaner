<?php


namespace phylogram\CronScheduleCleaning;


class ArgsChecker
{

    public const INCOMPLETE = 0;
    public const CLASS_MISSING = 1;

    private array $args;
    private array $errors = [];

    public function __construct($args)
    {
        $this->args = (array) $args;

    }

    public function check(): bool
    {
        \array_walk_recursive($this->args, fn($value, $index) => $this->checkObjectValid($index) && $this->checkObjectValid($value) );
        return \count($this->errors) === 0;
    }

    private function checkObjectValid($value): bool
    {
        if (! \is_object($value)) {
            return true;
        }

        $class = \get_class($value);
        
        if ($class === \__PHP_Incomplete_Class::class) {
            $this->errors[$class] = static::INCOMPLETE;
        }

        if (! \class_exists($class) ) {
            $this->errors[$class] = static::CLASS_MISSING;
        }

        return true;

    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

}