<?php

class donorAgreementEditAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        
        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }
        
        // Initialize Laravel DB
        $this->initDatabase();
        
        // Get types and statuses
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        
        // Get agreement
        $this->agreement = \Illuminate\Database\Capsule\Manager::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($join) {
                $join->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->where('da.id', $id)
            ->select(['da.*', 'dai.title as i18n_title', 'dai.description as i18n_description'])
            ->first();
        
        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }
        
        // Use i18n values if main ones empty
        if (empty($this->agreement->title) && !empty($this->agreement->i18n_title)) {
            $this->agreement->title = $this->agreement->i18n_title;
        }
        if (empty($this->agreement->description) && !empty($this->agreement->i18n_description)) {
            $this->agreement->description = $this->agreement->i18n_description;
        }
        
        // Get documents
        $this->documents = \Illuminate\Database\Capsule\Manager::table('donor_agreement_document')
            ->where('donor_agreement_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
        
        // Get donor info
        $this->donorId = $this->agreement->donor_id;
        $this->donor = null;
        if ($this->donorId) {
            $this->donor = \Illuminate\Database\Capsule\Manager::table('donor as d')
                ->join('actor as a', 'd.id', '=', 'a.id')
                ->leftJoin('actor_i18n as ai', function($join) {
                    $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                })
                ->where('d.id', $this->donorId)
                ->select(['d.id', 'ai.authorized_form_of_name as name'])
                ->first();
        }

        // Get linked records
        $this->linkedRecords = \Illuminate\Database\Capsule\Manager::table('donor_agreement_record as dar')
            ->join('information_object as io', 'dar.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('dar.agreement_id', $id)
            ->select(['dar.id as link_id', 'io.id', 'io.identifier', 's.slug', 'ioi.title'])
            ->get()
            ->all();

        // Get linked accessions
        $this->linkedAccessions = \Illuminate\Database\Capsule\Manager::table('donor_agreement_accession as daa')
            ->join('accession as acc', 'daa.accession_id', '=', 'acc.id')
            ->leftJoin('accession_i18n as acci', function($join) {
                $join->on('acc.id', '=', 'acci.id')->where('acci.culture', '=', 'en');
            })
            ->where('daa.donor_agreement_id', $id)
            ->select(['daa.id as link_id', 'acc.id', 'acc.identifier', 'acci.title'])
            ->get()
            ->all();

        // Get existing reminders
        $this->reminders = \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder')
            ->where('donor_agreement_id', $id)
            ->orderBy('reminder_date', 'asc')
            ->get()
            ->all();
        
        if ($request->isMethod('POST')) {
            $this->processForm($request, $id);
        }
    }

    protected function initDatabase()
    {
        static $initialized = false;
        
        if ($initialized) {
            return;
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';

        $config = include(sfConfig::get('sf_root_dir') . '/config/config.php');
        
        $dsn = $config['all']['propel']['param']['dsn'];
        preg_match('/dbname=([^;]+)/', $dsn, $matches);
        $dbname = $matches[1] ?? 'archive';
        
        preg_match('/host=([^;]+)/', $dsn, $hostMatches);
        $host = $hostMatches[1] ?? 'localhost';

        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $host,
            'database'  => $dbname,
            'username'  => $config['all']['propel']['param']['username'],
            'password'  => $config['all']['propel']['param']['password'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();

        $initialized = true;
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

    protected function processForm($request, $id)
    {
        $data = $request->getParameter('agreement', []);
        $errors = [];
        
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $db->beginTransaction();
        
        try {
            // Update main record
            \Illuminate\Database\Capsule\Manager::table('donor_agreement')
                ->where('id', $id)
                ->update([
                    'donor_id' => !empty($data['donor_id']) ? $data['donor_id'] : null,
                    'agreement_type_id' => $data['agreement_type_id'],
                    'agreement_number' => $data['agreement_number'] ?: null,
                    'title' => $data['title'] ?: $data['agreement_number'],
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'] ?? 'draft',
                    // Donor info
                    'donor_name' => $data['donor_name'] ?? null,
                    'donor_contact_info' => $data['donor_contact_info'] ?? null,
                    // Institution info
                    'institution_name' => $data['institution_name'] ?? null,
                    'institution_contact_info' => $data['institution_contact_info'] ?? null,
                    'repository_representative' => $data['repository_representative'] ?? null,
                    'repository_representative_title' => $data['repository_representative_title'] ?? null,
                    // Legal representative
                    'legal_representative' => $data['legal_representative'] ?? null,
                    'legal_representative_title' => $data['legal_representative_title'] ?? null,
                    'legal_representative_contact' => $data['legal_representative_contact'] ?? null,
                    // Dates
                    'agreement_date' => !empty($data['agreement_date']) ? $data['agreement_date'] : null,
                    'effective_date' => !empty($data['effective_date']) ? $data['effective_date'] : null,
                    'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                    'review_date' => !empty($data['review_date']) ? $data['review_date'] : null,
                    'termination_date' => !empty($data['termination_date']) ? $data['termination_date'] : null,
                    'termination_reason' => $data['termination_reason'] ?? null,
                    // Scope & transfer
                    'scope_description' => $data['scope_description'] ?? null,
                    'extent_statement' => $data['extent_statement'] ?? null,
                    'transfer_method' => $data['transfer_method'] ?? null,
                    'transfer_date' => !empty($data['transfer_date']) ? $data['transfer_date'] : null,
                    'received_by' => $data['received_by'] ?? null,
                    // Financial
                    'has_financial_terms' => !empty($data['has_financial_terms']) ? 1 : 0,
                    'purchase_amount' => !empty($data['purchase_amount']) ? $data['purchase_amount'] : null,
                    'currency' => $data['currency'] ?? 'ZAR',
                    'payment_terms' => $data['payment_terms'] ?? null,
                    // Terms
                    'general_terms' => $data['general_terms'] ?? null,
                    'special_conditions' => $data['special_conditions'] ?? null,
                    // Signatures
                    'donor_signature_date' => !empty($data['donor_signature_date']) ? $data['donor_signature_date'] : null,
                    'donor_signature_name' => $data['donor_signature_name'] ?? null,
                    'repository_signature_date' => !empty($data['repository_signature_date']) ? $data['repository_signature_date'] : null,
                    'repository_signature_name' => $data['repository_signature_name'] ?? null,
                    'witness_name' => $data['witness_name'] ?? null,
                    'witness_date' => !empty($data['witness_date']) ? $data['witness_date'] : null,
                    // Admin
                    'internal_notes' => $data['internal_notes'] ?? null,
                    'is_template' => !empty($data['is_template']) ? 1 : 0,
                    'supersedes_agreement_id' => !empty($data['supersedes_agreement_id']) ? $data['supersedes_agreement_id'] : null,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $this->context->user->getAttribute('user_id'),
                ]);
            
            // Update i18n
            \Illuminate\Database\Capsule\Manager::table('donor_agreement_i18n')
                ->updateOrInsert(
                    ['id' => $id, 'culture' => 'en'],
                    [
                        'title' => $data['title'] ?: $data['agreement_number'],
                        'description' => $data['description'] ?? null,
                    ]
                );
            
            // Handle file uploads
            $uploadResult = $this->handleDocumentUploads($request, $id);
            
            // Handle reminders
            $this->handleReminders($request, $id);
            // Handle linked records
            $this->handleLinkedRecords($request, $id);
            // Handle linked accessions
            $this->handleLinkedAccessions($request, $id);
            
            // Log history
            try {
                \Illuminate\Database\Capsule\Manager::table('donor_agreement_history')->insert([
                    'agreement_id' => $id,
                    'action' => 'updated',
                    'user_id' => $this->context->user->getAttribute('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                // History logging is optional
            }
            
            $db->commit();
            
            $message = 'Agreement updated successfully.';
            if ($uploadResult) {
                $message .= ' ' . $uploadResult;
            }
            $this->context->user->setFlash('notice', $message);
            $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $id]);
            
		} catch (Exception $e) {
            if ($e instanceof sfStopException) {
                throw $e;
            }
            $db->rollBack();
            $this->context->user->setFlash('error', 'Error updating agreement: ' . $e->getMessage());
            $this->redirect(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $id]);
        }
    }

    protected function handleDocumentUploads($request, $agreementId)
    {
        $uploadCount = 0;
        $errors = [];
        
        $files = isset($_FILES['documents']) ? $_FILES['documents'] : null;
        
        if (empty($files) || empty($files['name'])) {
            return null;
        }

        $baseUploadDir = sfConfig::get('sf_upload_dir');
        if (empty($baseUploadDir)) {
            $baseUploadDir = sfConfig::get('sf_root_dir') . '/uploads';
        }
        
        $uploadDir = $baseUploadDir . '/donor_agreements/' . $agreementId;
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return 'Failed to create upload directory';
            }
        }

        $docTypes = $request->getParameter('document_types', []);
        $docDescriptions = $request->getParameter('document_descriptions', []);

        if (is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if (empty($name)) {
                    continue;
                }
                
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
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
                }
            }
        }
        
        return $uploadCount > 0 ? "{$uploadCount} document(s) uploaded." : null;
    }

    protected function saveDocument($agreementId, $tmpName, $originalName, $size, $mimeType, $uploadDir, $docType, $description)
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $filepath)) {
            return false;
        }
        
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
            @unlink($filepath);
            return false;
        }
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
                'title' => $reminder['message'] ?? 'Reminder',
                'reminder_date' => $reminder['reminder_date'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            if (!empty($reminder['notify_email'])) {
                $insertData['notification_recipients'] = $reminder['notify_email'];
            }
            
            try {
                \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder')->insert($insertData);
            } catch (Exception $e) {
                // Skip invalid reminders
            }
        }
    }

    protected function handleLinkedRecords($request, $agreementId)
    {
        $records = $request->getParameter('link_records', []);
        error_log("DEBUG link_records: " . json_encode($records));
        foreach ($records as $record) {
            if (empty($record['id'])) {
                continue;
            }
            try {
                \Illuminate\Database\Capsule\Manager::table('donor_agreement_record')->insert([
                    'agreement_id' => $agreementId,
                    'information_object_id' => (int) $record['id'],
                    'relationship_type' => $record['relationship'] ?? 'covers',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                // Skip duplicates
            }
        }
    }

    protected function handleLinkedAccessions($request, $agreementId)
    {
        $accessions = $request->getParameter('link_accessions', []);
        error_log("DEBUG link_accessions: " . json_encode($accessions));
        foreach ($accessions as $accession) {
            if (empty($accession['id'])) {
                continue;
            }
            try {
                \Illuminate\Database\Capsule\Manager::table('donor_agreement_accession')->insert([
                    'donor_agreement_id' => $agreementId,
                    'accession_id' => (int) $accession['id'],
                    'is_primary' => !empty($accession['primary']) ? 1 : 0,
                    'linked_by' => $this->context->user->getAttribute('user_id'),
                    'linked_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                // Skip duplicates
            }
        }
    }
}
