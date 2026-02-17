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
            'isad' => 'You are an archivist describing a digitised record. This is NOT a cartoon, illustration, or modern image — it is a scan or photograph of a real historical document, photograph, map, artwork, or artefact held in an archive. Describe what is depicted: visible text, dates, people, buildings, landmarks, or scenes. Note the physical condition if apparent. Use formal archival description language. Be factual and specific.',

            'cco' => 'You are a museum cataloguer describing a heritage object or artwork. This is NOT a cartoon or modern illustration — it is a photograph, scan, or tracing of a real museum object, artefact, rock art, painting, sculpture, or cultural item. Consider that black silhouette figures on white backgrounds are likely tracings of ancient rock art or cave paintings. Describe the subject matter, artistic style, composition, figures depicted, and any cultural or historical significance you can identify. Be specific about what you observe.',

            'marc' => 'You are a librarian describing a catalogue record image. This is a scan or photograph of a real book, manuscript, periodical, or library item. Note the cover content, title, author, text legibility, binding style and condition, and any visible cataloging information. Be factual.',

            'vra' => 'You are an art historian describing an artwork or visual resource. This is a real artwork, not a cartoon. Describe the medium, artistic style and period, composition, subject matter, technique, and any cultural context. Identify the tradition or school if recognisable.',

            'iptc' => 'Describe this image for a digital asset catalogue. Provide a factual, objective description suitable for alt text and search indexing. Note key subjects, setting, and notable features. Be concise but comprehensive.',

            'default' => 'You are describing an image from a cultural heritage collection (archive, museum, library, or gallery). This is NOT a cartoon or modern illustration — it is a real historical or cultural item. Describe what is depicted factually: subjects, setting, visible text, notable features, and condition. Keep the description suitable for use as archival metadata.',
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

    /**
     * 3D object prompt for local LLM (concise for llava:7b).
     *
     * Includes filename and existing scope_and_content as context hints
     * so the LLM can produce descriptions aligned with the catalogue record.
     */
    public static function get3DPrompt($context = 'default', $fileName = '', $scopeContent = '')
    {
        $base = 'This collage shows 6 views of a 3D-scanned heritage object: Front, Back, Left, Right, Top, and Detail.';

        // Add filename context — often contains meaningful identifiers
        if (!empty($fileName)) {
            $readable = str_replace(['_', '-', '.'], ' ', $fileName);
            $base .= ' The object is catalogued as: "' . $readable . '".';
        }

        // Add existing description as reference context
        if (!empty($scopeContent)) {
            $base .= ' Existing catalogue description: "' . substr($scopeContent, 0, 300) . '".';
            $base .= ' Write a physical description of the object that complements this. Focus on what you can observe: form, material appearance, construction, surface texture, color, condition, and notable physical features. Do not repeat the catalogue description.';
        } else {
            $base .= ' Describe this object: its form, material appearance, construction, surface texture, color, condition, and notable physical features.';
        }

        $key = strtolower($context);
        if ($key === 'cco') {
            $base .= ' This is a museum or heritage object.';
        } elseif ($key === 'isad') {
            $base .= ' This is an archival artefact.';
        }

        $base .= ' Be concise (2-4 sentences). Respond in plain text.';

        return $base;
    }

    /**
     * 3D object prompt for cloud LLM (detailed for Claude).
     */
    public static function get3DCloudPrompt($context = 'default', $fileName = '', $scopeContent = '')
    {
        $base = 'You are examining a 3D-scanned heritage object from multiple angles. This collage shows the same physical object from 6 labeled viewpoints: Front, Back, Left, Right, Top, and Detail.';

        if (!empty($fileName)) {
            $readable = str_replace(['_', '-', '.'], ' ', $fileName);
            $base .= ' The object is catalogued as: "' . $readable . '".';
        }

        if (!empty($scopeContent)) {
            $base .= ' Existing catalogue description: "' . substr($scopeContent, 0, 500) . '".';
            $base .= ' Write a complementary physical description for the "Extent and medium" field. Focus on what you observe: form, dimensions impression, materials, surface treatment, construction technique, condition, and notable physical features. Do not duplicate the existing description.';
        } else {
            $base .= ' Provide a comprehensive physical description suitable for the "Extent and medium" museum catalogue field: object identification, form, materials, surface treatment, condition, construction technique, and notable features.';
        }

        $key = strtolower($context);
        if ($key === 'cco') {
            $base .= ' Apply CCO descriptive standards.';
        } elseif ($key === 'isad') {
            $base .= ' Apply ISAD(G) archival description principles.';
        }

        return $base . "\n\nRespond in 2-5 sentences of plain text without headings or bullet points.";
    }
}
