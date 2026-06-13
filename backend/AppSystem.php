<?php
class ConstructionSystem
{
    private $pdo;

    private const ALLOWED_PHOTO_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_NTP_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_AWARD_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xls', 'xlsx', 'csv'];
    private const MAX_FILE_SIZE = 5242880; // 5MB

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function validateFile($ext, $allowedExts, $size, $tmpPath = null)
    {
        $ext = strtolower($ext);
        if (!in_array($ext, $allowedExts, true)) {
            return 'File type .' . $ext . ' is not allowed.';
        }
        $blocked = ['php', 'js', 'html', 'htm', 'exe', 'sh', 'bat', 'cmd', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp'];
        if (in_array($ext, $blocked, true)) {
            return 'Executable files are not allowed.';
        }
        if ($size > self::MAX_FILE_SIZE) {
            return 'File size exceeds the maximum limit of 5MB.';
        }
        if ($tmpPath && file_exists($tmpPath) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            $allowedMimes = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/webp',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv', 'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            if (!in_array($mime, $allowedMimes, true) && strpos($mime, 'image/') !== 0) {
                return 'Invalid file content. MIME type not allowed.';
            }
        }
        return null;
    }

    private function safeUpload($file, $uploadDir, $prefix, $allowedExts)
    {
        if (!$file || !isset($file['tmp_name']) || !$file['tmp_name'] || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $error = $this->validateFile($ext, $allowedExts, $file['size'], $file['tmp_name']);
        if ($error) {
            return $error;
        }

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $relativePath = 'uploads/' . basename(rtrim($uploadDir, '\\/')) . '/' . $fileName;
            return $relativePath;
        }

        return 'Failed to upload file.';
    }

    public function login($email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return ['status' => 'error', 'message' => 'Email address not found.', 'field' => 'email'];
        }
        if (!password_verify($password, $admin['password'])) {
            return ['status' => 'error', 'message' => 'Incorrect password.', 'field' => 'password'];
        }

        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        return ['status' => 'success', 'role' => 'admin'];
    }

    public function getDashboardStats()
    {
        $stats = ['projects' => 0, 'users' => 0, 'total_cash_release' => 0, 'total_payroll_advance' => 0];

        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM projects WHERE LOWER(TRIM(status)) IN ('ongoing', 'pending')");
            if ($stmt)
                $stats['projects'] = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
        }

        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM manpower WHERE is_archived = 0");
            if ($stmt)
                $stats['users'] = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
        }

        try {
            $stmt = $this->pdo->query("SELECT SUM(release_amount) FROM cash_releases WHERE status != 'Cancelled'");
            if ($stmt)
                $stats['total_cash_release'] = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
        }

        try {
            $stmt = $this->pdo->query("SELECT SUM(cash_advance) FROM payroll");
            if ($stmt)
                $stats['total_payroll_advance'] = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
        }

        return $stats;
    }

    private function generateDefaultChecklist($project_id)
    {
        $template = [
            'SOG TO ROOF BEAM' => ['Layout', 'Batter Boards', 'Excavation (Footing/Tie Beam)', 'Rebars Fabrication', 'Slab on Grade'],
            'TRUSSES AND ROOFING' => ['QDE Application', 'Trusses Installation', 'Roofing and Accessories'],
            'CHB LAYING' => ['GF and 2F Area', 'Interior/Exterior Walls', 'Electrical/Plumbing Rough-ins']
        ];
        $stmt = $this->pdo->prepare("INSERT INTO project_accomplishments (project_id, category, task_name, status, award_cost) VALUES (?, ?, ?, 'Not Started', 1500.00)");
        foreach ($template as $cat => $tasks) {
            foreach ($tasks as $task) {
                $stmt->execute([$project_id, $cat, $task]);
            }
        }
    }

    public function addProject($name, $client, $location, $desc, $foreman, $start_date, $block_no = '', $lot_no = '', $foreman_2 = '', $completion_date = null, $work_description = null, $project_description = null, $total_amount = 0)
    {
        $stmt = $this->pdo->prepare("INSERT INTO projects (name, block_no, lot_no, client_name, location, description, foreman, foreman_2, start_date, completion_date, work_description, project_description, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ongoing')");
        $stmt->execute([$name, $block_no, $lot_no, $client, $location, $desc, $foreman, $foreman_2 ?: null, $start_date, $completion_date, $work_description, $project_description, $total_amount ?: 0]);
        $projectId = $this->pdo->lastInsertId();

        try {
            $this->pdo->prepare("INSERT INTO project_costs (project_id) VALUES (?)")->execute([$projectId]);
        } catch (PDOException $e) {
        }

        $this->generateDefaultChecklist($projectId);
        return ['status' => 'success'];
    }

    public function getProjects()
    {
        try {
            return $this->pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
        } catch (Exception $e) {
            try {
                return $this->pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll();
            } catch (Exception $e2) {
                return [];
            }
        }
    }

    public function updateProject($id, $name, $client, $location, $desc, $foreman, $start_date, $block_no = '', $lot_no = '', $foreman_2 = '', $completion_date = null, $work_description = null, $project_description = null, $total_amount = 0)
    {
        $this->pdo->prepare("UPDATE projects SET name=?, block_no=?, lot_no=?, client_name=?, location=?, description=?, foreman=?, foreman_2=?, start_date=?, completion_date=?, work_description=?, project_description=?, total_amount=? WHERE id=?")->execute([$name, $block_no, $lot_no, $client, $location, $desc, $foreman, $foreman_2 ?: null, $start_date, $completion_date, $work_description, $project_description, $total_amount ?: 0, $id]);
        return ['status' => 'success'];
    }

    public function updateProjectStatus($id, $status)
    {
        $this->pdo->prepare("UPDATE projects SET status=? WHERE id=?")->execute([strtolower(trim($status)), $id]);
        return ['status' => 'success'];
    }

    public function deleteProject($id)
    {
        $this->pdo->prepare("DELETE FROM project_accomplishments WHERE project_id = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM material_issuances WHERE project_id = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
        return ['status' => 'success'];
    }

    public function getProjectData($project_id)
    {
        $projStmt = $this->pdo->prepare("SELECT id, name, block_no, lot_no, client_name, location, description, foreman, foreman_2, start_date, completion_date, work_description, project_description, total_amount, ntp_attachment, status FROM projects WHERE id = ?");
        $projStmt->execute([$project_id]);
        $project = $projStmt->fetch();
        $accStmt = $this->pdo->prepare("SELECT * FROM project_accomplishments WHERE project_id = ? ORDER BY id ASC");
        $accStmt->execute([$project_id]);
        $issStmt = $this->pdo->prepare("SELECT i.*, inv.name as item_name, inv.unit FROM material_issuances i JOIN inventory inv ON i.item_id = inv.id WHERE i.project_id = ? ORDER BY i.id DESC");
        $issStmt->execute([$project_id]);
        return ['status' => 'success', 'project' => $project, 'project_status' => $project['status'] ?? '', 'checklist' => $accStmt->fetchAll(), 'issuances' => $issStmt->fetchAll()];
    }

    public function addChecklistTask($project_id, $category, $task_name)
    {
        $this->pdo->prepare("INSERT INTO project_accomplishments (project_id, category, task_name, status, award_cost) VALUES (?, ?, ?, 'Not Started', 1500.00)")->execute([$project_id, $category, $task_name]);
        return ['status' => 'success'];
    }
    public function updateChecklistStatus($task_id, $status)
    {
        $this->pdo->prepare("UPDATE project_accomplishments SET status = ?, completion_date = ? WHERE id = ?")->execute([$status, ($status === 'Completed') ? date('Y-m-d') : null, $task_id]);
        return ['status' => 'success'];
    }
    public function editChecklistTask($task_id, $task_name)
    {
        $this->pdo->prepare("UPDATE project_accomplishments SET task_name = ? WHERE id = ?")->execute([$task_name, $task_id]);
        return ['status' => 'success'];
    }
    public function updateTaskCost($task_id, $cost)
    {
        $this->pdo->prepare("UPDATE project_accomplishments SET award_cost = ? WHERE id = ?")->execute([$cost, $task_id]);
        return ['status' => 'success'];
    }
    public function deleteChecklistTask($task_id)
    {
        $this->pdo->prepare("DELETE FROM project_accomplishments WHERE id = ?")->execute([$task_id]);
        return ['status' => 'success'];
    }
    public function deleteChecklistCategory($project_id, $category)
    {
        $this->pdo->prepare("DELETE FROM project_accomplishments WHERE project_id = ? AND category = ?")->execute([$project_id, $category]);
        return ['status' => 'success'];
    }
    public function assignWorker($project_id, $category, $worker)
    {
        $this->pdo->prepare("UPDATE project_accomplishments SET assigned_worker = ? WHERE project_id = ? AND category = ?")->execute([$worker, $project_id, $category]);
        return ['status' => 'success'];
    }
    public function removeWorkerAssignment($project_id, $category)
    {
        $this->pdo->prepare("UPDATE project_accomplishments SET assigned_worker = NULL WHERE project_id = ? AND category = ?")->execute([$project_id, $category]);
        return ['status' => 'success'];
    }

    public function getSuppliers()
    {
        return $this->pdo->query("
            SELECT s.* FROM suppliers s
            ORDER BY s.name ASC
        ")->fetchAll();
    }
    public function addSupplier($name, $materials = '', $contact = '', $email = '', $contact_person = '', $address = '', $material_category = '', $project_id = '', $bom_id = '', $inventory_item_id = '', $price_quote = 0, $payment_terms = '', $status = 'Active', $remarks = '')
    {
        if (trim($name) === '') {
            return ['status' => 'error', 'message' => 'Supplier name is required.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Invalid email format.'];
        }
        $price_quote = floatval($price_quote);
        if ($price_quote < 0) {
            return ['status' => 'error', 'message' => 'Price quote must be 0 or greater.'];
        }
        $allowedStatuses = ['Active', 'Inactive', 'Preferred', 'Blacklisted'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Active';
        }
        $this->pdo->prepare("
            INSERT INTO suppliers (name, materials, contact, email, contact_person, address, material_category, project_id, bom_id, inventory_item_id, price_quote, payment_terms, status, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$name, $materials, $contact, $email, $contact_person, $address, $material_category, $project_id ?: null, $bom_id ?: null, $inventory_item_id ?: null, $price_quote ?: 0, $payment_terms, $status, $remarks]);
        return ['status' => 'success'];
    }
    public function updateSupplier($id, $name, $materials, $contact, $email, $contact_person, $address, $material_category, $project_id, $bom_id, $inventory_item_id, $price_quote, $payment_terms, $status, $remarks)
    {
        if (trim($name) === '') {
            return ['status' => 'error', 'message' => 'Supplier name is required.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Invalid email format.'];
        }
        $price_quote = floatval($price_quote);
        if ($price_quote < 0) {
            return ['status' => 'error', 'message' => 'Price quote must be 0 or greater.'];
        }
        $allowedStatuses = ['Active', 'Inactive', 'Preferred', 'Blacklisted'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Active';
        }
        $this->pdo->prepare("
            UPDATE suppliers SET name=?, materials=?, contact=?, email=?, contact_person=?, address=?, material_category=?, project_id=?, bom_id=?, inventory_item_id=?, price_quote=?, payment_terms=?, status=?, remarks=?
            WHERE id=?
        ")->execute([$name, $materials, $contact, $email, $contact_person, $address, $material_category, $project_id ?: null, $bom_id ?: null, $inventory_item_id ?: null, $price_quote ?: 0, $payment_terms, $status, $remarks, $id]);
        return ['status' => 'success', 'message' => 'Supplier updated.'];
    }
    public function deleteSupplier($id)
    {
        $this->pdo->prepare("UPDATE suppliers SET status='Inactive' WHERE id=?")->execute([$id]);
        return ['status' => 'success', 'message' => 'Supplier deactivated.'];
    }
    public function searchSuppliers($query)
    {
        $q = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT s.* FROM suppliers s
            WHERE s.name LIKE ? OR s.materials LIKE ? OR s.contact LIKE ? OR s.email LIKE ?
               OR s.contact_person LIKE ? OR s.material_category LIKE ?
               OR s.payment_terms LIKE ? OR s.remarks LIKE ?
            ORDER BY s.name ASC
        ");
        $stmt->execute([$q, $q, $q, $q, $q, $q, $q, $q]);
        return $stmt->fetchAll();
    }
    public function getInventoryCategories()
    {
        return $this->pdo->query("SELECT name FROM inventory_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    }
    public function addInventoryCategory($name)
    {
        $this->pdo->prepare("INSERT IGNORE INTO inventory_categories (name) VALUES (?)")->execute([$name]);
        return ['status' => 'success'];
    }
    public function getInventory()
    {
        return $this->pdo->query("SELECT * FROM inventory ORDER BY name ASC")->fetchAll();
    }
    public function addInventory($name, $category, $stock, $unit, $cost, $supplier)
    {
        $this->pdo->prepare("INSERT INTO inventory (name, category, stock, unit, unit_cost, supplier) VALUES (?, ?, ?, ?, ?, ?)")->execute([$name, $category, $stock, $unit, $cost, $supplier]);
        return ['status' => 'success'];
    }

    public function issueMaterial($project_id, $item_id, $qty, $receiver)
    {
        $stmt = $this->pdo->prepare("SELECT stock, unit_cost FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        if (!$item || $item['stock'] < $qty)
            return ['status' => 'error', 'message' => 'Insufficient stock! Only ' . ($item['stock'] ?? 0) . ' left.'];
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE inventory SET stock = stock - ? WHERE id = ?")->execute([$qty, $item_id]);
            $this->pdo->prepare("INSERT INTO material_issuances (project_id, item_id, qty, unit_cost, receiver) VALUES (?, ?, ?, ?, ?)")->execute([$project_id, $item_id, $qty, $item['unit_cost'], $receiver]);
            $this->pdo->commit();
            return ['status' => 'success'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()];
        }
    }

    public function addSkillCategory($name)
    {
        $this->pdo->prepare("INSERT IGNORE INTO skill_categories (name) VALUES (?)")->execute([trim($name)]);
        return ['status' => 'success'];
    }
    public function editSkillCategory($old_name, $new_name)
    {
        $this->pdo->prepare("UPDATE skill_categories SET name = ? WHERE name = ?")->execute([trim($new_name), trim($old_name)]);
        $this->pdo->prepare("UPDATE manpower SET skills = ? WHERE skills = ?")->execute([trim($new_name), trim($old_name)]);
        return ['status' => 'success'];
    }
    public function deleteSkillCategory($name)
    {
        $trimmedName = trim($name);
        $this->pdo->prepare("DELETE FROM skill_categories WHERE name = ?")->execute([$trimmedName]);
        $this->pdo->prepare("UPDATE manpower SET skills = 'Uncategorized' WHERE skills = ?")->execute([$trimmedName]);
        return ['status' => 'success'];
    }
    public function archiveManpower($id)
    {
        $this->pdo->prepare("UPDATE manpower SET is_archived = 1, archived_date = NOW() WHERE id = ?")->execute([$id]);
        return ['status' => 'success'];
    }
    public function restoreManpower($id)
    {
        $this->pdo->prepare("UPDATE manpower SET is_archived = 0, archived_date = NULL WHERE id = ?")->execute([$id]);
        return ['status' => 'success'];
    }

    public function getArchivedManpower()
    {
        try {
            return $this->pdo->query("SELECT m.*, m.photo_path as photo, p.name as project_name FROM manpower m LEFT JOIN projects p ON m.project_id = p.id WHERE m.is_archived = 1 ORDER BY m.archived_date DESC")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    public function getUsers()
    {
        try {
            return $this->pdo->query("SELECT m.id, m.name, m.position, m.skills, m.rate as salary, m.photo_path as photo, m.foreman, m.contact_number, m.address, m.status, p.name as project_name FROM manpower m LEFT JOIN projects p ON m.project_id = p.id WHERE m.is_archived = 0 ORDER BY m.name ASC")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getManpowerSkills()
    {
        $this->pdo->exec("INSERT IGNORE INTO skill_categories (name) SELECT DISTINCT skills FROM manpower WHERE skills IS NOT NULL AND TRIM(skills) != '' AND TRIM(skills) != 'Uncategorized'");
        $skills = $this->pdo->query("SELECT c.name as skill_name, (SELECT COUNT(*) FROM manpower m WHERE TRIM(m.skills) = c.name AND m.is_archived = 0) as worker_count FROM skill_categories c ORDER BY c.name ASC")->fetchAll();
        $uncat = $this->pdo->query("SELECT COUNT(*) FROM manpower WHERE (skills IS NULL OR TRIM(skills) = '' OR TRIM(skills) = 'Uncategorized') AND is_archived = 0")->fetchColumn();
        if ($uncat > 0) {
            $skills[] = ['skill_name' => 'Uncategorized', 'worker_count' => $uncat];
        }
        return $skills;
    }

    public function getManpowerBySkill($skill)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT m.*, m.photo_path as photo, p.name as project_name FROM manpower m LEFT JOIN projects p ON m.project_id = p.id WHERE TRIM(m.skills) = ? AND m.is_archived = 0 ORDER BY m.name ASC");
            $stmt->execute([trim($skill)]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getManpowerByForeman($foremanName)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT m.*, m.photo_path as photo, p.name as project_name FROM manpower m LEFT JOIN projects p ON m.project_id = p.id WHERE m.foreman = ? AND m.is_archived = 0 ORDER BY m.name ASC");
            $stmt->execute([$foremanName]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getForemenList()
    {
        try {
            return $this->pdo->query("SELECT id, name, skills, contact_number, (SELECT COUNT(*) FROM manpower w WHERE w.foreman = m.name AND w.is_archived = 0) as worker_count FROM manpower m WHERE (LOWER(m.position) LIKE '%foreman%' OR LOWER(m.position) LIKE '%lead%' OR LOWER(m.position) LIKE '%engineer%' OR LOWER(m.position) LIKE '%in-charge%' OR m.position IS NULL) AND m.is_archived = 0 ORDER BY m.name ASC")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getUnassignedWorkers()
    {
        try {
            return $this->pdo->query("SELECT m.*, m.photo_path as photo, p.name as project_name FROM manpower m LEFT JOIN projects p ON m.project_id = p.id WHERE (m.foreman IS NULL OR TRIM(m.foreman) = '') AND m.is_archived = 0 ORDER BY m.name ASC")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function searchManpower($query)
    {
        try {
            $q = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT m.*, m.photo_path as photo, p.name as project_name 
                FROM manpower m 
                LEFT JOIN projects p ON m.project_id = p.id 
                WHERE m.is_archived = 0 
                  AND (m.name LIKE ? 
                       OR m.skills LIKE ? 
                       OR m.foreman LIKE ? 
                       OR m.contact_number LIKE ? 
                       OR m.address LIKE ? 
                       OR m.position LIKE ?
                       OR p.name LIKE ?
                       OR m.project_site_text LIKE ?)
                ORDER BY m.name ASC
            ");
            $stmt->execute([$q, $q, $q, $q, $q, $q, $q, $q]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllForemanNames()
    {
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT foreman FROM manpower WHERE foreman IS NOT NULL AND TRIM(foreman) != '' AND is_archived = 0 ORDER BY foreman ASC");
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }


    public function addManpower($name, $skills, $position, $salary, $project_id, $photo, $foreman = '', $contact_number = '', $address = '', $status = 'Active', $project_site_text = '')
    {
        try {
            $name = trim($name);
            $skills = trim($skills);
            $position = trim($position);
            $salary = trim((string) $salary);
            $foreman = trim($foreman);
            $contact_number = trim($contact_number);
            $address = trim($address);
            $status = trim($status) ?: 'Active';
            $project_site_text = trim($project_site_text);

            if ($name === '' || $skills === '' || $position === '' || $salary === '') {
                return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
            }

            $salary = preg_replace('/[^0-9.]/', '', $salary);

            if ($salary === '' || !is_numeric($salary)) {
                return ['status' => 'error', 'message' => 'Invalid salary/rate.'];
            }

            $filePath = null;

            if ($photo && isset($photo['tmp_name']) && $photo['tmp_name'] && $photo['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/manpower/';

                $result = $this->safeUpload($photo, $uploadDir, 'MP', self::ALLOWED_PHOTO_EXT);
                if (is_string($result) && strpos($result, 'uploads/') === 0) {
                    $filePath = $result;
                } elseif (is_string($result)) {
                    return ['status' => 'error', 'message' => $result];
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO manpower (name, skills, contact_number, address, position, rate, project_id, project_site_text, foreman, photo_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $skills,
                $contact_number ?: null,
                $address ?: null,
                $position,
                $salary,
                $project_id ?: null,
                $project_site_text ?: null,
                $foreman ?: null,
                $filePath,
                $status
            ]);

            $this->pdo->prepare("
                INSERT IGNORE INTO skill_categories (name) 
                VALUES (?)
            ")->execute([$skills]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Add manpower failed: ' . $e->getMessage()
            ];
        }
    }

    public function updateManpower($id, $name, $skills, $position, $salary, $project_id, $foreman, $contact_number, $address, $status, $photo, $project_site_text = '')
    {
        try {
            $name = trim($name);
            $skills = trim($skills);
            $position = trim($position);
            $salary = trim((string) $salary);
            $foreman = trim($foreman);
            $contact_number = trim($contact_number);
            $address = trim($address);
            $status = trim($status) ?: 'Active';
            $project_site_text = trim($project_site_text);

            if ($id === '' || !$id) {
                return ['status' => 'error', 'message' => 'Worker ID is required.'];
            }
            if ($name === '' || $skills === '' || $position === '' || $salary === '') {
                return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
            }

            $salary = preg_replace('/[^0-9.]/', '', $salary);

            if ($salary === '' || !is_numeric($salary)) {
                return ['status' => 'error', 'message' => 'Invalid salary/rate.'];
            }

            $filePath = null;
            $hasNewFile = false;

            if ($photo && isset($photo['tmp_name']) && $photo['tmp_name'] && $photo['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/manpower/';
                $result = $this->safeUpload($photo, $uploadDir, 'MP', self::ALLOWED_PHOTO_EXT);
                if (is_string($result) && strpos($result, 'uploads/') === 0) {
                    $filePath = $result;
                    $hasNewFile = true;
                } elseif (is_string($result)) {
                    return ['status' => 'error', 'message' => $result];
                }
            }

            if ($hasNewFile) {
                $this->pdo->prepare("
                    UPDATE manpower SET name = ?, skills = ?, contact_number = ?, address = ?, position = ?, rate = ?, project_id = ?, project_site_text = ?, foreman = ?, photo_path = ?, status = ?
                    WHERE id = ?
                ")->execute([$name, $skills, $contact_number ?: null, $address ?: null, $position, $salary, $project_id ?: null, $project_site_text ?: null, $foreman ?: null, $filePath, $status, $id]);
            } else {
                $this->pdo->prepare("
                    UPDATE manpower SET name = ?, skills = ?, contact_number = ?, address = ?, position = ?, rate = ?, project_id = ?, project_site_text = ?, foreman = ?, status = ?
                    WHERE id = ?
                ")->execute([$name, $skills, $contact_number ?: null, $address ?: null, $position, $salary, $project_id ?: null, $project_site_text ?: null, $foreman ?: null, $status, $id]);
            }

            $this->pdo->prepare("INSERT IGNORE INTO skill_categories (name) VALUES (?)")->execute([$skills]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Update manpower failed: ' . $e->getMessage()];
        }
    }

    public function bulkAddManpower($itemsJson)
    {
        try {
            $items = json_decode($itemsJson, true);

            if (!is_array($items) || count($items) === 0) {
                return ['status' => 'error', 'message' => 'No records received.'];
            }

            $this->pdo->beginTransaction();

            $insertStmt = $this->pdo->prepare("
            INSERT INTO manpower (name, skills, position, rate, project_id, photo_path)
            VALUES (?, ?, ?, ?, ?, NULL)
        ");

            $skillStmt = $this->pdo->prepare("
            INSERT IGNORE INTO skill_categories (name)
            VALUES (?)
        ");

            $projectStmt = $this->pdo->prepare("
            SELECT id FROM projects 
            WHERE name = ? OR location = ?
            LIMIT 1
        ");

            $inserted = 0;
            $skipped = [];
            $projectCache = [];

            foreach ($items as $index => $item) {
                $lineNumber = $index + 1;

                $name = trim($item['name'] ?? '');
                $skills = trim($item['skills'] ?? '');
                $position = trim($item['position'] ?? '');
                $salaryRaw = trim((string) ($item['salary'] ?? ''));
                $projectRaw = trim((string) ($item['project'] ?? ''));

                $salary = preg_replace('/[^0-9.]/', '', $salaryRaw);

                if ($name === '' || $skills === '' || $position === '' || $salary === '' || !is_numeric($salary)) {
                    $skipped[] = [
                        'line' => $lineNumber,
                        'reason' => 'Incomplete or invalid data'
                    ];
                    continue;
                }

                $projectId = null;

                if ($projectRaw !== '') {
                    if (is_numeric($projectRaw)) {
                        $projectId = (int) $projectRaw;
                    } else {
                        $projectKey = strtolower($projectRaw);

                        if (!array_key_exists($projectKey, $projectCache)) {
                            $projectStmt->execute([$projectRaw, $projectRaw]);
                            $foundProjectId = $projectStmt->fetchColumn();
                            $projectCache[$projectKey] = $foundProjectId ?: null;
                        }

                        $projectId = $projectCache[$projectKey];
                    }
                }

                $insertStmt->execute([
                    $name,
                    $skills,
                    $position,
                    $salary,
                    $projectId
                ]);

                $skillStmt->execute([$skills]);

                $inserted++;
            }

            $this->pdo->commit();

            if ($inserted === 0) {
                return [
                    'status' => 'error',
                    'message' => 'No valid records were added.',
                    'skipped' => $skipped
                ];
            }

            return [
                'status' => 'success',
                'inserted' => $inserted,
                'skipped' => $skipped
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'status' => 'error',
                'message' => 'Bulk add failed: ' . $e->getMessage()
            ];
        }
    }

    public function updateBioData($worker_id, $photo)
    {
        if (!$photo || !isset($photo['tmp_name']) || !$photo['tmp_name'] || $photo['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'No file uploaded.'];
        }

        $uploadDir = __DIR__ . '/../uploads/manpower/';
        $result = $this->safeUpload($photo, $uploadDir, 'MP', self::ALLOWED_PHOTO_EXT);

        if (is_string($result) && strpos($result, 'uploads/') === 0) {
            $this->pdo->prepare("UPDATE manpower SET photo_path = ? WHERE id = ?")->execute([$result, $worker_id]);
            return ['status' => 'success'];
        }

        return ['status' => 'error', 'message' => is_string($result) ? $result : 'Upload failed.'];
    }

    public function getAwardCosts()
    {
        try {
            return $this->pdo->query("
                SELECT a.*, p.name as project_name 
                FROM award_costs a 
                LEFT JOIN projects p ON a.project_id = p.id 
                ORDER BY a.created_at DESC
            ")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAwardCostById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, p.name as project_name 
                FROM award_costs a 
                LEFT JOIN projects p ON a.project_id = p.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ? ['status' => 'success', 'data' => $result] : ['status' => 'error', 'message' => 'Record not found.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addAwardCost($project_id, $service_agreement_code, $block_no, $lot_no, $location, $item, $unit, $start_date, $completion_date, $work_description, $project_description, $total_amount, $file)
    {
        try {
            $total_amount = preg_replace('/[^0-9.]/', '', $total_amount);

            if ($service_agreement_code === '' || $item === '' || $unit === '' || $start_date === '' || $completion_date === '' || $work_description === '' || $project_description === '' || $total_amount === '' || !is_numeric($total_amount)) {
                return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
            }
            if ($total_amount < 0) {
                return ['status' => 'error', 'message' => 'Total Amount cannot be negative.'];
            }
            if ($completion_date < $start_date) {
                return ['status' => 'error', 'message' => 'Completion Date cannot be earlier than Start Date.'];
            }

            $filePath = null;
            if ($file && isset($file['tmp_name']) && $file['tmp_name'] && $file['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/award/';
                $result = $this->safeUpload($file, $uploadDir, 'AWD', self::ALLOWED_AWARD_EXT);
                if (is_string($result) && strpos($result, 'uploads/') === 0) {
                    $filePath = $result;
                } elseif (is_string($result)) {
                    return ['status' => 'error', 'message' => $result];
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO award_costs 
                (project_id, service_agreement_code, block_no, lot_no, location, item, unit, start_date, completion_date, work_description, project_description, total_amount, attachment_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$project_id ?: null, $service_agreement_code, $block_no, $lot_no, $location, $item, $unit, $start_date, $completion_date, $work_description, $project_description, $total_amount, $filePath]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Add award cost failed: ' . $e->getMessage()];
        }
    }

    public function updateAwardCost($id, $project_id, $service_agreement_code, $block_no, $lot_no, $location, $item, $unit, $start_date, $completion_date, $work_description, $project_description, $total_amount, $file)
    {
        try {
            $total_amount = preg_replace('/[^0-9.]/', '', $total_amount);

            if ($service_agreement_code === '' || $item === '' || $unit === '' || $start_date === '' || $completion_date === '' || $work_description === '' || $project_description === '' || $total_amount === '' || !is_numeric($total_amount)) {
                return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
            }
            if ($total_amount < 0) {
                return ['status' => 'error', 'message' => 'Total Amount cannot be negative.'];
            }
            if ($completion_date < $start_date) {
                return ['status' => 'error', 'message' => 'Completion Date cannot be earlier than Start Date.'];
            }

            $filePath = null;
            $hasNewFile = false;
            if ($file && isset($file['tmp_name']) && $file['tmp_name'] && $file['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/award/';
                $result = $this->safeUpload($file, $uploadDir, 'AWD', self::ALLOWED_AWARD_EXT);
                if (is_string($result) && strpos($result, 'uploads/') === 0) {
                    $filePath = $result;
                    $hasNewFile = true;
                } elseif (is_string($result)) {
                    return ['status' => 'error', 'message' => $result];
                }
            }

            if ($hasNewFile) {
                $stmt = $this->pdo->prepare("
                    UPDATE award_costs SET 
                    project_id = ?, service_agreement_code = ?, block_no = ?, lot_no = ?, location = ?, 
                    item = ?, unit = ?, start_date = ?, completion_date = ?, 
                    work_description = ?, project_description = ?, total_amount = ?, attachment_path = ?
                    WHERE id = ?
                ");
                $stmt->execute([$project_id ?: null, $service_agreement_code, $block_no, $lot_no, $location, $item, $unit, $start_date, $completion_date, $work_description, $project_description, $total_amount, $filePath, $id]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE award_costs SET 
                    project_id = ?, service_agreement_code = ?, block_no = ?, lot_no = ?, location = ?, 
                    item = ?, unit = ?, start_date = ?, completion_date = ?, 
                    work_description = ?, project_description = ?, total_amount = ?
                    WHERE id = ?
                ");
                $stmt->execute([$project_id ?: null, $service_agreement_code, $block_no, $lot_no, $location, $item, $unit, $start_date, $completion_date, $work_description, $project_description, $total_amount, $id]);
            }

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Update award cost failed: ' . $e->getMessage()];
        }
    }

    public function deleteAwardCost($id)
    {
        $this->pdo->prepare("DELETE FROM award_costs WHERE id = ?")->execute([$id]);
        return ['status' => 'success'];
    }

    public function searchAwardCosts($query)
    {
        try {
            $q = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT a.*, p.name as project_name 
                FROM award_costs a 
                LEFT JOIN projects p ON a.project_id = p.id 
                WHERE a.service_agreement_code LIKE ? 
                OR p.name LIKE ? 
                OR a.block_no LIKE ? 
                OR a.lot_no LIKE ? 
                OR a.location LIKE ? 
                OR a.item LIKE ? 
                OR a.work_description LIKE ? 
                OR a.project_description LIKE ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$q, $q, $q, $q, $q, $q, $q, $q]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllCompletedTasks()
    {
        try {
            return $this->pdo->query("SELECT a.*, p.name as project_name, p.location as project_location FROM project_accomplishments a JOIN projects p ON a.project_id = p.id WHERE a.status = 'Completed' AND a.assigned_worker IS NOT NULL")->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    public function getPayroll()
    {
        try {
            return $this->pdo->query("SELECT p.*, m.name FROM payroll p JOIN manpower m ON p.manpower_id = m.id")->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    public function bulkAddAll($module, $itemsJson)
    {
        try {
            $module = strtolower(trim($module));
            $items = json_decode($itemsJson, true);

            if (!is_array($items) || count($items) === 0) {
                return ['status' => 'error', 'message' => 'No records received.'];
            }

            $allowedModules = [
                'projects',
                'suppliers',
                'inventory',
                'manpower',
                'award_costs',
                'payroll',
                'cash_release',
                'ntp'
            ];

            if (!in_array($module, $allowedModules, true)) {
                return ['status' => 'error', 'message' => 'Invalid bulk module.'];
            }

            $inserted = 0;
            $skipped = [];

            $this->pdo->beginTransaction();

            $projectLookupStmt = $this->pdo->prepare("
            SELECT id FROM projects 
            WHERE id = ? OR name = ? OR location = ? 
            LIMIT 1
        ");

            foreach ($items as $index => $item) {
                $lineNumber = $index + 1;

                try {
                    if ($module === 'projects') {
                        $name = trim($item['name'] ?? '');
                        $client = trim($item['client'] ?? '');
                        $location = trim($item['location'] ?? '');
                        $desc = trim($item['description'] ?? '');
                        $foreman = trim($item['foreman'] ?? '');
                        $startDate = trim($item['start_date'] ?? '');
                        $block_no = trim($item['block_no'] ?? $item['block'] ?? '');
                        $lot_no = trim($item['lot_no'] ?? $item['lot'] ?? '');
                        $foreman_2 = trim($item['foreman_2'] ?? $item['foreman2'] ?? '');
                        $completion_date = trim($item['completion_date'] ?? '') ?: null;
                        $work_description = trim($item['work_description'] ?? '') ?: null;
                        $project_description = trim($item['project_description'] ?? '') ?: null;
                        $total_amount = preg_replace('/[^0-9.]/', '', (string) ($item['total_amount'] ?? '0'));

                        if ($name === '' || $location === '' || $foreman === '' || $startDate === '') {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Project name, location, foreman, and start date are required.'];
                            continue;
                        }

                        $stmt = $this->pdo->prepare("
                        INSERT INTO projects 
                        (name, block_no, lot_no, client_name, location, description, foreman, foreman_2, start_date, completion_date, work_description, project_description, total_amount, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ongoing')
                    ");
                        $stmt->execute([$name, $block_no, $lot_no, $client ?: '-', $location, $desc, $foreman, $foreman_2 ?: null, $startDate, $completion_date, $work_description, $project_description, $total_amount ?: 0]);

                        $projectId = $this->pdo->lastInsertId();

                        try {
                            $this->pdo->prepare("INSERT INTO project_costs (project_id) VALUES (?)")->execute([$projectId]);
                        } catch (Exception $e) {
                        }

                        $this->generateDefaultChecklist($projectId);
                        $inserted++;
                        continue;
                    }

                    if ($module === 'suppliers') {
                        $name = trim($item['name'] ?? '');
                        $materials = trim($item['materials'] ?? '');
                        $contact = trim($item['contact'] ?? '');
                        $email = trim($item['email'] ?? '');

                        if ($name === '' || $materials === '' || $contact === '') {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Supplier name, materials, and contact are required.'];
                            continue;
                        }

                        $this->pdo->prepare("
                        INSERT INTO suppliers (name, materials, contact, email, status) 
                        VALUES (?, ?, ?, ?, 'Active')
                    ")->execute([$name, $materials, $contact, $email]);

                        $inserted++;
                        continue;
                    }

                    if ($module === 'inventory') {
                        $name = trim($item['name'] ?? '');
                        $category = trim($item['category'] ?? '');
                        $qty = preg_replace('/[^0-9.]/', '', (string) ($item['qty'] ?? ''));
                        $unit = trim($item['unit'] ?? '');
                        $cost = preg_replace('/[^0-9.]/', '', (string) ($item['cost'] ?? ''));
                        $supplier = trim($item['supplier'] ?? '');

                        if ($name === '' || $category === '' || $qty === '' || $unit === '' || $cost === '' || !is_numeric($qty) || !is_numeric($cost)) {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Item name, category, quantity, unit, and cost are required.'];
                            continue;
                        }

                        $this->pdo->prepare("
                        INSERT IGNORE INTO inventory_categories (name) 
                        VALUES (?)
                    ")->execute([$category]);

                        $this->pdo->prepare("
                        INSERT INTO inventory (name, category, stock, unit, unit_cost, supplier) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")->execute([$name, $category, $qty, $unit, $cost, $supplier]);

                        $inserted++;
                        continue;
                    }

                    if ($module === 'manpower') {
                        $name = trim($item['name'] ?? '');
                        $skills = trim($item['skills'] ?? '');
                        $position = trim($item['position'] ?? '');
                        $salary = preg_replace('/[^0-9.]/', '', (string) ($item['salary'] ?? ''));
                        $projectRaw = trim((string) ($item['project'] ?? ''));
                        $projectId = null;

                        if ($name === '' || $skills === '' || $position === '' || $salary === '' || !is_numeric($salary)) {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Name, skills, position, and daily rate are required.'];
                            continue;
                        }

                        if ($projectRaw !== '') {
                            $projectLookupStmt->execute([$projectRaw, $projectRaw, $projectRaw]);
                            $projectId = $projectLookupStmt->fetchColumn() ?: null;
                        }

                        $this->pdo->prepare("
                        INSERT INTO manpower (name, skills, position, rate, project_id, photo_path) 
                        VALUES (?, ?, ?, ?, ?, NULL)
                    ")->execute([$name, $skills, $position, $salary, $projectId]);

                        $this->pdo->prepare("
                        INSERT IGNORE INTO skill_categories (name) 
                        VALUES (?)
                    ")->execute([$skills]);

                        $inserted++;
                        continue;
                    }

                    if ($module === 'award_costs') {
                        $projectRaw = trim((string) ($item['project'] ?? ''));
                        $service_agreement_code = trim($item['service_agreement_code'] ?? $item['service_code'] ?? '');
                        $item_name = trim($item['item'] ?? '');
                        $unit = trim($item['unit'] ?? '');
                        $start_date = trim($item['start_date'] ?? '');
                        $completion_date = trim($item['completion_date'] ?? '');
                        $work_description = trim($item['work_description'] ?? $item['description'] ?? '');
                        $project_description = trim($item['project_description'] ?? $item['project_desc'] ?? '');
                        $total_amount = preg_replace('/[^0-9.]/', '', (string) ($item['total_amount'] ?? $item['amount'] ?? ''));

                        if ($service_agreement_code === '' || $item_name === '' || $unit === '' || $start_date === '' || $completion_date === '' || $work_description === '' || $project_description === '' || $total_amount === '' || !is_numeric($total_amount)) {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Service agreement code, item, unit, dates, descriptions, and amount are required.'];
                            continue;
                        }

                        $projectId = null;
                        if ($projectRaw !== '') {
                            if (is_numeric($projectRaw)) {
                                $projectId = (int) $projectRaw;
                            } else {
                                $projectLookupStmt->execute([$projectRaw, $projectRaw, $projectRaw]);
                                $projectId = $projectLookupStmt->fetchColumn() ?: null;
                            }
                        }

                        $block_no = trim($item['block_no'] ?? $item['block'] ?? '');
                        $lot_no = trim($item['lot_no'] ?? $item['lot'] ?? '');
                        $location = trim($item['location'] ?? '');

                        $this->pdo->prepare("
                        INSERT INTO award_costs 
                        (project_id, service_agreement_code, block_no, lot_no, location, item, unit, start_date, completion_date, work_description, project_description, total_amount) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$projectId, $service_agreement_code, $block_no, $lot_no, $location, $item_name, $unit, $start_date, $completion_date, $work_description, $project_description, $total_amount]);

                        $inserted++;
                        continue;
                    }

                    if ($module === 'payroll') {
                        $date = trim($item['date'] ?? '');
                        $name = trim($item['name'] ?? '');
                        $job = trim($item['job_desc'] ?? '');
                        $award = preg_replace('/[^0-9.]/', '', (string) ($item['award'] ?? '0'));
                        $advance = preg_replace('/[^0-9.]/', '', (string) ($item['advance'] ?? '0'));

                        if ($date === '' || $name === '' || $job === '') {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Date, name, and job description are required.'];
                            continue;
                        }

                        $stmt = $this->pdo->prepare("SELECT id FROM manpower WHERE name = ? LIMIT 1");
                        $stmt->execute([$name]);
                        $worker = $stmt->fetch();

                        if (!$worker) {
                            $this->pdo->prepare("
                            INSERT INTO manpower (name, position, skills, rate) 
                            VALUES (?, 'Worker', 'Uncategorized', 500)
                        ")->execute([$name]);

                            $manpowerId = $this->pdo->lastInsertId();
                        } else {
                            $manpowerId = $worker['id'];
                        }

                        $this->pdo->prepare("
                        INSERT INTO payroll 
                        (manpower_id, pay_date, job_description, rate, days_worked, gross_pay, deductions, net_pay, award_cost, cash_advance, overall_advance, balance) 
                        VALUES (?, ?, ?, 0, 0, 0, 0, 0, ?, ?, 0, 0)
                    ")->execute([$manpowerId, $date, $job, $award ?: 0, $advance ?: 0]);

                        $inserted++;
                        continue;
                    }

                    if ($module === 'cash_release') {
                        $date = trim($item['date'] ?? '');
                        $category = trim($item['category'] ?? '');
                        $name = trim($item['name'] ?? '');
                        $desc = trim($item['description'] ?? '');
                        $amount = preg_replace('/[^0-9.]/', '', (string) ($item['amount'] ?? ''));

                        if ($date === '' || $category === '' || $name === '' || $amount === '' || !is_numeric($amount)) {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Date, category, name, and amount are required.'];
                            continue;
                        }

                        $this->pdo->prepare("
                        INSERT INTO cash_releases (project_id, release_date, category, released_to, release_description, release_amount) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")->execute([0, $date, $category, $name, $desc, $amount]);

                        $inserted++;
                        continue;
                    }

                    if ($module === 'ntp') {
                        $projectRaw = trim((string) ($item['project'] ?? ''));
                        $ticket = trim($item['ticket'] ?? '');
                        $date = trim($item['date'] ?? '');
                        $awardCost = preg_replace('/[^0-9.]/', '', (string) ($item['award_cost'] ?? '0'));
                        $dueDate = trim($item['due_date'] ?? '');
                        $acceptDate = trim($item['accept_date'] ?? '');
                        $completion_date = trim($item['completion_date'] ?? '') ?: null;
                        $work_description = trim($item['work_description'] ?? '') ?: null;
                        $project_description = trim($item['project_description'] ?? '') ?: null;
                        $total_amount = preg_replace('/[^0-9.]/', '', (string) ($item['total_amount'] ?? '0'));

                        if ($projectRaw === '' || $date === '' || $dueDate === '') {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Project, date received, and due date are required.'];
                            continue;
                        }

                        $projectLookupStmt->execute([$projectRaw, $projectRaw, $projectRaw]);
                        $projectId = $projectLookupStmt->fetchColumn();

                        if (!$projectId) {
                            $skipped[] = ['line' => $lineNumber, 'reason' => 'Project not found. Use project ID or exact project/location name.'];
                            continue;
                        }

                        $this->pdo->prepare("
                        INSERT INTO project_ntp 
                        (project_id, ntp_ticket, date_received, award_cost, due_date, acceptance_date, file_path) 
                        VALUES (?, ?, ?, ?, ?, ?, '')
                    ")->execute([$projectId, $ticket, $date, $awardCost ?: 0, $dueDate, $acceptDate]);

                        $this->pdo->prepare("
                        UPDATE projects SET status = 'ongoing', completion_date=?, work_description=?, project_description=?, total_amount=? WHERE id = ?
                    ")->execute([$completion_date, $work_description, $project_description, $total_amount ?: 0, $projectId]);

                        $inserted++;
                        continue;
                    }
                } catch (Exception $rowError) {
                    $skipped[] = ['line' => $lineNumber, 'reason' => $rowError->getMessage()];
                    continue;
                }
            }

            $this->pdo->commit();

            if ($inserted === 0) {
                return [
                    'status' => 'error',
                    'message' => 'No valid records were added.',
                    'inserted' => 0,
                    'skipped' => $skipped
                ];
            }

            return [
                'status' => 'success',
                'inserted' => $inserted,
                'skipped' => $skipped
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'status' => 'error',
                'message' => 'Bulk add failed: ' . $e->getMessage()
            ];
        }
    }
    public function deletePayrollEntry($id)
    {
        try {
            $this->pdo->prepare("DELETE FROM payroll WHERE id = ?")->execute([$id]);
            return ['status' => 'success'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    public function editPayrollEntry($id, $award, $advance)
    {
        try {
            $this->pdo->prepare("UPDATE payroll SET award_cost = ?, cash_advance = ? WHERE id = ?")->execute([$award, $advance, $id]);
            return ['status' => 'success'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addPayroll($date, $name, $job_desc, $award, $advance)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM manpower WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $worker = $stmt->fetch();

            if (!$worker) {
                $this->pdo->prepare("INSERT INTO manpower (name, position, skills, rate) VALUES (?, 'Worker', 'Uncategorized', 500)")->execute([$name]);
                $manpowerId = $this->pdo->lastInsertId();
            } else {
                $manpowerId = $worker['id'];
            }

            $this->pdo->prepare("INSERT INTO payroll (manpower_id, pay_date, job_description, rate, days_worked, gross_pay, deductions, net_pay, award_cost, cash_advance, overall_advance, balance) VALUES (?, ?, ?, 0, 0, 0, 0, 0, ?, ?, 0, 0)")->execute([$manpowerId, $date, $job_desc, $award ?: 0, $advance ?: 0]);
            return ['status' => 'success'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function archiveAndResetPayroll()
    {
        $cycle = 'CYCLE-' . date('Ymd-His');
        $payroll = $this->getPayroll();
        $balances = [];
        foreach ($payroll as $p) {
            $key = $p['manpower_id'] . '_' . $p['job_description'];
            if (!isset($balances[$key]))
                $balances[$key] = 0;
            $balances[$key] += ($p['award_cost'] - $p['cash_advance']);
        }
        $stmt = $this->pdo->prepare("INSERT INTO payroll_history (cycle_id, manpower_id, pay_date, job_description, rate, net_pay, award_cost, cash_advance, overall_advance, balance) VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?, ?)");
        $deleteStmt = $this->pdo->prepare("DELETE FROM payroll WHERE id = ?");
        $archivedCount = 0;
        foreach ($payroll as $p) {
            $key = $p['manpower_id'] . '_' . $p['job_description'];
            if ($balances[$key] <= 0) {
                $stmt->execute([$cycle, $p['manpower_id'], $p['pay_date'], $p['job_description'], $p['award_cost'], $p['cash_advance'], $p['overall_advance'], $p['balance']]);
                $deleteStmt->execute([$p['id']]);
                $archivedCount++;
            }
        }
        return ['status' => 'success', 'archived' => $archivedCount];
    }
    public function getPayrollHistory()
    {
        $this->pdo->query("DELETE FROM payroll_history WHERE pay_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)");
        return $this->pdo->query("SELECT h.*, m.name FROM payroll_history h JOIN manpower m ON h.manpower_id = m.id ORDER BY h.pay_date DESC, h.id DESC")->fetchAll();
    }

    // =========================
    // PAYROLL ENTRIES (Manpower & Subcon)
    // =========================
    public function getPayrollEntryRecords($project_id = '')
    {
        try {
            if ($project_id !== '') {
                $stmt = $this->pdo->prepare("
                    SELECT e.*, p.name as project_name, m.name as worker_name
                    FROM payroll_entries e
                    LEFT JOIN projects p ON e.project_id = p.id
                    LEFT JOIN manpower m ON e.worker_id = m.id
                    WHERE e.project_id = ?
                    ORDER BY e.created_at DESC
                ");
                $stmt->execute([$project_id]);
            } else {
                $stmt = $this->pdo->query("
                    SELECT e.*, p.name as project_name, m.name as worker_name
                    FROM payroll_entries e
                    LEFT JOIN projects p ON e.project_id = p.id
                    LEFT JOIN manpower m ON e.worker_id = m.id
                    ORDER BY e.created_at DESC
                ");
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPayrollEntryRecordById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, p.name as project_name, m.name as worker_name
                FROM payroll_entries e
                LEFT JOIN projects p ON e.project_id = p.id
                LEFT JOIN manpower m ON e.worker_id = m.id
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ? ['status' => 'success', 'data' => $result] : ['status' => 'error', 'message' => 'Record not found.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addPayrollEntryRecord($project_id, $worker_id, $foreman, $payroll_type, $payee_name, $position_or_role, $skill, $period_start, $period_end, $daily_rate, $days_worked, $overtime_hours, $overtime_rate, $deductions, $payment_method, $payroll_status, $subcon_company, $subcon_scope, $subcon_reference_no, $remarks, $amount = 0)
    {
        try {
            $amount = floatval(preg_replace('/[^0-9.]/', '', $amount));

            if ($project_id === '' || !$project_id) {
                return ['status' => 'error', 'message' => 'Project Site / NTP is required.'];
            }
            if ($payroll_type === '') {
                return ['status' => 'error', 'message' => 'Payroll Type is required.'];
            }
            if ($payee_name === '') {
                return ['status' => 'error', 'message' => 'Payee Name is required.'];
            }
            if ($period_start === '') {
                return ['status' => 'error', 'message' => 'Period Start is required.'];
            }
            if ($period_end === '') {
                return ['status' => 'error', 'message' => 'Period End is required.'];
            }

            $start = new DateTime($period_start);
            $end = new DateTime($period_end);
            if ($end < $start) {
                return ['status' => 'error', 'message' => 'Period End cannot be earlier than Period Start.'];
            }

            if ($amount < 0) {
                return ['status' => 'error', 'message' => 'Amount cannot be negative.'];
            }

            if ($amount > 0) {
                $daily_rate = 0;
                $days_worked = 0;
                $overtime_hours = 0;
                $overtime_rate = 0;
                $deductions = 0;
                $gross_amount = $amount;
                $net_amount = $amount;
            } else {
                $daily_rate = floatval(preg_replace('/[^0-9.]/', '', $daily_rate));
                $days_worked = floatval(preg_replace('/[^0-9.]/', '', $days_worked));
                $overtime_hours = floatval(preg_replace('/[^0-9.]/', '', $overtime_hours));
                $overtime_rate = floatval(preg_replace('/[^0-9.]/', '', $overtime_rate));
                $deductions = floatval(preg_replace('/[^0-9.]/', '', $deductions));

                if ($deductions < 0) {
                    return ['status' => 'error', 'message' => 'Deductions cannot be negative.'];
                }

                $gross_amount = ($daily_rate * $days_worked) + ($overtime_hours * $overtime_rate);
                $net_amount = $gross_amount - $deductions;

                if ($net_amount < 0) {
                    return ['status' => 'error', 'message' => 'Net Amount cannot be negative. Reduce deductions.'];
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO payroll_entries 
                (project_id, worker_id, foreman, payroll_type, payee_name, position_or_role, skill, period_start, period_end, daily_rate, days_worked, overtime_hours, overtime_rate, gross_amount, deductions, net_amount, payment_method, payroll_status, subcon_company, subcon_scope, subcon_reference_no, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $project_id,
                $worker_id ?: null,
                $foreman ?: null,
                $payroll_type,
                $payee_name,
                $position_or_role ?: null,
                $skill ?: null,
                $period_start,
                $period_end,
                $daily_rate,
                $days_worked,
                $overtime_hours,
                $overtime_rate,
                $gross_amount,
                $deductions,
                $net_amount,
                $payment_method ?: null,
                $payroll_status ?: 'Pending',
                $subcon_company ?: null,
                $subcon_scope ?: null,
                $subcon_reference_no ?: null,
                $remarks ?: null
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Add payroll entry failed: ' . $e->getMessage()];
        }
    }

    public function updatePayrollEntryRecord($id, $project_id, $worker_id, $foreman, $payroll_type, $payee_name, $position_or_role, $skill, $period_start, $period_end, $daily_rate, $days_worked, $overtime_hours, $overtime_rate, $deductions, $payment_method, $payroll_status, $subcon_company, $subcon_scope, $subcon_reference_no, $remarks, $amount = 0)
    {
        try {
            $amount = floatval(preg_replace('/[^0-9.]/', '', $amount));

            if ($id === '' || !$id) {
                return ['status' => 'error', 'message' => 'Payroll entry ID is required.'];
            }
            if ($project_id === '' || !$project_id) {
                return ['status' => 'error', 'message' => 'Project Site / NTP is required.'];
            }
            if ($payroll_type === '') {
                return ['status' => 'error', 'message' => 'Payroll Type is required.'];
            }
            if ($payee_name === '') {
                return ['status' => 'error', 'message' => 'Payee Name is required.'];
            }
            if ($period_start === '') {
                return ['status' => 'error', 'message' => 'Period Start is required.'];
            }
            if ($period_end === '') {
                return ['status' => 'error', 'message' => 'Period End is required.'];
            }

            $start = new DateTime($period_start);
            $end = new DateTime($period_end);
            if ($end < $start) {
                return ['status' => 'error', 'message' => 'Period End cannot be earlier than Period Start.'];
            }

            if ($amount < 0) {
                return ['status' => 'error', 'message' => 'Amount cannot be negative.'];
            }

            if ($amount > 0) {
                $daily_rate = 0;
                $days_worked = 0;
                $overtime_hours = 0;
                $overtime_rate = 0;
                $deductions = 0;
                $gross_amount = $amount;
                $net_amount = $amount;
            } else {
                $daily_rate = floatval(preg_replace('/[^0-9.]/', '', $daily_rate));
                $days_worked = floatval(preg_replace('/[^0-9.]/', '', $days_worked));
                $overtime_hours = floatval(preg_replace('/[^0-9.]/', '', $overtime_hours));
                $overtime_rate = floatval(preg_replace('/[^0-9.]/', '', $overtime_rate));
                $deductions = floatval(preg_replace('/[^0-9.]/', '', $deductions));

                if ($deductions < 0) {
                    return ['status' => 'error', 'message' => 'Deductions cannot be negative.'];
                }

                $gross_amount = ($daily_rate * $days_worked) + ($overtime_hours * $overtime_rate);
                $net_amount = $gross_amount - $deductions;

                if ($net_amount < 0) {
                    return ['status' => 'error', 'message' => 'Net Amount cannot be negative. Reduce deductions.'];
                }
            }

            $stmt = $this->pdo->prepare("
                UPDATE payroll_entries SET 
                project_id = ?, worker_id = ?, foreman = ?, payroll_type = ?, payee_name = ?,
                position_or_role = ?, skill = ?, period_start = ?, period_end = ?,
                daily_rate = ?, days_worked = ?, overtime_hours = ?, overtime_rate = ?,
                gross_amount = ?, deductions = ?, net_amount = ?,
                payment_method = ?, payroll_status = ?,
                subcon_company = ?, subcon_scope = ?, subcon_reference_no = ?,
                remarks = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $project_id,
                $worker_id ?: null,
                $foreman ?: null,
                $payroll_type,
                $payee_name,
                $position_or_role ?: null,
                $skill ?: null,
                $period_start,
                $period_end,
                $daily_rate,
                $days_worked,
                $overtime_hours,
                $overtime_rate,
                $gross_amount,
                $deductions,
                $net_amount,
                $payment_method ?: null,
                $payroll_status ?: 'Pending',
                $subcon_company ?: null,
                $subcon_scope ?: null,
                $subcon_reference_no ?: null,
                $remarks ?: null,
                $id
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Update payroll entry failed: ' . $e->getMessage()];
        }
    }

    public function deletePayrollEntryRecord($id)
    {
        try {
            $this->pdo->prepare("DELETE FROM payroll_entries WHERE id = ?")->execute([$id]);
            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Delete payroll entry failed: ' . $e->getMessage()];
        }
    }

    public function searchPayrollEntryRecords($query)
    {
        try {
            $q = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT e.*, p.name as project_name, m.name as worker_name
                FROM payroll_entries e
                LEFT JOIN projects p ON e.project_id = p.id
                LEFT JOIN manpower m ON e.worker_id = m.id
                WHERE p.name LIKE ? 
                   OR e.payroll_type LIKE ?
                   OR e.payee_name LIKE ?
                   OR m.name LIKE ?
                   OR e.foreman LIKE ?
                   OR e.skill LIKE ?
                   OR e.subcon_company LIKE ?
                   OR e.subcon_scope LIKE ?
                   OR e.subcon_reference_no LIKE ?
                   OR e.payroll_status LIKE ?
                   OR e.remarks LIKE ?
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$q, $q, $q, $q, $q, $q, $q, $q, $q, $q, $q]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPayrollEntryRecordSummary($project_id = '')
    {
        try {
            $where = '';
            $params = [];
            if ($project_id !== '') {
                $where = 'WHERE e.project_id = ?';
                $params[] = $project_id;
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN e.payroll_status != 'Cancelled' THEN e.gross_amount ELSE 0 END), 0) as total_gross,
                    COALESCE(SUM(CASE WHEN e.payroll_status != 'Cancelled' THEN e.deductions ELSE 0 END), 0) as total_deductions,
                    COALESCE(SUM(CASE WHEN e.payroll_status != 'Cancelled' THEN e.net_amount ELSE 0 END), 0) as total_net,
                    COALESCE(SUM(CASE WHEN e.payroll_status = 'Paid' THEN e.net_amount ELSE 0 END), 0) as total_paid,
                    COALESCE(SUM(CASE WHEN e.payroll_status = 'Pending' THEN e.net_amount ELSE 0 END), 0) as total_pending,
                    COUNT(CASE WHEN e.payroll_type = 'Manpower' THEN 1 END) as manpower_count,
                    COUNT(CASE WHEN e.payroll_type = 'Subcon' THEN 1 END) as subcon_count
                FROM payroll_entries e
                $where
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            return [
                'total_gross' => 0, 'total_deductions' => 0, 'total_net' => 0,
                'total_paid' => 0, 'total_pending' => 0,
                'manpower_count' => 0, 'subcon_count' => 0
            ];
        }
    }

    // =========================
    // CASH RELEASE (Simple Cash Log)
    // =========================
    public function getCashReleases()
    {
        try {
            return $this->pdo->query("
                SELECT * FROM cash_releases
                ORDER BY release_date DESC, id DESC
            ")->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getCashReleaseById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM cash_releases WHERE id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ? ['status' => 'success', 'data' => $result] : ['status' => 'error', 'message' => 'Record not found.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addCashRelease($release_date, $category, $released_to, $release_description, $release_amount)
    {
        try {
            $release_amount = floatval(preg_replace('/[^0-9.]/', '', $release_amount));

            if ($release_date === '') {
                return ['status' => 'error', 'message' => 'Date is required.'];
            }
            if ($category === '') {
                return ['status' => 'error', 'message' => 'Category is required.'];
            }
            if ($released_to === '') {
                return ['status' => 'error', 'message' => 'Receiver name is required.'];
            }
            if ($release_amount <= 0) {
                return ['status' => 'error', 'message' => 'Amount must be greater than 0.'];
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO cash_releases 
                (project_id, release_date, category, released_to, release_description, release_amount) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                0,
                $release_date,
                $category,
                $released_to,
                $release_description ?: null,
                $release_amount
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Add cash release failed: ' . $e->getMessage()];
        }
    }

    public function updateCashRelease($id, $release_date, $category, $released_to, $release_description, $release_amount)
    {
        try {
            $release_amount = floatval(preg_replace('/[^0-9.]/', '', $release_amount));

            if ($id === '' || !$id) {
                return ['status' => 'error', 'message' => 'Cash release ID is required.'];
            }
            if ($release_date === '') {
                return ['status' => 'error', 'message' => 'Date is required.'];
            }
            if ($category === '') {
                return ['status' => 'error', 'message' => 'Category is required.'];
            }
            if ($released_to === '') {
                return ['status' => 'error', 'message' => 'Receiver name is required.'];
            }
            if ($release_amount <= 0) {
                return ['status' => 'error', 'message' => 'Amount must be greater than 0.'];
            }

            $stmt = $this->pdo->prepare("
                UPDATE cash_releases SET 
                project_id = 0, release_date = ?, category = ?, released_to = ?,
                release_description = ?, release_amount = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $release_date,
                $category,
                $released_to,
                $release_description ?: null,
                $release_amount,
                $id
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Update cash release failed: ' . $e->getMessage()];
        }
    }

    public function deleteCashRelease($id)
    {
        try {
            $this->pdo->prepare("DELETE FROM cash_releases WHERE id = ?")->execute([$id]);
            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Delete cash release failed: ' . $e->getMessage()];
        }
    }

    public function searchCashReleases($query)
    {
        try {
            $q = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT * FROM cash_releases
                WHERE release_date LIKE ?
                   OR category LIKE ?
                   OR released_to LIKE ?
                   OR release_description LIKE ?
                   OR CAST(release_amount AS CHAR) LIKE ?
                ORDER BY release_date DESC, id DESC
            ");
            $stmt->execute([$q, $q, $q, $q, $q]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getCashReleaseCategoryTotals()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(SUM(CASE WHEN category = 'Materials' THEN release_amount ELSE 0 END), 0) as total_materials,
                    COALESCE(SUM(CASE WHEN category = 'Labor' THEN release_amount ELSE 0 END), 0) as total_labor,
                    COALESCE(SUM(CASE WHEN category NOT IN ('Materials', 'Labor') THEN release_amount ELSE 0 END), 0) as total_other,
                    COALESCE(SUM(release_amount), 0) as grand_total
                FROM cash_releases
            ");
            return $stmt->fetch();
        } catch (Exception $e) {
            return ['total_materials' => 0, 'total_labor' => 0, 'total_other' => 0, 'grand_total' => 0];
        }
    }

    public function getCashReleaseSummary($project_id)
    {
        try {
            // Get award cost info if applicable
            $awardData = null;
            $stmt = $this->pdo->prepare("SELECT id, service_agreement_code, total_amount FROM award_costs WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$project_id]);
            $awardData = $stmt->fetch();

            // Get the latest capital_amount set for this project
            $stmt = $this->pdo->prepare("SELECT capital_amount FROM cash_releases WHERE project_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$project_id]);
            $latestCapital = $stmt->fetch();

            // Sum released amounts (excluding cancelled)
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(release_amount), 0) as total_released FROM cash_releases WHERE project_id = ? AND status != 'Cancelled'");
            $stmt->execute([$project_id]);
            $totals = $stmt->fetch();

            // Capital priority: award_cost total_amount > latest capital_amount from records > 0
            $capital = $awardData ? floatval($awardData['total_amount']) : ($latestCapital ? floatval($latestCapital['capital_amount']) : 0);
            $totalReleased = floatval($totals['total_released']);
            $remainingBalance = $capital - $totalReleased;
            $progressPercent = $capital > 0 ? round(($totalReleased / $capital) * 100) : 0;

            // Fund state
            $fundState = 'No Capital';
            if ($capital > 0) {
                if ($remainingBalance < 0) {
                    $fundState = 'Over Budget';
                } elseif ($remainingBalance == 0) {
                    $fundState = 'Fully Used';
                } else {
                    $fundState = 'Within Capital';
                }
            }

            return [
                'project_id' => $project_id,
                'award_total_amount' => $capital,
                'total_released' => $totalReleased,
                'remaining_balance' => $remainingBalance,
                'progress_percent' => $progressPercent,
                'fund_state' => $fundState,
                'service_agreement_code' => $awardData ? $awardData['service_agreement_code'] : null
            ];
        } catch (Exception $e) {
            return [
                'project_id' => $project_id,
                'award_total_amount' => 0,
                'total_released' => 0,
                'remaining_balance' => 0,
                'progress_percent' => 0,
                'fund_state' => 'No Capital',
                'service_agreement_code' => null
            ];
        }
    }

    public function getAwardCostsForCashRelease($project_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, service_agreement_code, item, total_amount 
                FROM award_costs 
                WHERE project_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$project_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllNTPs()
    {
        return $this->pdo->query("SELECT n.*, p.name as project_name, p.completion_date as completion_date_project, p.work_description as work_description_project, p.project_description as project_description_project, p.total_amount as total_amount_project FROM project_ntp n JOIN projects p ON n.project_id = p.id ORDER BY n.due_date ASC")->fetchAll();
    }
    public function uploadNTPFile($project_id, $ticket, $date, $award_cost, $due_date, $accept_date, $file, $completion_date = null, $work_description = null, $project_description = null, $total_amount = 0)
    {
        $filePath = '';
        if ($file && isset($file['tmp_name']) && $file['tmp_name'] && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/ntp/';

            $result = $this->safeUpload($file, $uploadDir, 'NTP', self::ALLOWED_NTP_EXT);
            if (is_string($result) && strpos($result, 'uploads/') === 0) {
                $filePath = $result;
            } elseif (is_string($result)) {
                return ['status' => 'error', 'message' => $result];
            }
        }
        $this->pdo->prepare("INSERT INTO project_ntp (project_id, ntp_ticket, date_received, award_cost, due_date, acceptance_date, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$project_id, $ticket, $date, $award_cost, $due_date, $accept_date, $filePath]);
        $this->pdo->prepare("UPDATE projects SET status = 'ongoing', completion_date=?, work_description=?, project_description=?, total_amount=? WHERE id=?")->execute([$completion_date, $work_description, $project_description, $total_amount ?: 0, $project_id]);
        return ['status' => 'success'];
    }

    // ============================================================
    // BILL OF MATERIALS (BOM)
    // ============================================================
    public function getBOMItems($project_id = '')
    {
        try {
            if ($project_id !== '') {
                $stmt = $this->pdo->prepare("
                    SELECT b.*, p.name as project_name, a.service_agreement_code
                    FROM bill_of_materials b
                    LEFT JOIN projects p ON b.project_id = p.id
                    LEFT JOIN award_costs a ON b.award_cost_id = a.id
                    WHERE b.project_id = ?
                    ORDER BY b.created_at DESC
                ");
                $stmt->execute([$project_id]);
            } else {
                $stmt = $this->pdo->query("
                    SELECT b.*, p.name as project_name, a.service_agreement_code
                    FROM bill_of_materials b
                    LEFT JOIN projects p ON b.project_id = p.id
                    LEFT JOIN award_costs a ON b.award_cost_id = a.id
                    ORDER BY b.created_at DESC
                ");
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getBOMItemById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, p.name as project_name, a.service_agreement_code
                FROM bill_of_materials b
                LEFT JOIN projects p ON b.project_id = p.id
                LEFT JOIN award_costs a ON b.award_cost_id = a.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ? ['status' => 'success', 'data' => $result] : ['status' => 'error', 'message' => 'Record not found.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addBOMItem($project_id, $award_cost_id, $material_name, $description, $quantity, $unit, $unit_cost, $supplier_name, $remarks, $award_cost_text = '')
    {
        try {
            $quantity = floatval(preg_replace('/[^0-9.]/', '', $quantity));
            $unit_cost = floatval(preg_replace('/[^0-9.]/', '', $unit_cost));
            $total_cost = $quantity * $unit_cost;

            if ($project_id === '' || $project_id === null) {
                return ['status' => 'error', 'message' => 'Project Site / NTP is required.'];
            }
            if ($material_name === '') {
                return ['status' => 'error', 'message' => 'Material Name is required.'];
            }
            if ($quantity <= 0) {
                return ['status' => 'error', 'message' => 'Quantity must be greater than 0.'];
            }
            if ($unit === '') {
                return ['status' => 'error', 'message' => 'Unit is required.'];
            }
            if ($unit_cost < 0) {
                return ['status' => 'error', 'message' => 'Unit Cost cannot be negative.'];
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO bill_of_materials 
                (project_id, award_cost_id, award_cost_text, material_name, description, quantity, unit, unit_cost, total_cost, supplier_name, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $project_id ?: null,
                $award_cost_id ?: null,
                $award_cost_text ?: null,
                $material_name,
                $description ?: null,
                $quantity,
                $unit,
                $unit_cost,
                $total_cost,
                $supplier_name ?: null,
                $remarks ?: null
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Add BOM item failed: ' . $e->getMessage()];
        }
    }

    public function updateBOMItem($id, $project_id, $award_cost_id, $material_name, $description, $quantity, $unit, $unit_cost, $supplier_name, $remarks, $award_cost_text = '')
    {
        try {
            $quantity = floatval(preg_replace('/[^0-9.]/', '', $quantity));
            $unit_cost = floatval(preg_replace('/[^0-9.]/', '', $unit_cost));
            $total_cost = $quantity * $unit_cost;

            if ($id === '' || !$id) {
                return ['status' => 'error', 'message' => 'BOM item ID is required.'];
            }
            if ($project_id === '' || $project_id === null) {
                return ['status' => 'error', 'message' => 'Project Site / NTP is required.'];
            }
            if ($material_name === '') {
                return ['status' => 'error', 'message' => 'Material Name is required.'];
            }
            if ($quantity <= 0) {
                return ['status' => 'error', 'message' => 'Quantity must be greater than 0.'];
            }
            if ($unit === '') {
                return ['status' => 'error', 'message' => 'Unit is required.'];
            }
            if ($unit_cost < 0) {
                return ['status' => 'error', 'message' => 'Unit Cost cannot be negative.'];
            }

            $stmt = $this->pdo->prepare("
                UPDATE bill_of_materials SET 
                project_id = ?, award_cost_id = ?, award_cost_text = ?, material_name = ?, description = ?, 
                quantity = ?, unit = ?, unit_cost = ?, total_cost = ?, 
                supplier_name = ?, remarks = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $project_id ?: null,
                $award_cost_id ?: null,
                $award_cost_text ?: null,
                $material_name,
                $description ?: null,
                $quantity,
                $unit,
                $unit_cost,
                $total_cost,
                $supplier_name ?: null,
                $remarks ?: null,
                $id
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Update BOM item failed: ' . $e->getMessage()];
        }
    }

    public function deleteBOMItem($id)
    {
        try {
            $this->pdo->prepare("DELETE FROM bill_of_materials WHERE id = ?")->execute([$id]);
            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Delete BOM item failed: ' . $e->getMessage()];
        }
    }

    public function searchBOMItems($query)
    {
        try {
            $q = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT b.*, p.name as project_name, a.service_agreement_code
                FROM bill_of_materials b
                LEFT JOIN projects p ON b.project_id = p.id
                LEFT JOIN award_costs a ON b.award_cost_id = a.id
                WHERE b.material_name LIKE ? 
                   OR b.description LIKE ?
                   OR b.supplier_name LIKE ?
                   OR b.remarks LIKE ?
                   OR p.name LIKE ?
                   OR b.award_cost_text LIKE ?
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$q, $q, $q, $q, $q, $q]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAwardCostsForBOM($project_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, service_agreement_code, item 
                FROM award_costs 
                WHERE project_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$project_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    // ============================================================
    // BILLING PROGRESS
    // ============================================================
    public function getBillingRecords($project_id = '')
    {
        try {
            if ($project_id !== '') {
                $stmt = $this->pdo->prepare("
                    SELECT b.*, p.name as project_name, a.service_agreement_code, a.total_amount as award_total_amount
                    FROM billing_progress b
                    LEFT JOIN projects p ON b.project_id = p.id
                    LEFT JOIN award_costs a ON b.award_cost_id = a.id
                    WHERE b.project_id = ?
                    ORDER BY b.billing_date DESC, b.id DESC
                ");
                $stmt->execute([$project_id]);
            } else {
                $stmt = $this->pdo->query("
                    SELECT b.*, p.name as project_name, a.service_agreement_code, a.total_amount as award_total_amount
                    FROM billing_progress b
                    LEFT JOIN projects p ON b.project_id = p.id
                    LEFT JOIN award_costs a ON b.award_cost_id = a.id
                    ORDER BY b.billing_date DESC, b.id DESC
                ");
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getBillingRecordById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, p.name as project_name, a.service_agreement_code, a.total_amount as award_total_amount
                FROM billing_progress b
                LEFT JOIN projects p ON b.project_id = p.id
                LEFT JOIN award_costs a ON b.award_cost_id = a.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ? ['status' => 'success', 'data' => $result] : ['status' => 'error', 'message' => 'Record not found.'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addBillingRecord($project_id, $award_cost_id, $billing_date, $billing_reference_no, $billing_description, $amount_billed, $amount_collected, $payment_method, $remarks, $status)
    {
        try {
            $amount_billed = floatval(preg_replace('/[^0-9.]/', '', $amount_billed));
            $amount_collected = floatval(preg_replace('/[^0-9.]/', '', $amount_collected));

            if ($project_id === '' || !$project_id) {
                return ['status' => 'error', 'message' => 'Project Site / NTP is required.'];
            }
            if ($billing_date === '') {
                return ['status' => 'error', 'message' => 'Billing Date is required.'];
            }
            if ($billing_description === '') {
                return ['status' => 'error', 'message' => 'Billing Description is required.'];
            }
            if ($amount_billed <= 0) {
                return ['status' => 'error', 'message' => 'Amount Billed must be greater than 0.'];
            }
            if ($amount_collected < 0) {
                return ['status' => 'error', 'message' => 'Amount Collected cannot be negative.'];
            }
            if ($amount_collected > $amount_billed) {
                return ['status' => 'error', 'message' => 'Amount Collected cannot exceed Amount Billed.'];
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO billing_progress 
                (project_id, award_cost_id, billing_date, billing_reference_no, billing_description, amount_billed, amount_collected, payment_method, remarks, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $project_id,
                $award_cost_id ?: null,
                $billing_date,
                $billing_reference_no ?: null,
                $billing_description,
                $amount_billed,
                $amount_collected,
                $payment_method ?: null,
                $remarks ?: null,
                $status ?: 'Pending'
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Add billing record failed: ' . $e->getMessage()];
        }
    }

    public function updateBillingRecord($id, $project_id, $award_cost_id, $billing_date, $billing_reference_no, $billing_description, $amount_billed, $amount_collected, $payment_method, $remarks, $status)
    {
        try {
            $amount_billed = floatval(preg_replace('/[^0-9.]/', '', $amount_billed));
            $amount_collected = floatval(preg_replace('/[^0-9.]/', '', $amount_collected));

            if ($id === '' || !$id) {
                return ['status' => 'error', 'message' => 'Billing record ID is required.'];
            }
            if ($project_id === '' || !$project_id) {
                return ['status' => 'error', 'message' => 'Project Site / NTP is required.'];
            }
            if ($billing_date === '') {
                return ['status' => 'error', 'message' => 'Billing Date is required.'];
            }
            if ($billing_description === '') {
                return ['status' => 'error', 'message' => 'Billing Description is required.'];
            }
            if ($amount_billed <= 0) {
                return ['status' => 'error', 'message' => 'Amount Billed must be greater than 0.'];
            }
            if ($amount_collected < 0) {
                return ['status' => 'error', 'message' => 'Amount Collected cannot be negative.'];
            }
            if ($amount_collected > $amount_billed) {
                return ['status' => 'error', 'message' => 'Amount Collected cannot exceed Amount Billed.'];
            }

            $stmt = $this->pdo->prepare("
                UPDATE billing_progress SET 
                project_id = ?, award_cost_id = ?, billing_date = ?, billing_reference_no = ?,
                billing_description = ?, amount_billed = ?, amount_collected = ?,
                payment_method = ?, remarks = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $project_id,
                $award_cost_id ?: null,
                $billing_date,
                $billing_reference_no ?: null,
                $billing_description,
                $amount_billed,
                $amount_collected,
                $payment_method ?: null,
                $remarks ?: null,
                $status ?: 'Pending',
                $id
            ]);

            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Update billing record failed: ' . $e->getMessage()];
        }
    }

    public function deleteBillingRecord($id)
    {
        try {
            $this->pdo->prepare("DELETE FROM billing_progress WHERE id = ?")->execute([$id]);
            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Delete billing record failed: ' . $e->getMessage()];
        }
    }

    public function searchBillingRecords($query)
    {
        try {
            $q = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT b.*, p.name as project_name, a.service_agreement_code, a.total_amount as award_total_amount
                FROM billing_progress b
                LEFT JOIN projects p ON b.project_id = p.id
                LEFT JOIN award_costs a ON b.award_cost_id = a.id
                WHERE p.name LIKE ? 
                   OR a.service_agreement_code LIKE ?
                   OR b.billing_reference_no LIKE ?
                   OR b.billing_description LIKE ?
                   OR b.status LIKE ?
                   OR b.remarks LIKE ?
                ORDER BY b.billing_date DESC, b.id DESC
            ");
            $stmt->execute([$q, $q, $q, $q, $q, $q]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getBillingSummary($project_id)
    {
        try {
            // Get award cost info if applicable
            $awardData = null;
            $stmt = $this->pdo->prepare("SELECT id, service_agreement_code, total_amount FROM award_costs WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$project_id]);
            $awardData = $stmt->fetch();

            // Sum billed and collected
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount_billed), 0) as total_billed, COALESCE(SUM(amount_collected), 0) as total_collected FROM billing_progress WHERE project_id = ? AND status != 'Cancelled'");
            $stmt->execute([$project_id]);
            $totals = $stmt->fetch();

            $totalAwardAmount = $awardData ? floatval($awardData['total_amount']) : 0;
            $totalBilled = floatval($totals['total_billed']);
            $totalCollected = floatval($totals['total_collected']);
            $remainingBalance = $totalAwardAmount > 0 ? $totalAwardAmount - $totalCollected : $totalBilled - $totalCollected;
            $remainingBalance = max(0, $remainingBalance);
            $progressPercent = $totalAwardAmount > 0 ? min(100, round(($totalCollected / $totalAwardAmount) * 100)) : 0;

            // Determine overall status
            $overallStatus = 'No Award';
            if ($totalAwardAmount > 0) {
                if ($totalCollected >= $totalAwardAmount) {
                    $overallStatus = 'Collected';
                } elseif ($totalCollected > 0) {
                    $overallStatus = 'Partially Collected';
                } else {
                    $overallStatus = 'Pending';
                }
            } elseif ($totalBilled > 0) {
                $overallStatus = 'No Award Cost';
            }

            return [
                'project_id' => $project_id,
                'award_total_amount' => $totalAwardAmount,
                'total_billed' => $totalBilled,
                'total_collected' => $totalCollected,
                'remaining_balance' => $remainingBalance,
                'progress_percent' => $progressPercent,
                'status' => $overallStatus,
                'service_agreement_code' => $awardData ? $awardData['service_agreement_code'] : null
            ];
        } catch (Exception $e) {
            return [
                'project_id' => $project_id,
                'award_total_amount' => 0,
                'total_billed' => 0,
                'total_collected' => 0,
                'remaining_balance' => 0,
                'progress_percent' => 0,
                'status' => 'No Award',
                'service_agreement_code' => null
            ];
        }
    }

    public function getAwardCostsForBilling($project_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, service_agreement_code, item, total_amount 
                FROM award_costs 
                WHERE project_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$project_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
