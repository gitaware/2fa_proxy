<?php
namespace App\Providers;

interface ProviderInterface {
    public function getId(): string;
    public function getLabel(): string;
    public function getIcon(): string;
    public function getFormDefinition($adminform): array;
    public function verify(array $data): array; // [bool success, ?string error]
}

