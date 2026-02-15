<?php
/**
 * AHG Voice — LLM Prompt Templates per descriptive standard / sector.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class VoicePromptTemplates
{
    /**
     * Context-aware prompt templates keyed by sector/standard.
     */
    public static function getPrompt($context = 'default')
    {
        $prompts = [
            'isad' => 'Describe this archival image. What is depicted? Note any visible text, dates, people, buildings, or landmarks. Describe the physical condition if apparent. Use formal archival description language.',

            'cco' => 'Describe this museum object. Focus on physical characteristics, materials, artistic style, condition, and any identifying marks or inscriptions.',

            'marc' => 'Describe this library item image. Note the cover content, text legibility, binding condition, and any visible cataloging information.',

            'vra' => 'Describe this artwork or visual resource. Note the medium, style, composition, subject matter, and artistic technique.',

            'iptc' => 'Provide a general-purpose description of this image suitable for alt text and search indexing. Be concise but comprehensive.',

            'default' => 'Describe this image in detail. Note what is depicted, any visible text, the setting, and notable features. Keep the description suitable for use as archival metadata.',
        ];

        $key = strtolower($context);

        return $prompts[$key] ?? $prompts['default'];
    }

    /**
     * Cloud-optimized prompts (more detailed for Claude).
     */
    public static function getCloudPrompt($context = 'default')
    {
        $base = self::getPrompt($context);

        return $base . "\n\nProvide a structured description with:\n"
            . "1. A one-sentence summary suitable for alt text\n"
            . "2. A detailed description (2-4 sentences) suitable for archival scope and content\n"
            . "3. Any notable features: visible text, dates, people, locations, condition\n"
            . "\nRespond in plain text without headings or bullet points.";
    }
}
