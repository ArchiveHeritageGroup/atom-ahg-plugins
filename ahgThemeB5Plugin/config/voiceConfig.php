<?php
/**
 * AHG Voice Commands — Configuration
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
return [
    // LLM provider: 'local' | 'cloud' | 'hybrid'
    'llm_provider'      => 'local',

    // Local LLM (Ollama)
    'local_llm_url'     => 'http://localhost:11434',
    'local_llm_model'   => 'llava:7b',
    'local_llm_timeout' => 120,

    // Cloud LLM (Anthropic)
    'anthropic_api_key'  => '', // Set via AHG Settings or here
    'cloud_model'        => 'claude-sonnet-4-20250514',
    'daily_cloud_limit'  => 50,

    // Audit
    'audit_ai_calls'     => true,
];
