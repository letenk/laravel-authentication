<?php

namespace App\Exceptions;

use Exception;

class GeneralException extends Exception
{
    protected mixed $data;

    public static function create(string $message, mixed $data = null, int $errCode = 400): static
    {
        $instance = new static($message, $errCode);
        $instance->data = $data;

        return $instance;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
