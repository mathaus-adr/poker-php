<?php

namespace App\Commands;

class CommandExecutedData
{
    public function __construct(private array $data = [])
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function pushData(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
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
