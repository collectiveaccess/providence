<?php

namespace Softonic\GraphQL;

class Response
{
    private $data;
    private $errors;

    public function __construct(array $data, array $errors = [])
    {
        $this->data = $data;
        $this->errors = $errors;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
