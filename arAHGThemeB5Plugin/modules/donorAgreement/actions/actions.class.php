<?php

class donorAgreementActions extends sfActions
{
    /**
     * Get agreement types from database
     */
    protected function getAgreementTypes()
    {
        $types = [];
        
        try {
            $pdo = Propel::getConnection();
            $stmt = $pdo->query("
                SELECT id, name, slug, description 
                FROM agreement_type 
                WHERE is_active = 1 
                ORDER BY sort_order, name
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $types[] = $row;
            }
        } catch (Exception $e) {
            error_log("Error fetching agreement types: " . $e->getMessage());
        }
        
        return $types;
    }

    /**
     * Get statuses
     */
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

    /**
     * Browse agreements
     */
    public function executeBrowse(sfWebRequest $request)
    {
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        
        $pdo = Propel::getConnection();
        $where = [];
        $params = [];
        
        if ($status = $request->getParameter('status')) {
            $where[] = "da.status = ?";
            $params[] = $status;
        }
        
        if ($typeId = $request->getParameter('type')) {
            $where[] = "da.agreement_type_id = ?";
            $params[] = $typeId;
        }
        
        if ($sq = $request->getParameter('sq')) {
            $where[] = "(da.agreement_number LIKE ? OR dai.title LIKE ? OR ai.authorized_form_of_name LIKE ?)";
            $params[] = "%{$sq}%";
            $params[] = "%{$sq}%";
            $params[] = "%{$sq}%";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT 
                da.*,
                dai.title,
                dai.description,
                at.name as agreement_type_name,
                ai.authorized_form_of_name as donor_name
            FROM donor_agreement da
            LEFT JOIN donor_agreement_i18n dai ON da.id = dai.id AND dai.culture = 'en'
            LEFT JOIN agreement_type at ON da.agreement_type_id = at.id
            LEFT JOIN donor d ON da.donor_id = d.id
            LEFT JOIN actor a ON d.id = a.id
            LEFT JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            {$whereClause}
            ORDER BY da.created_at DESC
            LIMIT 100
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $this->agreements = $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Add new agreement
     */
    public function executeAdd(sfWebRequest $request)
    {
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        $this->agreement = null;
        $this->donors = $this->getDonorsList();
        
        // For donor pre-selection
        $this->donorId = $request->getParameter('donor_id');
        $this->donor = null;
        if ($this->donorId) {
            $pdo = Propel::getConnection();
            $stmt = $pdo->prepare("
                SELECT d.id, ai.authorized_form_of_name as name
                FROM donor d
                JOIN actor a ON d.id = a.id
                JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
                WHERE d.id = ?
            ");
            $stmt->execute([$this->donorId]);
            $this->donor = $stmt->fetch(PDO::FETCH_OBJ);
        }
        
        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    /**
     * Edit existing agreement
     */
    public function executeEdit(sfWebRequest $request)
    {
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        $this->donors = $this->getDonorsList();
        
        $id = $request->getParameter('id');
        
        $pdo = Propel::getConnection();
        $stmt = $pdo->prepare("
            SELECT da.*, dai.title, dai.description
            FROM donor_agreement da
            LEFT JOIN donor_agreement_i18n dai ON da.id = dai.id AND dai.culture = 'en'
            WHERE da.id = ?
        ");
        $stmt->execute([$id]);
        $this->agreement = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }
        
        // Get donor info
        $this->donorId = $this->agreement->donor_id;
        $this->donor = null;
        if ($this->donorId) {
            $stmt = $pdo->prepare("
                SELECT d.id, ai.authorized_form_of_name as name
                FROM donor d
                JOIN actor a ON d.id = a.id
                JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
                WHERE d.id = ?
            ");
            $stmt->execute([$this->donorId]);
            $this->donor = $stmt->fetch(PDO::FETCH_OBJ);
        }
        
        if ($request->isMethod('post')) {
            $this->processForm($request, $id);
        }
    }

    /**
     * View agreement details
     */
    public function executeView(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        
        $pdo = Propel::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                da.*,
                dai.title,
                dai.description,
                at.name as agreement_type_name,
                ai.authorized_form_of_name as donor_name
            FROM donor_agreement da
            LEFT JOIN donor_agreement_i18n dai ON da.id = dai.id AND dai.culture = 'en'
            LEFT JOIN agreement_type at ON da.agreement_type_id = at.id
            LEFT JOIN donor d ON da.donor_id = d.id
            LEFT JOIN actor a ON d.id = a.id
            LEFT JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            WHERE da.id = ?
        ");
        $stmt->execute([$id]);
        $this->agreement = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }
        
        // Get rights
        $stmt = $pdo->prepare("SELECT * FROM donor_agreement_right WHERE donor_agreement_id = ?");
        $stmt->execute([$id]);
        $this->rights = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        // Get restrictions
        $stmt = $pdo->prepare("SELECT * FROM donor_agreement_restriction WHERE donor_agreement_id = ?");
        $stmt->execute([$id]);
        $this->restrictions = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        // Get documents
        $stmt = $pdo->prepare("SELECT * FROM donor_agreement_document WHERE donor_agreement_id = ?");
        $stmt->execute([$id]);
        $this->documents = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        // Get reminders
        $stmt = $pdo->prepare("SELECT * FROM donor_agreement_reminder WHERE donor_agreement_id = ? ORDER BY reminder_date");
        $stmt->execute([$id]);
        $this->reminders = $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Reminders list
     */
    public function executeReminders(sfWebRequest $request)
    {
        $pdo = Propel::getConnection();
        
        $sql = "
            SELECT 
                r.*,
                dai.title as agreement_title,
                da.agreement_number,
                ai.authorized_form_of_name as donor_name
            FROM donor_agreement_reminder r
            JOIN donor_agreement da ON r.donor_agreement_id = da.id
            LEFT JOIN donor_agreement_i18n dai ON da.id = dai.id AND dai.culture = 'en'
            LEFT JOIN donor d ON da.donor_id = d.id
            LEFT JOIN actor a ON d.id = a.id
            LEFT JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            WHERE r.is_active = 1
            ORDER BY r.reminder_date ASC
        ";
        
        $this->reminders = $pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get list of donors for dropdown
     */
    protected function getDonorsList()
    {
        $donors = [];
        
        try {
            $pdo = Propel::getConnection();
            $stmt = $pdo->query("
                SELECT d.id, ai.authorized_form_of_name as name
                FROM donor d
                JOIN actor a ON d.id = a.id
                JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
                ORDER BY ai.authorized_form_of_name
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $donors[] = $row;
            }
        } catch (Exception $e) {
            error_log("Error fetching donors: " . $e->getMessage());
        }
        
        return $donors;
    }

    /**
     * Process form submission
     */
    protected function processForm(sfWebRequest $request, $id = null)
    {
        $data = $request->getParameter('agreement');
        $pdo = Propel::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE donor_agreement SET
                        donor_id = ?,
                        agreement_type_id = ?,
                        agreement_number = ?,
                        status = ?,
                        effective_date = ?,
                        expiry_date = ?,
                        review_date = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['donor_id'] ?: null,
                    $data['agreement_type_id'],
                    $data['agreement_number'] ?: null,
                    $data['status'] ?: 'draft',
                    $data['effective_date'] ?: null,
                    $data['expiry_date'] ?: null,
                    $data['review_date'] ?: null,
                    $id
                ]);
                
                $stmt = $pdo->prepare("
                    INSERT INTO donor_agreement_i18n (id, culture, title, description)
                    VALUES (?, 'en', ?, ?)
                    ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description)
                ");
                $stmt->execute([$id, $data['title'], $data['description'] ?? null]);
                
                $agreementId = $id;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO donor_agreement 
                    (donor_id, agreement_type_id, agreement_number, status, effective_date, expiry_date, review_date, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $data['donor_id'] ?: null,
                    $data['agreement_type_id'],
                    $data['agreement_number'] ?: null,
                    $data['status'] ?: 'draft',
                    $data['effective_date'] ?: null,
                    $data['expiry_date'] ?: null,
                    $data['review_date'] ?: null
                ]);
                
                $agreementId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("
                    INSERT INTO donor_agreement_i18n (id, culture, title, description)
                    VALUES (?, 'en', ?, ?)
                ");
                $stmt->execute([$agreementId, $data['title'], $data['description'] ?? null]);
            }
            
            $pdo->commit();
            
            $this->getUser()->setFlash('notice', $id ? 'Agreement updated.' : 'Agreement created.');
            $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreementId]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->getUser()->setFlash('error', 'Error saving agreement: ' . $e->getMessage());
        }
    }

    /**
     * Delete agreement
     */
    public function executeDelete(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        
        if ($request->isMethod('post')) {
            $pdo = Propel::getConnection();
            
            try {
                $pdo->beginTransaction();
                
                $pdo->exec("DELETE FROM donor_agreement_right WHERE donor_agreement_id = {$id}");
                $pdo->exec("DELETE FROM donor_agreement_restriction WHERE donor_agreement_id = {$id}");
                $pdo->exec("DELETE FROM donor_agreement_document WHERE donor_agreement_id = {$id}");
                $pdo->exec("DELETE FROM donor_agreement_reminder_log WHERE donor_agreement_reminder_id IN (SELECT id FROM donor_agreement_reminder WHERE donor_agreement_id = {$id})");
                $pdo->exec("DELETE FROM donor_agreement_reminder WHERE donor_agreement_id = {$id}");
                $pdo->exec("DELETE FROM donor_agreement_i18n WHERE id = {$id}");
                $pdo->exec("DELETE FROM donor_agreement WHERE id = {$id}");
                
                $pdo->commit();
                
                $this->getUser()->setFlash('notice', 'Agreement deleted.');
            } catch (Exception $e) {
                $pdo->rollBack();
                $this->getUser()->setFlash('error', 'Error deleting: ' . $e->getMessage());
            }
        }
        
        $this->redirect(['module' => 'donorAgreement', 'action' => 'browse']);
    }
}
