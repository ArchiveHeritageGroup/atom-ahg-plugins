<?php

class donorAgreementAddAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Initialize Laravel DB
        $this->initDatabase();

        // Get types directly from database using Laravel Query Builder
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        $this->donors = $this->getDonorsList();
        $this->agreement = null;
        $this->documents = [];

        // Pre-fill donor if passed
        $this->donorId = $request->getParameter('donor');
        $this->donor = null;
        if ($this->donorId) {
            $this->donor = QubitDonor::getById($this->donorId);
        }

        if ($request->isMethod('POST')) {
            $data = $request->getParameter('agreement', []);
            $data['created_by'] = $this->context->user->getAttribute('user_id');
            
            try {
                $id = $this->createAgreement($data);
                
                // Handle file uploads
                $uploadResult = $this->handleDocumentUploads($request, $id);
                
                // Handle reminders
                $this->handleReminders($request, $id);
                
                $this->context->user->setFlash('notice', 'Agreement created successfully. Edit to link Archival Descriptions and Accessions.' . ($uploadResult ? ' ' . $uploadResult : ''));
                $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $id]);
            } catch (Exception $e) {
                $this->context->user->setFlash('error', 'Error creating agreement: ' . $e->getMessage());
            }
        }
    }

    protected function initDatabase()
    {
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }

    protected function getAgreementTypes()
    {
        return \Illuminate\Database\Capsule\Manager::table('agreement_type')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    protected function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'pending_signature' => 'Pending Signature',
            'active' => 'Active',
            'expired' => 'Expired',
            'terminated' => 'Terminated'
        ];
    }

    protected function createAgreement($data)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        
        $db->beginTransaction();
        
        try {
            // Generate agreement number if not provided
            $agreementNumber = !empty($data['agreement_number']) 
                ? $data['agreement_number'] 
                : $this->generateAgreementNumber($data['agreement_type_id']);
            
            // Title defaults to agreement number if not provided
            $title = !empty($data['title']) ? $data['title'] : $agreementNumber;
            
            $id = \Illuminate\Database\Capsule\Manager::table('donor_agreement')->insertGetId([
                'donor_id' => !empty($data['donor_id']) ? $data['donor_id'] : null,
                'agreement_type_id' => $data['agreement_type_id'],
                'agreement_number' => $agreementNumber,
                'title' => $title,
                'status' => $data['status'] ?? 'draft',
                'effective_date' => !empty($data['effective_date']) ? $data['effective_date'] : null,
                'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                'review_date' => !empty($data['review_date']) ? $data['review_date'] : null,
                'created_by' => $data['created_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Also insert into i18n table if it exists
            try {
                \Illuminate\Database\Capsule\Manager::table('donor_agreement_i18n')->insert([
                    'id' => $id,
                    'culture' => 'en',
                    'title' => $title,
                    'description' => $data['description'] ?? null,
                ]);
            } catch (Exception $e) {
                // i18n table might not exist or have different structure - ignore
            }
            
            $db->commit();
            return $id;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    protected function handleDocumentUploads($request, $agreementId)
    {
        $uploadCount = 0;
        $errors = [];
        
        // Get files from $_FILES directly since Symfony's getFiles might not work as expected
        $files = isset($_FILES['documents']) ? $_FILES['documents'] : null;
        
        if (empty($files) || empty($files['name'])) {
            return null;
        }

        // Use AtoM's upload directory
        $baseUploadDir = sfConfig::get('sf_upload_dir');
        if (empty($baseUploadDir)) {
            $baseUploadDir = sfConfig::get('sf_root_dir') . '/uploads';
        }
        
        $uploadDir = $baseUploadDir . '/donor_agreements/' . $agreementId;
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $errors[] = 'Failed to create upload directory';
                return implode(', ', $errors);
            }
        }

        $docTypes = $request->getParameter('document_types', []);
        $docDescriptions = $request->getParameter('document_descriptions', []);

        // Handle multiple files
        if (is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if (empty($name)) {
                    continue;
                }
                
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = "Upload error for {$name}: " . $this->getUploadErrorMessage($files['error'][$i]);
                    continue;
                }
                
                $result = $this->saveDocument(
                    $agreementId,
                    $files['tmp_name'][$i],
                    $name,
                    $files['size'][$i],
                    $files['type'][$i],
                    $uploadDir,
                    $docTypes[$i] ?? 'signed_agreement',
                    $docDescriptions[$i] ?? ''
                );
                
                if ($result === true) {
                    $uploadCount++;
                } else {
                    $errors[] = $result;
                }
            }
        } else {
            // Single file
            if (!empty($files['name']) && $files['error'] === UPLOAD_ERR_OK) {
                $result = $this->saveDocument(
                    $agreementId,
                    $files['tmp_name'],
                    $files['name'],
                    $files['size'],
                    $files['type'],
                    $uploadDir,
                    $docTypes[0] ?? 'signed_agreement',
                    $docDescriptions[0] ?? ''
                );
                
                if ($result === true) {
                    $uploadCount++;
                } else {
                    $errors[] = $result;
                }
            }
        }
        
        $msg = '';
        if ($uploadCount > 0) {
            $msg = "{$uploadCount} document(s) uploaded.";
        }
        if (!empty($errors)) {
            $msg .= ' Errors: ' . implode('; ', $errors);
        }
        
        return $msg ?: null;
    }

    protected function saveDocument($agreementId, $tmpName, $originalName, $size, $mimeType, $uploadDir, $docType, $description)
    {
        // Generate unique filename
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $filepath)) {
            return "Failed to move uploaded file: {$originalName}";
        }
        
        // Make file readable
        chmod($filepath, 0644);

        try {
            \Illuminate\Database\Capsule\Manager::table('donor_agreement_document')->insert([
                'donor_agreement_id' => $agreementId,
                'document_type' => $docType,
                'filename' => $filename,
                'original_filename' => $originalName,
                'file_path' => 'uploads/donor_agreements/' . $agreementId . '/' . $filename,
                'file_size' => $size,
                'mime_type' => $mimeType,
                'description' => $description,
                'uploaded_by' => $this->context->user->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            return true;
        } catch (Exception $e) {
            // Remove uploaded file if DB insert fails
            @unlink($filepath);
            return "Database error for {$originalName}: " . $e->getMessage();
        }
    }

    protected function getUploadErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];
        
        return $errors[$errorCode] ?? 'Unknown error';
    }

    protected function handleReminders($request, $agreementId)
    {
        $reminders = $request->getParameter('reminders', []);
        
        foreach ($reminders as $reminder) {
            if (empty($reminder['reminder_date'])) {
                continue;
            }
            
            $insertData = [
                'donor_agreement_id' => $agreementId,
                'reminder_type' => $reminder['reminder_type'] ?? 'review_due',
                'subject' => $reminder['message'] ?? 'Reminder',
                'reminder_date' => $reminder['reminder_date'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            if (!empty($reminder['notify_email'])) {
                $insertData['notification_recipients'] = $reminder['notify_email'];
            }
            
            \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder')->insert($insertData);
        }
    }

    protected function generateAgreementNumber($typeId)
    {
        $type = \Illuminate\Database\Capsule\Manager::table('agreement_type')
            ->where('id', $typeId)
            ->first();
        
        $prefix = $type && $type->prefix ? $type->prefix : 'AGR';
        $year = date('Y');
        
        $count = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('agreement_number', 'LIKE', $prefix . '-' . $year . '-%')
            ->count();
        
        return sprintf('%s-%s-%04d', $prefix, $year, $count + 1);
    }

    protected function getDonorsList()
    {
        $donors = [];
        try {
            $results = \Illuminate\Database\Capsule\Manager::table('donor')
                ->join('actor', 'donor.id', '=', 'actor.id')
                ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
                ->select(['donor.id', 'actor_i18n.authorized_form_of_name as name'])
                ->where('actor_i18n.culture', 'en')
                ->orderBy('actor_i18n.authorized_form_of_name')
                ->get();
            foreach ($results as $row) {
                $donors[] = $row;
            }
        } catch (Exception $e) {
            error_log("Error fetching donors: " . $e->getMessage());
        }
        return $donors;
    }
}
