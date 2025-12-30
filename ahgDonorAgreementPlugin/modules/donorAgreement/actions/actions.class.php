<?php
class donorAgreementActions extends sfActions
{
    protected function getConnection() { return Propel::getConnection(); }

    public function executeBrowse(sfWebRequest $request)
    {
        $conn = $this->getConnection();
        $this->limit = sfConfig::get('app_hits_per_page', 25);
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $this->offset = ($this->page - 1) * $this->limit;
        $where = ["da.is_template = 0"]; $params = [];
        if ($status = $request->getParameter('status')) { $where[] = "da.status = ?"; $params[] = $status; }
        if ($type = $request->getParameter('type')) { $where[] = "da.agreement_type_id = ?"; $params[] = $type; }
        if ($search = $request->getParameter('sq')) { $where[] = "(da.title LIKE ? OR da.agreement_number LIKE ? OR da.donor_name LIKE ?)"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
        $sql = "SELECT da.*, at.name as agreement_type_name FROM donor_agreement da LEFT JOIN agreement_type at ON da.agreement_type_id = at.id WHERE " . implode(' AND ', $where) . " ORDER BY da.agreement_date DESC LIMIT {$this->limit} OFFSET {$this->offset}";
        $stmt = $conn->prepare($sql); $stmt->execute($params); $this->agreements = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt = $conn->prepare("SELECT id, name FROM agreement_type WHERE is_active = 1 ORDER BY sort_order"); $stmt->execute(); $this->agreementTypes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $this->statuses = ['draft' => 'Draft', 'pending_review' => 'Pending Review', 'pending_signature' => 'Pending Signature', 'active' => 'Active', 'expired' => 'Expired', 'terminated' => 'Terminated', 'superseded' => 'Superseded'];
    }

    public function executeView(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT da.*, at.name as agreement_type_name FROM donor_agreement da LEFT JOIN agreement_type at ON da.agreement_type_id = at.id WHERE da.id = ?"); $stmt->execute([$id]); $this->agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->agreement) { $this->forward404('Agreement not found'); }
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_right WHERE donor_agreement_id = ?"); $stmt->execute([$id]); $this->agreement['rights'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_restriction WHERE donor_agreement_id = ?"); $stmt->execute([$id]); $this->agreement['restrictions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_document WHERE donor_agreement_id = ? ORDER BY created_at DESC"); $stmt->execute([$id]); $this->agreement['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_reminder WHERE donor_agreement_id = ? ORDER BY reminder_date"); $stmt->execute([$id]); $this->agreement['reminders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function executeAdd(sfWebRequest $request)
    {
        $this->form = new DonorAgreementForm(); $this->rightsForms = []; $this->restrictionForms = [];
        $conn = $this->getConnection(); $stmt = $conn->prepare("SELECT id, name FROM agreement_type WHERE is_active = 1 ORDER BY sort_order"); $stmt->execute(); $this->agreementTypes = $stmt->fetchAll(PDO::FETCH_OBJ);
        if ($request->isMethod('post')) { $this->processForm($request); }
    }

    public function executeEdit(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM donor_agreement WHERE id = ?"); $stmt->execute([$id]); $this->agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->agreement) { $this->forward404('Agreement not found'); }
        $this->form = new DonorAgreementForm(); $this->form->setDefaults($this->agreement);
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_right WHERE donor_agreement_id = ?"); $stmt->execute([$id]); $this->agreement['rights'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_restriction WHERE donor_agreement_id = ?"); $stmt->execute([$id]); $this->agreement['restrictions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->prepare("SELECT id, name FROM agreement_type WHERE is_active = 1 ORDER BY sort_order"); $stmt->execute(); $this->agreementTypes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $this->rightsForms = []; $this->restrictionForms = [];
        if ($request->isMethod('post')) { $this->processForm($request, $id); }
    }

    public function executeDelete(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM donor_agreement WHERE id = ?"); $stmt->execute([$id]); $this->agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->agreement) { $this->forward404('Agreement not found'); }
        if ($request->isMethod('delete') || $request->getParameter('confirm')) { $stmt = $conn->prepare("DELETE FROM donor_agreement WHERE id = ?"); $stmt->execute([$id]); $this->getUser()->setFlash('notice', 'Agreement deleted.'); $this->redirect(['module' => 'donorAgreement', 'action' => 'browse']); }
    }

    public function executeDashboard(sfWebRequest $request)
    {
        $conn = $this->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, SUM(CASE WHEN status = 'pending_signature' THEN 1 ELSE 0 END) as pending_signature, SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired FROM donor_agreement WHERE is_template = 0"); $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $conn->query("SELECT COUNT(*) as cnt FROM donor_agreement WHERE status = 'active' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)"); $stats['expiring_soon'] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        $stmt = $conn->query("SELECT COUNT(*) as cnt FROM donor_agreement WHERE status = 'active' AND review_date IS NOT NULL AND review_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"); $stats['review_due'] = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        $stmt = $conn->query("SELECT da.*, at.name as agreement_type_name, DATEDIFF(expiry_date, CURDATE()) as days_until_expiry FROM donor_agreement da LEFT JOIN agreement_type at ON da.agreement_type_id = at.id WHERE da.status = 'active' AND da.expiry_date IS NOT NULL AND da.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY da.expiry_date LIMIT 10"); $expiring = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt = $conn->query("SELECT dar.*, da.agreement_number, da.title as agreement_title, da.donor_name, DATEDIFF(dar.reminder_date, CURDATE()) as days_until_due FROM donor_agreement_reminder dar JOIN donor_agreement da ON dar.donor_agreement_id = da.id WHERE dar.status = 'active' AND dar.reminder_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY dar.priority DESC, dar.reminder_date LIMIT 10"); $reminders = $stmt->fetchAll(PDO::FETCH_OBJ);
        $this->dashboardData = ['statistics' => $stats, 'expiring_soon' => $expiring, 'pending_reminders' => $reminders, 'review_due' => [], 'restrictions_releasing' => []];
    }

    public function executeAddDocument(sfWebRequest $request)
    {
        $agreementId = (int) $request->getParameter('id'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM donor_agreement WHERE id = ?"); $stmt->execute([$agreementId]); $this->agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->agreement) { $this->forward404('Agreement not found'); }
        $this->form = new DonorAgreementDocumentForm();
        if ($request->isMethod('post')) {
            $this->form->bind($request->getParameter('document'), $request->getFiles('document'));
            if ($this->form->isValid()) {
                $file = $request->getFiles('document'); $values = $this->form->getValues();
                $uploadDir = sfConfig::get('sf_upload_dir') . '/agreements/' . $agreementId;
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $filename = uniqid() . '_' . basename($file['file']['name']); $filePath = $uploadDir . '/' . $filename;
                if (move_uploaded_file($file['file']['tmp_name'], $filePath)) {
                    $stmt = $conn->prepare("INSERT INTO donor_agreement_document (donor_agreement_id, document_type, filename, original_filename, file_path, mime_type, file_size, checksum_md5, title, description, document_date, is_signed, signature_date, is_confidential, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$agreementId, $values['document_type'], $filename, $file['file']['name'], $filePath, $file['file']['type'], $file['file']['size'], md5_file($filePath), $values['title'], $values['description'], $values['document_date'] ?: null, $values['is_signed'] ? 1 : 0, $values['signature_date'] ?: null, $values['is_confidential'] ? 1 : 0, $this->getUser()->getAttribute('user_id')]);
                    $this->getUser()->setFlash('notice', 'Document uploaded.'); $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreementId]);
                }
            }
        }
    }

    public function executeDownloadDocument(sfWebRequest $request)
    {
        $docId = (int) $request->getParameter('docId'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM donor_agreement_document WHERE id = ?"); $stmt->execute([$docId]); $document = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$document || !file_exists($document['file_path'])) { $this->forward404('Document not found'); }
        $response = $this->getResponse(); $response->setContentType($document['mime_type']); $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $document['original_filename'] . '"'); $response->setHttpHeader('Content-Length', $document['file_size']); $response->setContent(file_get_contents($document['file_path'])); return sfView::NONE;
    }

    public function executeAddReminder(sfWebRequest $request)
    {
        $agreementId = (int) $request->getParameter('id'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM donor_agreement WHERE id = ?"); $stmt->execute([$agreementId]); $this->agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->agreement) { $this->forward404('Agreement not found'); }
        $this->form = new DonorAgreementReminderForm();
        if ($request->isMethod('post')) {
            $this->form->bind($request->getParameter('reminder'));
            if ($this->form->isValid()) {
                $values = $this->form->getValues();
                $stmt = $conn->prepare("INSERT INTO donor_agreement_reminder (donor_agreement_id, reminder_type, title, description, reminder_date, priority, is_recurring, recurrence_pattern, recurrence_end_date, notify_email, action_required, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$agreementId, $values['reminder_type'], $values['title'], $values['description'], $values['reminder_date'], $values['priority'], $values['is_recurring'] ? 1 : 0, $values['recurrence_pattern'] ?: null, $values['recurrence_end_date'] ?: null, $values['notify_email'] ? 1 : 0, $values['action_required'], $this->getUser()->getAttribute('user_id')]);
                $this->getUser()->setFlash('notice', 'Reminder created.'); $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreementId]);
            }
        }
    }

    public function executeCompleteReminder(sfWebRequest $request)
    {
        $reminderId = (int) $request->getParameter('reminderId'); $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE donor_agreement_reminder SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?"); $stmt->execute([$this->getUser()->getAttribute('user_id'), $reminderId]);
        $this->getUser()->setFlash('notice', 'Reminder completed.');
        if ($request->isXmlHttpRequest()) { return $this->renderText(json_encode(['success' => true])); }
        $this->redirect($request->getReferer() ?: '@homepage');
    }

    public function executeSnoozeReminder(sfWebRequest $request)
    {
        $reminderId = (int) $request->getParameter('reminderId'); $snoozeUntil = $request->getParameter('snooze_until'); $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE donor_agreement_reminder SET status = 'snoozed', snooze_until = ? WHERE id = ?"); $stmt->execute([$snoozeUntil, $reminderId]);
        $this->getUser()->setFlash('notice', 'Reminder snoozed.');
        if ($request->isXmlHttpRequest()) { return $this->renderText(json_encode(['success' => true])); }
        $this->redirect($request->getReferer() ?: '@homepage');
    }

    public function executeTerminate(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id'); $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT * FROM donor_agreement WHERE id = ?"); $stmt->execute([$id]); $this->agreement = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->agreement) { $this->forward404('Agreement not found'); }
        if ($request->isMethod('post')) { $stmt = $conn->prepare("UPDATE donor_agreement SET status = 'terminated', termination_date = ?, termination_reason = ? WHERE id = ?"); $stmt->execute([$request->getParameter('termination_date') ?: date('Y-m-d'), $request->getParameter('reason'), $id]); $this->getUser()->setFlash('notice', 'Agreement terminated.'); $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $id]); }
    }

    protected function processForm(sfWebRequest $request, ?int $id = null)
    {
        $this->form->bind($request->getParameter('agreement'));
        if ($this->form->isValid()) {
            $values = $this->form->getValues(); $conn = $this->getConnection();
            if ($id) {
                $stmt = $conn->prepare("UPDATE donor_agreement SET agreement_type_id = ?, title = ?, description = ?, status = ?, donor_id = ?, donor_name = ?, institution_name = ?, legal_representative = ?, legal_representative_title = ?, repository_representative = ?, repository_representative_title = ?, agreement_date = ?, effective_date = ?, expiry_date = ?, review_date = ?, scope_description = ?, extent_statement = ?, transfer_date = ?, general_terms = ?, special_conditions = ?, accession_id = ?, information_object_id = ?, internal_notes = ?, is_template = ?, updated_by = ? WHERE id = ?");
                $stmt->execute([$values['agreement_type_id'], $values['title'], $values['description'], $values['status'], $values['donor_id'] ?: null, $values['donor_name'], $values['institution_name'], $values['legal_representative'], $values['legal_representative_title'], $values['repository_representative'], $values['repository_representative_title'], $values['agreement_date'] ?: null, $values['effective_date'] ?: null, $values['expiry_date'] ?: null, $values['review_date'] ?: null, $values['scope_description'], $values['extent_statement'], $values['transfer_date'] ?: null, $values['general_terms'], $values['special_conditions'], $values['accession_id'] ?: null, $values['information_object_id'] ?: null, $values['internal_notes'], $values['is_template'] ? 1 : 0, $this->getUser()->getAttribute('user_id'), $id]);
                $agreementId = $id;
            } else {
                $year = date('Y'); $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(agreement_number, '-', -1) AS UNSIGNED)) as max_num FROM donor_agreement WHERE agreement_number LIKE 'AGR-{$year}-%'"); $maxNum = $stmt->fetch(PDO::FETCH_ASSOC)['max_num'] ?? 0; $agreementNumber = sprintf('AGR-%s-%04d', $year, $maxNum + 1);
                $stmt = $conn->prepare("INSERT INTO donor_agreement (agreement_number, agreement_type_id, title, description, status, donor_id, donor_name, institution_name, legal_representative, legal_representative_title, repository_representative, repository_representative_title, agreement_date, effective_date, expiry_date, review_date, scope_description, extent_statement, transfer_date, general_terms, special_conditions, accession_id, information_object_id, internal_notes, is_template, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$agreementNumber, $values['agreement_type_id'], $values['title'], $values['description'], $values['status'] ?: 'draft', $values['donor_id'] ?: null, $values['donor_name'], $values['institution_name'], $values['legal_representative'], $values['legal_representative_title'], $values['repository_representative'], $values['repository_representative_title'], $values['agreement_date'] ?: null, $values['effective_date'] ?: null, $values['expiry_date'] ?: null, $values['review_date'] ?: null, $values['scope_description'], $values['extent_statement'], $values['transfer_date'] ?: null, $values['general_terms'], $values['special_conditions'], $values['accession_id'] ?: null, $values['information_object_id'] ?: null, $values['internal_notes'], $values['is_template'] ? 1 : 0, $this->getUser()->getAttribute('user_id')]);
                $agreementId = $conn->lastInsertId();
                if ($rightsData = $request->getParameter('rights')) { foreach ($rightsData as $right) { if (!empty($right['right_type'])) { $stmt = $conn->prepare("INSERT INTO donor_agreement_right (donor_agreement_id, right_type, permission, conditions) VALUES (?, ?, ?, ?)"); $stmt->execute([$agreementId, $right['right_type'], $right['permission'] ?? 'granted', $right['conditions'] ?? null]); } } }
                if ($restrictionsData = $request->getParameter('restrictions')) { foreach ($restrictionsData as $restriction) { if (!empty($restriction['restriction_type'])) { $stmt = $conn->prepare("INSERT INTO donor_agreement_restriction (donor_agreement_id, restriction_type, release_date, reason, auto_release) VALUES (?, ?, ?, ?, ?)"); $stmt->execute([$agreementId, $restriction['restriction_type'], $restriction['release_date'] ?: null, $restriction['reason'] ?? null, !empty($restriction['auto_release']) ? 1 : 0]); } } }
            }
            $this->getUser()->setFlash('notice', 'Agreement saved.'); $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreementId]);
        }
    }
}
