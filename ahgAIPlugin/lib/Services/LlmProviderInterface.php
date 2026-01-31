<?php

/**
 * LLM Provider Interface
 *
 * Contract for LLM providers (Ollama, OpenAI, Anthropic)
 * Used by LlmService to abstract away provider-specific implementations
 */
interface LlmProviderInterface
{
    /**
     * Send a completion request to the LLM
     *
     * @param string $systemPrompt The system/context prompt
     * @param string $userPrompt The user message/prompt
     * @param array $options Additional options (max_tokens, temperature, etc.)
     * @return array ['success' => bool, 'text' => string, 'tokens_used' => int, 'model' => string, 'error' => string|null]
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): array;

    /**
     * Check if the provider is available and responding
     *
     * @return bool True if provider is reachable and functional
     */
    public function isAvailable(): bool;

    /**
     * Get the provider name
     *
     * @return string Provider identifier (e.g., 'ollama', 'openai', 'anthropic')
     */
    public function getName(): string;

    /**
     * Get available models from this provider
     *
     * @return array List of available model names
     */
    public function getModels(): array;

    /**
     * Get provider health status with details
     *
     * @return array ['status' => string, 'models' => array, 'version' => string, 'error' => string|null]
     */
    public function getHealth(): array;
}
