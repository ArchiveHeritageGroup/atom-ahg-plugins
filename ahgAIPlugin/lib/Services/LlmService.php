<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/LlmProviderInterface.php';
require_once dirname(__FILE__) . '/providers/OllamaProvider.php';
require_once dirname(__FILE__) . '/providers/OpenAIProvider.php';
require_once dirname(__FILE__) . '/providers/AnthropicProvider.php';

/**
 * LLM Service
 *
 * Factory and orchestrator for LLM providers.
 * Manages configurations and provides a unified interface for LLM operations.
 */
class LlmService
{
    private const ENCRYPTION_METHOD = 'aes-256-cbc';

    private ?string $encryptionKey = null;

    public function __construct()
    {
        // Use a key from settings or generate one based on a constant
        $this->encryptionKey = $this->getEncryptionKey();
    }

    /**
     * Get an LLM provider instance by config ID
     *
     * @param int|null $configId Config ID from ahg_llm_config, or null for default
     * @return LlmProviderInterface
     *
     * @throws Exception If config not found or provider unknown
     */
    public function getProvider(?int $configId = null): LlmProviderInterface
    {
        $config = $configId ? $this->getConfiguration($configId) : $this->getDefaultConfig();

        if (!$config) {
            throw new Exception('LLM configuration not found');
        }

        // Decrypt API key if present
        if (!empty($config->api_key_encrypted)) {
            $config->api_key = $this->decryptApiKey($config->api_key_encrypted);
        } else {
            $config->api_key = null;
        }

        $configArray = (array) $config;

        switch ($config->provider) {
            case 'ollama':
                return new OllamaProvider($configArray);

            case 'openai':
                return new OpenAIProvider($configArray);

            case 'anthropic':
                return new AnthropicProvider($configArray);

            default:
                throw new Exception("Unknown LLM provider: {$config->provider}");
        }
    }

    /**
     * Send a completion request using specified or default config
     *
     * @param string $systemPrompt System/context prompt
     * @param string $userPrompt User message
     * @param int|null $configId Config ID or null for default
     * @param array $options Additional options
     * @return array Result with success, text, tokens_used, model, etc.
     */
    public function complete(string $systemPrompt, string $userPrompt, ?int $configId = null, array $options = []): array
    {
        try {
            $provider = $this->getProvider($configId);

            return $provider->complete($systemPrompt, $userPrompt, $options);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'text' => null,
                'tokens_used' => 0,
                'model' => null,
            ];
        }
    }

    /**
     * Get a specific configuration by ID
     *
     * @param int $configId
     * @return object|null
     */
    public function getConfiguration(int $configId): ?object
    {
        return DB::table('ahg_llm_config')
            ->where('id', $configId)
            ->first();
    }

    /**
     * Get the default active configuration
     *
     * @return object|null
     */
    public function getDefaultConfig(): ?object
    {
        // First try to get the configured default
        $config = DB::table('ahg_llm_config')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        // Fallback to first active config
        if (!$config) {
            $config = DB::table('ahg_llm_config')
                ->where('is_active', 1)
                ->orderBy('id')
                ->first();
        }

        return $config;
    }

    /**
     * Get all configurations
     *
     * @param bool $activeOnly Only return active configs
     * @return array
     */
    public function getConfigurations(bool $activeOnly = false): array
    {
        $query = DB::table('ahg_llm_config');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('provider')->orderBy('name')->get()->toArray();
    }

    /**
     * Create a new LLM configuration
     *
     * @param array $data Configuration data
     * @return int New config ID
     */
    public function createConfiguration(array $data): int
    {
        $insert = [
            'provider' => $data['provider'],
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? 1,
            'is_default' => $data['is_default'] ?? 0,
            'endpoint_url' => $data['endpoint_url'] ?? null,
            'model' => $data['model'],
            'max_tokens' => $data['max_tokens'] ?? 2000,
            'temperature' => $data['temperature'] ?? 0.7,
            'timeout_seconds' => $data['timeout_seconds'] ?? 120,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Encrypt API key if provided
        if (!empty($data['api_key'])) {
            $insert['api_key_encrypted'] = $this->encryptApiKey($data['api_key']);
        }

        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            DB::table('ahg_llm_config')->update(['is_default' => 0]);
        }

        return DB::table('ahg_llm_config')->insertGetId($insert);
    }

    /**
     * Update an existing configuration
     *
     * @param int $configId
     * @param array $data
     * @return bool
     */
    public function updateConfiguration(int $configId, array $data): bool
    {
        $update = [];

        $fields = ['name', 'is_active', 'endpoint_url', 'model', 'max_tokens', 'temperature', 'timeout_seconds'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        // Handle API key update
        if (array_key_exists('api_key', $data)) {
            if (!empty($data['api_key'])) {
                $update['api_key_encrypted'] = $this->encryptApiKey($data['api_key']);
            } else {
                $update['api_key_encrypted'] = null;
            }
        }

        // Handle default flag
        if (!empty($data['is_default'])) {
            DB::table('ahg_llm_config')->update(['is_default' => 0]);
            $update['is_default'] = 1;
        }

        if (empty($update)) {
            return true;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('ahg_llm_config')
            ->where('id', $configId)
            ->update($update) >= 0;
    }

    /**
     * Delete a configuration
     *
     * @param int $configId
     * @return bool
     */
    public function deleteConfiguration(int $configId): bool
    {
        return DB::table('ahg_llm_config')
            ->where('id', $configId)
            ->delete() > 0;
    }

    /**
     * Get health status for all active providers
     *
     * @return array
     */
    public function getAllHealth(): array
    {
        $configs = $this->getConfigurations(true);
        $results = [];

        foreach ($configs as $config) {
            try {
                $provider = $this->getProvider($config->id);
                $health = $provider->getHealth();
                $results[$config->name] = array_merge($health, [
                    'config_id' => $config->id,
                    'provider' => $config->provider,
                ]);
            } catch (Exception $e) {
                $results[$config->name] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'config_id' => $config->id,
                    'provider' => $config->provider,
                ];
            }
        }

        return $results;
    }

    /**
     * Encrypt an API key for storage
     *
     * @param string $apiKey
     * @return string
     */
    public function encryptApiKey(string $apiKey): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($apiKey, self::ENCRYPTION_METHOD, $this->encryptionKey, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a stored API key
     *
     * @param string $encrypted
     * @return string
     */
    public function decryptApiKey(string $encrypted): string
    {
        $data = base64_decode($encrypted);
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);

        return openssl_decrypt($encryptedData, self::ENCRYPTION_METHOD, $this->encryptionKey, 0, $iv);
    }

    /**
     * Get or generate the encryption key
     *
     * @return string
     */
    private function getEncryptionKey(): string
    {
        // Try to get from settings
        $setting = DB::table('ahg_ai_settings')
            ->where('feature', 'general')
            ->where('setting_key', 'encryption_key')
            ->first();

        if ($setting && !empty($setting->setting_value)) {
            return $setting->setting_value;
        }

        // Generate a new key based on some server constants
        // This is a fallback - for production, a proper key should be configured
        $baseKey = 'ahg_llm_' . php_uname('n') . '_' . dirname(__FILE__);

        return hash('sha256', $baseKey);
    }
}
