<?php
require_once dirname(__FILE__)."/../../../lib/DAMConstants.php";

use Illuminate\Database\Capsule\Manager as DB;

class ahgDamActions extends sfActions
{
    public function executeDashboard(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Get DAM statistics
        $this->totalAssets = DB::table('display_object_config')
            ->where('object_type', 'dam')
            ->count();

        $this->withDigitalObjects = DB::table('display_object_config as doc')
            ->join('digital_object as do', 'doc.object_id', '=', 'do.object_id')
            ->where('doc.object_type', 'dam')
            ->whereNull('do.parent_id')
            ->count();

        $this->withIptcMetadata = DB::table('display_object_config as doc')
            ->join('dam_iptc_metadata as iptc', 'doc.object_id', '=', 'iptc.object_id')
            ->where('doc.object_type', 'dam')
            ->count();

        // Get recent DAM items
        $this->recentAssets = DB::table('information_object as io')
            ->join('object as o', 'io.id', '=', 'o.id')
            ->join('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('dam_iptc_metadata as iptc', 'io.id', '=', 'iptc.object_id')
            ->where('doc.object_type', 'dam')
            ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug', 'o.created_at', 'iptc.creator', 'iptc.headline')
            ->orderBy('o.created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Get media type breakdown
        $this->mediaTypes = DB::table('display_object_config as doc')
            ->join('digital_object as do', 'doc.object_id', '=', 'do.object_id')
            ->where('doc.object_type', 'dam')
            ->whereNull('do.parent_id')
            ->select(DB::raw('SUBSTRING_INDEX(do.mime_type, "/", 1) as media_type'), DB::raw('COUNT(*) as count'))
            ->groupBy('media_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        // Get license breakdown
        $this->licenseTypes = DB::table('display_object_config as doc')
            ->join('dam_iptc_metadata as iptc', 'doc.object_id', '=', 'iptc.object_id')
            ->where('doc.object_type', 'dam')
            ->whereNotNull('iptc.license_type')
            ->select('iptc.license_type', DB::raw('COUNT(*) as count'))
            ->groupBy('iptc.license_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    public function executeCreate(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Get repositories for dropdown
        $this->repositories = DB::table('repository as r')
            ->join('actor_i18n as ai', function($j) {
                $j->on('r.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select('r.id', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();

        // Get parent options (top-level DAM collections)
        $this->parents = DB::table('information_object as io')
            ->join('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->where('doc.object_type', 'dam')
            ->where('io.parent_id', 1)
            ->select('io.id', 'i18n.title', 'io.identifier')
            ->orderBy('i18n.title')
            ->get()
            ->toArray();

        // Get levels of description
        $levels = DB::table("level_of_description_sector as los")
            ->join("term", "los.term_id", "=", "term.id")
            ->join("term_i18n", function($j) {
                $j->on("term.id", "=", "term_i18n.id")->where("term_i18n.culture", "=", "en");
            })
            ->where("los.sector", "dam")
            ->orderBy("los.display_order")
            ->select("term.id", "term_i18n.name")
            ->get();
        $this->levels = [];
        foreach ($levels as $level) {
            $this->levels[$level->id] = $level->name;
        }

        if ($request->isMethod('post')) {
            $title = $request->getParameter('title');
            $identifier = $request->getParameter('identifier');
            $repositoryId = $request->getParameter('repository_id') ?: null;
            $parentId = $request->getParameter('parent_id') ?: DAMConstants::INFORMATION_OBJECT_ROOT_ID;
            $levelId = $request->getParameter('level_of_description_id') ?: sfConfig::get('app_defaultLevelOfDescriptionId', 380);
            $scopeContent = $request->getParameter('scope_content');

            if (empty($title)) {
                $this->getUser()->setFlash('error', 'Title is required');
                return sfView::SUCCESS;
            }

            // Use Propel ORM to create the information object properly (handles nested set)
            $informationObject = new QubitInformationObject();
            $informationObject->parentId = $parentId;
            $informationObject->identifier = $identifier;
            $informationObject->setTitle($title);
            $informationObject->levelOfDescriptionId = $levelId;
            $informationObject->displayStandardId = 1691; // Photo/DAM (IPTC/XMP)
            $informationObject->sourceStandard = 'dam';

            if ($scopeContent) {
                $informationObject->setScopeAndContent($scopeContent);
            }

            if ($repositoryId) {
                $informationObject->repositoryId = $repositoryId;
            }

            // Set publication status (published)
            $informationObject->setPublicationStatus(DAMConstants::PUBLICATION_STATUS_PUBLISHED_ID);
            $informationObject->save();

            $objectId = $informationObject->id;

            // Create IPTC record with all fields
            $iptcData = [
                'object_id' => $objectId,
                'title' => $request->getParameter('iptc_title'),
                // Creator
                'creator' => $request->getParameter('iptc_creator'),
                'creator_job_title' => $request->getParameter('iptc_creator_job_title'),
                'creator_email' => $request->getParameter('iptc_creator_email'),
                'creator_phone' => $request->getParameter('iptc_creator_phone'),
                'creator_website' => $request->getParameter('iptc_creator_website'),
                'creator_city' => $request->getParameter('iptc_creator_city'),
                'creator_address' => $request->getParameter('iptc_creator_address'),
                // Content
                'headline' => $request->getParameter('iptc_headline'),
                'caption' => $request->getParameter('iptc_caption'),
                'keywords' => $request->getParameter('iptc_keywords'),
                'iptc_subject_code' => $request->getParameter('iptc_subject_code'),
                'intellectual_genre' => $request->getParameter('iptc_intellectual_genre'),
                'persons_shown' => $request->getParameter('iptc_persons_shown'),
                // Location
                'date_created' => $request->getParameter('iptc_date_created') ?: null,
                'city' => $request->getParameter('iptc_city'),
                'state_province' => $request->getParameter('iptc_state_province'),
                'country' => $request->getParameter('iptc_country'),
                'country_code' => $request->getParameter('iptc_country_code'),
                'sublocation' => $request->getParameter('iptc_sublocation'),
                // Copyright
                'credit_line' => $request->getParameter('iptc_credit_line'),
                'source' => $request->getParameter('iptc_source'),
                'copyright_notice' => $request->getParameter('iptc_copyright_notice'),
                'rights_usage_terms' => $request->getParameter('iptc_rights_usage_terms'),
                'license_type' => $request->getParameter('iptc_license_type') ?: null,
                'license_url' => $request->getParameter('iptc_license_url'),
                'license_expiry' => $request->getParameter('iptc_license_expiry') ?: null,
                // Releases
                'model_release_status' => $request->getParameter('iptc_model_release_status') ?: 'none',
                'model_release_id' => $request->getParameter('iptc_model_release_id'),
                'property_release_status' => $request->getParameter('iptc_property_release_status') ?: 'none',
                'property_release_id' => $request->getParameter('iptc_property_release_id'),
                // Artwork
                'artwork_title' => $request->getParameter('iptc_artwork_title'),
                'artwork_creator' => $request->getParameter('iptc_artwork_creator'),
                'artwork_date' => $request->getParameter('iptc_artwork_date'),
                'artwork_source' => $request->getParameter('iptc_artwork_source'),
                'artwork_copyright' => $request->getParameter('iptc_artwork_copyright'),
                // Administrative
                'job_id' => $request->getParameter('iptc_job_id'),
                'instructions' => $request->getParameter('iptc_instructions'),
                // PBCore / Film-Video Production
                'asset_type' => $request->getParameter('asset_type') ?: null,
                'genre' => $request->getParameter('genre'),
                'color_type' => $request->getParameter('color_type') ?: null,
                'audio_language' => $request->getParameter('audio_language'),
                'subtitle_language' => $request->getParameter('subtitle_language'),
                'production_company' => $request->getParameter('production_company'),
                'distributor' => $request->getParameter('distributor'),
                'broadcast_date' => $request->getParameter('broadcast_date') ?: null,
                'awards' => $request->getParameter('awards'),
                'series_title' => $request->getParameter('series_title'),
                'season_number' => $request->getParameter('season_number') ?: null,
                'episode_number' => $request->getParameter('episode_number') ?: null,
                'contributors_json' => $this->buildContributorsJson($request),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            DB::table('dam_iptc_metadata')->insert($iptcData);

            $this->getUser()->setFlash('success', 'DAM asset created successfully');
            $this->redirect('@slug?slug=' . $informationObject->slug);
        }
    }

    public function executeEditIptc(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $slug = $request->getParameter('slug');
        
        // Get the information object
        $this->resource = DB::table("information_object as io")->join("slug", "slug.object_id", "=", "io.id")->leftJoin("information_object_i18n as ioi", "ioi.id", "=", "io.id")->where("slug.slug", $slug)->select("io.*", "ioi.title", "slug.slug")->first();
        if (!$this->resource) {
            $this->forward404('Resource not found');
        }

        // Get or create IPTC metadata
        $this->iptc = DB::table('dam_iptc_metadata')
            ->where('object_id', $this->resource->id)
            ->first();

        if (!$this->iptc) {
            // Create empty record
            DB::table('dam_iptc_metadata')->insert([
                'object_id' => $this->resource->id,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->iptc = DB::table('dam_iptc_metadata')
                ->where('object_id', $this->resource->id)
                ->first();
        }

        // Get digital object info
        $this->digitalObject = DB::table('digital_object')
            ->where('object_id', $this->resource->id)
            ->whereNull('parent_id')
            ->first();

        if ($request->isMethod('post')) {
            $data = [
                // Creator
                'creator' => $request->getParameter('creator'),
                'creator_job_title' => $request->getParameter('creator_job_title'),
                'creator_address' => $request->getParameter('creator_address'),
                'creator_city' => $request->getParameter('creator_city'),
                'creator_state' => $request->getParameter('creator_state'),
                'creator_postal_code' => $request->getParameter('creator_postal_code'),
                'creator_country' => $request->getParameter('creator_country'),
                'creator_phone' => $request->getParameter('creator_phone'),
                'creator_email' => $request->getParameter('creator_email'),
                'creator_website' => $request->getParameter('creator_website'),
                
                // Content
                'headline' => $request->getParameter('headline'),
                'caption' => $request->getParameter('caption'),
                'keywords' => $request->getParameter('keywords'),
                'iptc_subject_code' => $request->getParameter('iptc_subject_code'),
                'intellectual_genre' => $request->getParameter('intellectual_genre'),
                
                // Location
                'date_created' => $request->getParameter('date_created') ?: null,
                'city' => $request->getParameter('city'),
                'state_province' => $request->getParameter('state_province'),
                'country' => $request->getParameter('country'),
                'country_code' => $request->getParameter('country_code'),
                'sublocation' => $request->getParameter('sublocation'),
                
                // Status
                'title' => $request->getParameter('iptc_title'),
                'job_id' => $request->getParameter('job_id'),
                'instructions' => $request->getParameter('instructions'),
                'credit_line' => $request->getParameter('credit_line'),
                'source' => $request->getParameter('source'),
                
                // Copyright
                'copyright_notice' => $request->getParameter('copyright_notice'),
                'rights_usage_terms' => $request->getParameter('rights_usage_terms'),
                
                // Licensing
                'license_type' => $request->getParameter('license_type') ?: null,
                'license_url' => $request->getParameter('license_url'),
                'license_expiry' => $request->getParameter('license_expiry') ?: null,
                
                // Releases
                'model_release_status' => $request->getParameter('model_release_status') ?: 'none',
                'model_release_id' => $request->getParameter('model_release_id'),
                'property_release_status' => $request->getParameter('property_release_status') ?: 'none',
                'property_release_id' => $request->getParameter('property_release_id'),
                
                // Artwork
                'artwork_title' => $request->getParameter('artwork_title'),
                'artwork_creator' => $request->getParameter('artwork_creator'),
                'artwork_date' => $request->getParameter('artwork_date'),
                'artwork_source' => $request->getParameter('artwork_source'),
                'artwork_copyright' => $request->getParameter('artwork_copyright'),
                
                // People
                'persons_shown' => $request->getParameter('persons_shown'),

                // PBCore / Film fields
                'asset_type' => $request->getParameter('asset_type') ?: null,
                'genre' => $request->getParameter('genre'),
                'color_type' => $request->getParameter('color_type') ?: null,
                'audio_language' => $request->getParameter('audio_language'),
                'subtitle_language' => $request->getParameter('subtitle_language'),
                'production_company' => $request->getParameter('production_company'),
                'distributor' => $request->getParameter('distributor'),
                'broadcast_date' => $request->getParameter('broadcast_date') ?: null,
                'awards' => $request->getParameter('awards'),
                'series_title' => $request->getParameter('series_title'),
                'episode_number' => $request->getParameter('episode_number'),
                'season_number' => $request->getParameter('season_number'),

                // Contributors JSON
                'contributors_json' => $this->buildContributorsJson($request),
                
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            DB::table('dam_iptc_metadata')
                ->where('object_id', $this->resource->id)
                ->update($data);

            $this->getUser()->setFlash('success', 'IPTC metadata saved successfully');
            
            if ($request->getParameter('save_and_continue')) {
                $this->redirect(['module' => 'dam', 'action' => 'editIptc', 'slug' => $slug]);
            } else {
                $this->redirect('@slug?slug=' . $slug);
            }
        }
    }

    public function executeExtractMetadata(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $objectId = (int) $request->getParameter('id');

        // Get digital object path
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->whereNull('parent_id')
            ->first();

        if (!$digitalObject) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode(['error' => 'No digital object found']));
        }

        // Build file path
        $uploadDir = sfConfig::get('sf_upload_dir');
        $filePath = $uploadDir . $digitalObject->path . $digitalObject->name;

        if (!file_exists($filePath)) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode(['error' => 'File not found: ' . $filePath]));
        }

        // Use Universal Metadata Extractor
        $extractor = new ahgUniversalMetadataExtractor($filePath, $digitalObject->mime_type);
        $allMetadata = $extractor->extractAll();
        $errors = $extractor->getErrors();

        if (!empty($errors)) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode(['warning' => implode(', ', $errors), 'partial' => true]));
        }

        // Map extracted metadata to IPTC schema
        $exif = $allMetadata['exif'] ?? [];
        $iptc = $allMetadata['iptc'] ?? [];
        $xmp = $allMetadata['xmp'] ?? [];
        $image = $allMetadata['image'] ?? [];

        // Helper to get first non-empty value
        $get = function(...$sources) {
            foreach ($sources as $val) {
                if (!empty($val)) return is_array($val) ? implode(', ', $val) : $val;
            }
            return null;
        };

        $data = [
            // Creator / Contact
            'creator' => $get($iptc['by_line'] ?? null, $xmp['creator'] ?? null, $exif['Artist'] ?? null),
            'creator_job_title' => $get($iptc['by_line_title'] ?? null, $xmp['AuthorsPosition'] ?? null),
            'creator_city' => $get($xmp['CreatorCity'] ?? null),
            'creator_state' => $get($xmp['CreatorRegion'] ?? null),
            'creator_country' => $get($xmp['CreatorCountry'] ?? null),
            'creator_email' => $get($xmp['CreatorWorkEmail'] ?? null),
            'creator_website' => $get($xmp['CreatorWorkURL'] ?? null),
            'creator_phone' => $get($xmp['CreatorWorkTelephone'] ?? null),
            'creator_address' => $get($xmp['CreatorAddress'] ?? null),
            'creator_postal_code' => $get($xmp['CreatorPostalCode'] ?? null),

            // Content Description
            'headline' => $get($iptc['headline'] ?? null, $xmp['Headline'] ?? null),
            'caption' => $get($iptc['caption'] ?? null, $xmp['description'] ?? null, $exif['ImageDescription'] ?? null),
            'keywords' => $get($iptc['keywords'] ?? null, $xmp['subject'] ?? null),
            'iptc_subject_code' => $get($iptc['subject_reference'] ?? null, $xmp['SubjectCode'] ?? null),
            'intellectual_genre' => $get($xmp['IntellectualGenre'] ?? null),
            'iptc_scene' => $get($xmp['Scene'] ?? null),
            'persons_shown' => $get($xmp['PersonInImage'] ?? null),

            // Location (of depicted content)
            'date_created' => $get($iptc['date_created'] ?? null, $xmp['DateCreated'] ?? null),
            'city' => $get($iptc['city'] ?? null, $xmp['City'] ?? null),
            'state_province' => $get($iptc['province_state'] ?? null, $xmp['State'] ?? null),
            'country' => $get($iptc['country'] ?? null, $xmp['Country'] ?? null),
            'country_code' => $get($iptc['country_code'] ?? null, $xmp['CountryCode'] ?? null),
            'sublocation' => $get($iptc['sublocation'] ?? null, $xmp['Location'] ?? null),

            // Administrative
            'title' => $get($iptc['object_name'] ?? null, $xmp['title'] ?? null),
            'job_id' => $get($iptc['original_transmission_reference'] ?? null, $xmp['TransmissionReference'] ?? null),
            'instructions' => $get($iptc['special_instructions'] ?? null, $xmp['Instructions'] ?? null),
            'credit_line' => $get($iptc['credit'] ?? null, $xmp['Credit'] ?? null),
            'source' => $get($iptc['source'] ?? null, $xmp['Source'] ?? null),

            // Copyright
            'copyright_notice' => $get($iptc['copyright_notice'] ?? null, $xmp['rights'] ?? null, $exif['Copyright'] ?? null),
            'rights_usage_terms' => $get($xmp['UsageTerms'] ?? null),

            // Artwork (for reproductions)
            'artwork_title' => $get($xmp['ArtworkTitle'] ?? null),
            'artwork_creator' => $get($xmp['ArtworkCreator'] ?? null),
            'artwork_date' => $get($xmp['ArtworkDateCreated'] ?? null),
            'artwork_source' => $get($xmp['ArtworkSource'] ?? null),
            'artwork_copyright' => $get($xmp['ArtworkCopyrightNotice'] ?? null),

            // Technical - Camera
            'camera_make' => $get($exif['Make'] ?? null),
            'camera_model' => $get($exif['Model'] ?? null),
            'lens' => $get($exif['LensModel'] ?? null, $exif['Lens'] ?? null),
            'focal_length' => isset($exif['FocalLength']) ? $exif['FocalLength'] . 'mm' : null,
            'aperture' => isset($exif['FNumber']) ? 'f/' . $exif['FNumber'] : null,
            'shutter_speed' => $get($exif['ExposureTime'] ?? null),
            'iso_speed' => $get($exif['ISOSpeedRatings'] ?? null, $exif['ISO'] ?? null),
            'flash_used' => isset($exif['Flash']) ? ($exif['Flash'] > 0 ? 1 : 0) : null,

            // Technical - GPS
            'gps_latitude' => $get($exif['GPSLatitude'] ?? null),
            'gps_longitude' => $get($exif['GPSLongitude'] ?? null),
            'gps_altitude' => $get($exif['GPSAltitude'] ?? null),

            // Technical - Image
            'image_width' => $get($image['width'] ?? null, $exif['ImageWidth'] ?? null, $exif['ExifImageWidth'] ?? null),
            'image_height' => $get($image['height'] ?? null, $exif['ImageHeight'] ?? null, $exif['ExifImageHeight'] ?? null),
            'resolution_x' => $get($exif['XResolution'] ?? null),
            'resolution_y' => $get($exif['YResolution'] ?? null),
            'resolution_unit' => isset($exif['ResolutionUnit']) ? ($exif['ResolutionUnit'] == 2 ? 'dpi' : 'dpcm') : null,
            'color_space' => $get($exif['ColorSpace'] ?? null),
            'bit_depth' => $get($image['bits'] ?? null, $exif['BitsPerSample'] ?? null),
            'orientation' => $get($exif['Orientation'] ?? null),

            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Clean null values for update
        $data = array_filter($data, function($v) { return $v !== null; });

        // Update or insert
        DB::table('dam_iptc_metadata')
            ->updateOrInsert(
                ['object_id' => $objectId],
                array_merge($data, ['created_at' => date('Y-m-d H:i:s')])
            );

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode([
            'success' => true,
            'extracted_fields' => count($data),
            'data' => $data,
            'raw' => $allMetadata
        ]));
    }


    public function executeBrowse(sfWebRequest $request)
    {
        $this->redirect('display/browse?type=dam');
    }

    public function executeBulkCreate(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Get repositories
        $this->repositories = DB::table('repository as r')
            ->join('actor_i18n as ai', function($j) {
                $j->on('r.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select('r.id', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();

        // Get existing DAM collections
        $this->collections = DB::table('information_object as io')
            ->join('display_object_config as doc', 'io.id', '=', 'doc.object_id')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->where('doc.object_type', 'dam')
            ->where('io.parent_id', 1)
            ->select('io.id', 'i18n.title', 'io.identifier')
            ->orderBy('i18n.title')
            ->get()
            ->toArray();
    }

    public function executeConvert(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(403);
            return sfView::NONE;
        }

        $objectId = (int) $request->getParameter('id');
        
        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => 'dam', 'updated_at' => date('Y-m-d H:i:s')]
        );

        // Create IPTC record if not exists
        $exists = DB::table('dam_iptc_metadata')->where('object_id', $objectId)->exists();
        if (!$exists) {
            DB::table('dam_iptc_metadata')->insert([
                'object_id' => $objectId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->getUser()->setFlash('success', 'Record converted to DAM type');
        $this->redirect($request->getReferer() ?: 'dam/dashboard');
    }

    /**
     * Build contributors JSON from form arrays
     */
    protected function buildContributorsJson(sfWebRequest $request)
    {
        $roles = $request->getParameter('credit_role', []);
        $names = $request->getParameter('credit_name', []);
        
        if (!is_array($roles) || !is_array($names)) {
            return null;
        }
        
        $contributors = [];
        foreach ($roles as $i => $role) {
            $name = $names[$i] ?? '';
            if (!empty($role) && !empty($name)) {
                $contributors[] = [
                    'role' => trim($role),
                    'name' => trim($name),
                ];
            }
        }
        
        return !empty($contributors) ? json_encode($contributors) : null;
    }
}