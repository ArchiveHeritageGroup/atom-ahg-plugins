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
     */
    public static function get3DPrompt($context = 'default')
    {
        $base = 'These are 6 views of the same physical object: front, back, left, right, top, and detail. Describe this object: shape, proportions, color, surface texture, material appearance, any markings or inscriptions, damage or wear, and functional features.';

        $key = strtolower($context);
        if ($key === 'cco') {
            $base .= ' This is a museum or heritage object. Note cultural significance, period, and construction technique if apparent.';
        } elseif ($key === 'isad') {
            $base .= ' This is an archival artefact. Note historical context, provenance indicators, and condition.';
        }

        return $base;
    }

    /**
     * 3D object prompt for cloud LLM (detailed for Claude).
     */
    public static function get3DCloudPrompt($context = 'default')
    {
        $base = 'You are examining a 3D-scanned heritage object from multiple angles. The six images show front, back, left, right, top, and detail views of the same physical object. Provide a comprehensive physical description suitable for museum cataloguing: object identification, form, materials, surface treatment, condition, construction technique, and notable features.';

        $key = strtolower($context);
        if ($key === 'cco') {
            $base .= ' Apply CCO (Cataloguing Cultural Objects) descriptive standards. Note cultural significance, period attribution, and Spectrum 5.1 condition indicators.';
        } elseif ($key === 'isad') {
            $base .= ' Apply ISAD(G) archival description principles. Note provenance, historical context, and conservation status.';
        }

        return $base . "\n\nProvide:\n"
            . "1. A one-sentence identification (what is this object?)\n"
            . "2. A detailed physical description (3-5 sentences) covering form, materials, dimensions impression, surface, and condition\n"
            . "3. Notable features: markings, inscriptions, damage, repairs, decorative elements\n"
            . "\nRespond in plain text without headings or bullet points.";
    }
}
