<?php
/**
 * CCO Template Configurations
 * 
 * Defines field requirements for different object type templates.
 * Each template specifies which fields are required, recommended, or optional.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class ahgCCOTemplates
{
    const TEMPLATE_PAINTING = 'painting';
    const TEMPLATE_SCULPTURE = 'sculpture';
    const TEMPLATE_PHOTOGRAPH = 'photograph';
    const TEMPLATE_PRINT = 'print';
    const TEMPLATE_DRAWING = 'drawing';
    const TEMPLATE_TEXTILE = 'textile';
    const TEMPLATE_CERAMIC = 'ceramic';
    const TEMPLATE_FURNITURE = 'furniture';
    const TEMPLATE_DECORATIVE = 'decorative_art';
    const TEMPLATE_ARCHIVE = 'archival_object';
    const TEMPLATE_BOOK = 'book_manuscript';
    const TEMPLATE_NUMISMATIC = 'numismatic';
    const TEMPLATE_NATURAL = 'natural_specimen';
    const TEMPLATE_GENERIC = 'generic';

    /**
     * Get all available templates
     */
    public static function getTemplates()
    {
        return [
            self::TEMPLATE_PAINTING => [
                'label' => 'Painting',
                'description' => 'Oil, acrylic, watercolor, tempera, and other painted works on canvas, panel, or paper.',
                'icon' => 'fa-paint-brush',
                'defaultWorkType' => 'painting',
                'fields' => self::getPaintingFields()
            ],
            self::TEMPLATE_SCULPTURE => [
                'label' => 'Sculpture',
                'description' => 'Three-dimensional works in stone, bronze, wood, mixed media, etc.',
                'icon' => 'fa-cube',
                'defaultWorkType' => 'sculpture',
                'fields' => self::getSculptureFields()
            ],
            self::TEMPLATE_PHOTOGRAPH => [
                'label' => 'Photograph',
                'description' => 'Photographic prints, negatives, transparencies, and digital photographs.',
                'icon' => 'fa-camera',
                'defaultWorkType' => 'photograph',
                'fields' => self::getPhotographFields()
            ],
            self::TEMPLATE_PRINT => [
                'label' => 'Print',
                'description' => 'Etchings, engravings, lithographs, woodcuts, screenprints, etc.',
                'icon' => 'fa-clone',
                'defaultWorkType' => 'print',
                'fields' => self::getPrintFields()
            ],
            self::TEMPLATE_DRAWING => [
                'label' => 'Drawing',
                'description' => 'Works on paper: pencil, charcoal, pastel, ink drawings.',
                'icon' => 'fa-pencil',
                'defaultWorkType' => 'drawing',
                'fields' => self::getDrawingFields()
            ],
            self::TEMPLATE_TEXTILE => [
                'label' => 'Textile',
                'description' => 'Woven, embroidered, printed, or constructed fabric objects.',
                'icon' => 'fa-th',
                'defaultWorkType' => 'textile',
                'fields' => self::getTextileFields()
            ],
            self::TEMPLATE_CERAMIC => [
                'label' => 'Ceramic',
                'description' => 'Pottery, porcelain, earthenware, stoneware objects.',
                'icon' => 'fa-coffee',
                'defaultWorkType' => 'ceramic',
                'fields' => self::getCeramicFields()
            ],
            self::TEMPLATE_FURNITURE => [
                'label' => 'Furniture',
                'description' => 'Chairs, tables, cabinets, and other functional objects.',
                'icon' => 'fa-bed',
                'defaultWorkType' => 'furniture',
                'fields' => self::getFurnitureFields()
            ],
            self::TEMPLATE_DECORATIVE => [
                'label' => 'Decorative Art',
                'description' => 'Metalwork, glass, jewelry, and other decorative objects.',
                'icon' => 'fa-diamond',
                'defaultWorkType' => 'decorative art',
                'fields' => self::getDecorativeFields()
            ],
            self::TEMPLATE_ARCHIVE => [
                'label' => 'Archival Object',
                'description' => 'Documents, manuscripts, letters, and paper-based archives.',
                'icon' => 'fa-file-text-o',
                'defaultWorkType' => 'document',
                'fields' => self::getArchivalFields()
            ],
            self::TEMPLATE_BOOK => [
                'label' => 'Book/Manuscript',
                'description' => 'Printed books, illuminated manuscripts, rare books.',
                'icon' => 'fa-book',
                'defaultWorkType' => 'book',
                'fields' => self::getBookFields()
            ],
            self::TEMPLATE_NUMISMATIC => [
                'label' => 'Numismatic',
                'description' => 'Coins, medals, tokens, and currency.',
                'icon' => 'fa-circle-o',
                'defaultWorkType' => 'coin',
                'fields' => self::getNumismaticFields()
            ],
            self::TEMPLATE_NATURAL => [
                'label' => 'Natural Specimen',
                'description' => 'Botanical, geological, paleontological, and zoological specimens.',
                'icon' => 'fa-leaf',
                'defaultWorkType' => 'specimen',
                'fields' => self::getNaturalSpecimenFields()
            ],
            self::TEMPLATE_GENERIC => [
                'label' => 'Generic Object',
                'description' => 'General template for objects not fitting other categories.',
                'icon' => 'fa-archive',
                'defaultWorkType' => 'object',
                'fields' => self::getGenericFields()
            ]
        ];
    }

    /**
     * Field requirement levels
     */
    const REQUIRED = 'required';
    const RECOMMENDED = 'recommended';
    const OPTIONAL = 'optional';
    const HIDDEN = 'hidden';

    /**
     * Painting template field configuration
     */
    public static function getPaintingFields()
    {
        return [
            // Object/Work
            'work_type' => self::REQUIRED,
            'work_type_qualifier' => self::OPTIONAL,
            'components_count' => self::OPTIONAL,
            'object_number' => self::REQUIRED,
            
            // Titles
            'title' => self::REQUIRED,
            'title_type' => self::REQUIRED,
            'title_language' => self::OPTIONAL,
            'alternate_titles' => self::OPTIONAL,
            
            // Creation
            'creator_display' => self::REQUIRED,
            'creator' => self::REQUIRED,
            'creator_role' => self::REQUIRED,
            'attribution_qualifier' => self::RECOMMENDED,
            'creation_date_display' => self::REQUIRED,
            'creation_date_earliest' => self::RECOMMENDED,
            'creation_date_latest' => self::RECOMMENDED,
            'creation_place' => self::RECOMMENDED,
            'culture' => self::OPTIONAL,
            
            // Styles
            'style' => self::RECOMMENDED,
            'period' => self::OPTIONAL,
            'school_group' => self::OPTIONAL,
            
            // Measurements
            'dimensions_display' => self::REQUIRED,
            'height_value' => self::RECOMMENDED,
            'width_value' => self::RECOMMENDED,
            'depth_value' => self::OPTIONAL,
            'weight_value' => self::OPTIONAL,
            'dimension_notes' => self::OPTIONAL,
            
            // Materials
            'materials_display' => self::REQUIRED,
            'materials' => self::RECOMMENDED,
            'techniques' => self::RECOMMENDED,
            'support' => self::REQUIRED,
            
            // Subject
            'subject_display' => self::RECOMMENDED,
            'subjects_depicted' => self::RECOMMENDED,
            'iconography' => self::OPTIONAL,
            'named_subjects' => self::OPTIONAL,
            
            // Inscriptions
            'inscriptions' => self::OPTIONAL,
            'signature' => self::RECOMMENDED,
            'marks' => self::OPTIONAL,
            
            // State/Edition
            'edition_number' => self::HIDDEN,
            'edition_size' => self::HIDDEN,
            'state' => self::HIDDEN,
            'impression_quality' => self::HIDDEN,
            
            // Description
            'description' => self::RECOMMENDED,
            'physical_description' => self::OPTIONAL,
            
            // Condition
            'condition_summary' => self::RECOMMENDED,
            'condition_notes' => self::OPTIONAL,
            
            // Location
            'repository' => self::REQUIRED,
            'location_within_repository' => self::RECOMMENDED,
            'credit_line' => self::RECOMMENDED,
            
            // Related
            'related_works' => self::OPTIONAL,
            'relationship_type' => self::OPTIONAL,
            
            // Rights
            'rights_statement' => self::RECOMMENDED,
            'copyright_holder' => self::OPTIONAL,
            'reproduction_conditions' => self::OPTIONAL
        ];
    }

    /**
     * Sculpture template
     */
    public static function getSculptureFields()
    {
        $fields = self::getPaintingFields();
        
        // Customize for sculpture
        $fields['depth_value'] = self::REQUIRED;
        $fields['weight_value'] = self::RECOMMENDED;
        $fields['support'] = self::HIDDEN;
        
        return $fields;
    }

    /**
     * Photograph template
     */
    public static function getPhotographFields()
    {
        $fields = self::getPaintingFields();
        
        // Customize for photographs
        $fields['support'] = self::RECOMMENDED;
        $fields['edition_number'] = self::OPTIONAL;
        $fields['edition_size'] = self::OPTIONAL;
        
        // Add photography-specific field requirements
        return $fields;
    }

    /**
     * Print template
     */
    public static function getPrintFields()
    {
        $fields = self::getPaintingFields();
        
        // Prints need edition information
        $fields['edition_number'] = self::REQUIRED;
        $fields['edition_size'] = self::REQUIRED;
        $fields['state'] = self::RECOMMENDED;
        $fields['impression_quality'] = self::RECOMMENDED;
        
        return $fields;
    }

    /**
     * Drawing template
     */
    public static function getDrawingFields()
    {
        return self::getPaintingFields();
    }

    /**
     * Textile template
     */
    public static function getTextileFields()
    {
        $fields = self::getPaintingFields();
        
        // Textiles often don't have individual creators
        $fields['creator'] = self::RECOMMENDED;
        $fields['culture'] = self::REQUIRED;
        
        return $fields;
    }

    /**
     * Ceramic template
     */
    public static function getCeramicFields()
    {
        $fields = self::getPaintingFields();
        
        $fields['depth_value'] = self::REQUIRED;
        $fields['weight_value'] = self::OPTIONAL;
        $fields['culture'] = self::RECOMMENDED;
        $fields['support'] = self::HIDDEN;
        
        return $fields;
    }

    /**
     * Furniture template
     */
    public static function getFurnitureFields()
    {
        $fields = self::getPaintingFields();
        
        $fields['depth_value'] = self::REQUIRED;
        $fields['weight_value'] = self::OPTIONAL;
        $fields['support'] = self::HIDDEN;
        $fields['signature'] = self::OPTIONAL;
        $fields['marks'] = self::RECOMMENDED;
        
        return $fields;
    }

    /**
     * Decorative art template
     */
    public static function getDecorativeFields()
    {
        $fields = self::getPaintingFields();
        
        $fields['depth_value'] = self::RECOMMENDED;
        $fields['weight_value'] = self::OPTIONAL;
        $fields['support'] = self::HIDDEN;
        $fields['marks'] = self::RECOMMENDED;
        
        return $fields;
    }

    /**
     * Archival object template
     */
    public static function getArchivalFields()
    {
        $fields = self::getPaintingFields();
        
        // Archives often don't have creators in art sense
        $fields['creator'] = self::OPTIONAL;
        $fields['creator_role'] = self::OPTIONAL;
        $fields['style'] = self::HIDDEN;
        $fields['subject_display'] = self::REQUIRED;
        
        return $fields;
    }

    /**
     * Book/Manuscript template
     */
    public static function getBookFields()
    {
        $fields = self::getPaintingFields();
        
        $fields['components_count'] = self::RECOMMENDED;
        
        return $fields;
    }

    /**
     * Numismatic template
     */
    public static function getNumismaticFields()
    {
        $fields = self::getPaintingFields();
        
        $fields['weight_value'] = self::REQUIRED;
        $fields['depth_value'] = self::HIDDEN;
        $fields['support'] = self::HIDDEN;
        $fields['inscriptions'] = self::REQUIRED;
        
        return $fields;
    }

    /**
     * Natural specimen template
     */
    public static function getNaturalSpecimenFields()
    {
        $fields = self::getPaintingFields();
        
        // Natural specimens don't have creators
        $fields['creator'] = self::HIDDEN;
        $fields['creator_display'] = self::HIDDEN;
        $fields['creator_role'] = self::HIDDEN;
        $fields['attribution_qualifier'] = self::HIDDEN;
        $fields['style'] = self::HIDDEN;
        $fields['signature'] = self::HIDDEN;
        
        // Collection info is important
        $fields['creation_place'] = self::REQUIRED;
        $fields['creation_date_display'] = self::REQUIRED;
        
        return $fields;
    }

    /**
     * Generic object template
     */
    public static function getGenericFields()
    {
        return self::getPaintingFields();
    }

    /**
     * Get template by ID
     */
    public static function getTemplate($templateId)
    {
        $templates = self::getTemplates();
        return isset($templates[$templateId]) ? $templates[$templateId] : $templates[self::TEMPLATE_GENERIC];
    }

    /**
     * Get required fields for a template
     */
    public static function getRequiredFields($templateId)
    {
        $template = self::getTemplate($templateId);
        $fields = $template['fields'];
        
        return array_keys(array_filter($fields, function($level) {
            return $level === self::REQUIRED;
        }));
    }

    /**
     * Get field level for template
     */
    public static function getFieldLevel($templateId, $fieldName)
    {
        $template = self::getTemplate($templateId);
        return isset($template['fields'][$fieldName]) ? $template['fields'][$fieldName] : self::OPTIONAL;
    }

    /**
     * Check if field is visible in template
     */
    public static function isFieldVisible($templateId, $fieldName)
    {
        return self::getFieldLevel($templateId, $fieldName) !== self::HIDDEN;
    }

    /**
     * Validate record against template requirements
     */
    public static function validateRecord($templateId, $recordData)
    {
        $errors = [];
        $warnings = [];
        
        $requiredFields = self::getRequiredFields($templateId);
        $template = self::getTemplate($templateId);
        $fieldDefs = ahgCCOFieldDefinitions::getAllCategories();
        
        foreach ($requiredFields as $fieldName) {
            $value = isset($recordData[$fieldName]) ? $recordData[$fieldName] : null;
            
            if (empty($value)) {
                // Find field label
                $label = $fieldName;
                foreach ($fieldDefs as $category) {
                    if (isset($category['fields'][$fieldName])) {
                        $label = $category['fields'][$fieldName]['label'];
                        break;
                    }
                }
                
                $errors[] = [
                    'field' => $fieldName,
                    'label' => $label,
                    'message' => sprintf('%s is required', $label)
                ];
            }
        }
        
        // Check recommended fields
        foreach ($template['fields'] as $fieldName => $level) {
            if ($level === self::RECOMMENDED) {
                $value = isset($recordData[$fieldName]) ? $recordData[$fieldName] : null;
                
                if (empty($value)) {
                    $label = $fieldName;
                    foreach ($fieldDefs as $category) {
                        if (isset($category['fields'][$fieldName])) {
                            $label = $category['fields'][$fieldName]['label'];
                            break;
                        }
                    }
                    
                    $warnings[] = [
                        'field' => $fieldName,
                        'label' => $label,
                        'message' => sprintf('%s is recommended', $label)
                    ];
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'completeness' => self::calculateCompleteness($templateId, $recordData)
        ];
    }

    /**
     * Calculate record completeness percentage
     */
    public static function calculateCompleteness($templateId, $recordData)
    {
        $template = self::getTemplate($templateId);
        $visibleFields = array_filter($template['fields'], function($level) {
            return $level !== self::HIDDEN;
        });
        
        $totalFields = count($visibleFields);
        $filledFields = 0;
        
        foreach (array_keys($visibleFields) as $fieldName) {
            if (!empty($recordData[$fieldName])) {
                $filledFields++;
            }
        }
        
        return $totalFields > 0 ? round(($filledFields / $totalFields) * 100) : 0;
    }
}
