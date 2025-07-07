<?php

namespace App\Database;

abstract class JsonDb
{
    protected string $file;
    protected array $data = [];

    public function __construct(string $filepath)
    {
        $this->file = $filepath;

        if (!file_exists($this->file)) {
            $this->data = $this->getDefaultData();
            $this->save();
        } else {
            $this->load();
        }
    }

    abstract protected function getDefaultData(): array;

    protected function load(): void
    {
        $json = file_get_contents($this->file);
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            $decoded = $this->getDefaultData();
        }

        $this->data = $decoded;
    }

    protected function save(): void
    {
        $json = json_encode($this->data, JSON_PRETTY_PRINT);
        file_put_contents($this->file, $json, LOCK_EX);
    }
}

