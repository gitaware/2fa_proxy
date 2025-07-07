<?php

namespace App\Config;

use App\Database\ConfigDb;

//example usage:
//$config = new \App\Config\ConfigService(__DIR__ . '/../data/config.json');

// Get deeply nested value
//echo $config->get('totp')->get('issuer'); // CloudAware

// Set a nested value
//$config->get('totp')->set('issuer', 'NewIssuer');

// Add a new subkey
//$config->get('totp')->set('algo', 'SHA256');

// Retrieve deep nested section and keep going
//$csrf = $config->get('csrf');
//echo $csrf->get('token_id');

// Remove a top-level section
//$config->remove('totp');

// Remove a nested key
//$config->get('csrf')->removeKey('token_id');

// Set, then remove a nested subkey
//$config->get('totp')->set('algo', 'SHA256');
//$config->get('totp')->removeKey('algo');

class ConfigService
{
    private ConfigDb $db;
    private array $data;

    private array $defaults = [
        'totp' => [
            'issuer' => 'CloudAware',
        ],
        'csrf' => [
            'token_id' => 'admin_action',
        ],
    ];

    public function __construct(string $configPath)
    {
        $this->db = new ConfigDb($configPath);
        $this->initializeDefaults();
    }

    private function initializeDefaults(): void
    {
        $this->data = array_replace_recursive($this->defaults, $this->db->getAll());
        $this->persist();
    }

    public function get(string $key, mixed $defaultvalue = null): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            return $defaultvalue;
        }

        $value = $this->data[$key];
        return is_array($value)
            ? new ConfigSection($value, [$key], $this)
            : $value;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        $this->persist();
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
        $this->persist();
    }

    public function getAll(): array
    {
        return $this->data;
    }

    public function setNested(array $path, $value): void
    {
        $ref = &$this->data;
        foreach ($path as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
        $ref = $value;
        $this->persist();
    }

    public function getNested(array $path): mixed
    {
        $ref = &$this->data;
        foreach ($path as $segment) {
            if (!is_array($ref) || !array_key_exists($segment, $ref)) {
                return null;
            }
            $ref = &$ref[$segment];
        }
        return is_array($ref)
            ? new ConfigSection($ref, $path, $this)
            : $ref;
    }

    public function removeNested(array $path): void
    {
        $ref = &$this->data;
        $lastKey = array_pop($path);
        foreach ($path as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                return;
            }
            $ref = &$ref[$segment];
        }

        unset($ref[$lastKey]);
        $this->persist();
    }

    private function persist(): void
    {
        $this->db->setAll($this->data);
    }
}

class ConfigSection
{
    private array $data;
    private array $path;
    private ConfigService $root;

    public function __construct(array $data, array $path, ConfigService $root)
    {
        $this->data = $data;
        $this->path = $path;
        $this->root = $root;
    }

    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            return null;
        }

        $value = $this->data[$key];
        return is_array($value)
            ? new self($value, [...$this->path, $key], $this->root)
            : $value;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        $this->root->setNested([...$this->path, $key], $value);
    }

    public function removeKey(string $key): void
    {
        unset($this->data[$key]);
        $this->root->removeNested([...$this->path, $key]);
    }

    public function getAll(): array
    {
        return $this->data;
    }
}
