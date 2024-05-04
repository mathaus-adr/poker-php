<?php

namespace App\Commands;

readonly class CommandExecutionData
{
    public function __construct(private array $data = [])
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    public function read(string $name): mixed
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }
}
