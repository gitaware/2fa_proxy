<?php

namespace App\Database;

class ConfigDb
{
    private string $file;
    private array $data = [];

    public function __construct(string $file)
    {
        $this->file = $file;
        $this->data = file_exists($file)
            ? json_decode(file_get_contents($file), true) ?? []
            : [];
    }

    public function getAll(): array
    {
        return $this->data;
    }

    public function setAll(array $data): void
    {
        $this->data = $data;
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

