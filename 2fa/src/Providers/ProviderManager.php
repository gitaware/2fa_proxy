<?php
namespace App\Providers;

use App\Providers\ProviderInterface;

class ProviderManager {
    private $providers = [];

    public function registerProvider(ProviderInterface $provider): void {
        $this->providers[$provider->getId()] = $provider;
    }

    public function getProvider(string $id): ?ProviderInterface {
        return $this->providers[$id] ?? null;
    }

    public function getProviders(): array {
        return $this->providers;
    }

    public function getFormDefinitions($adminform = false): array {
        $forms = [];
        foreach ($this->providers as $id => $provider) {
            $forms[$id] = $provider->getFormDefinition($adminform);
        }
        return $forms;
    }
}

