<?php
session_start();
header('Content-Type: application/json');

require 'db.php';
require 'AppSystem.php';

$app = new ConstructionSystem($pdo);

// Central session guard
$publicActions = ['login', 'check_session'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$isPublic = in_array($action, $publicActions, true);

if (!$isPublic) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit;
    }
}

// Mutation actions must use POST only
$mutationActions = [
    'add_project', 'update_project', 'update_project_status', 'delete_project',
    'add_checklist_task', 'update_checklist_status', 'edit_checklist_task',
    'update_task_cost', 'delete_checklist_task', 'delete_checklist_category',
    'assign_worker', 'remove_worker',
    'add_supplier', 'update_supplier', 'delete_supplier', 'add_inventory_category', 'add_inventory', 'issue_material',
    'add_manpower', 'update_manpower', 'bulk_add_manpower', 'update_bio_data',
    'add_skill_category', 'edit_skill_category', 'delete_skill_category',
    'archive_manpower', 'restore_manpower',
    'add_award_cost', 'edit_award_cost', 'delete_award_cost',
    'add_payroll', 'edit_payroll_entry', 'delete_payroll_entry', 'archive_and_reset_payroll',
    'add_payroll_entry_record', 'update_payroll_entry_record', 'delete_payroll_entry_record',
    'add_cash_release', 'update_cash_release', 'delete_cash_release', 'get_cash_release_category_totals',
    'upload_ntp_file',
    'add_bom_item', 'edit_bom_item', 'delete_bom_item',
    'add_billing_record', 'edit_billing_record', 'delete_billing_record',
    'bulk_add_all',
    'logout',
];

if (in_array($action, $mutationActions, true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST.']);
    exit;
}

switch ($action) {
    case 'login':
        echo json_encode($app->login($_POST['email'] ?? '', $_POST['password'] ?? ''));
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['status' => 'success']);
        break;

    case 'check_session':
        echo json_encode([
            'logged_in' => isset($_SESSION['user_id']),
            'role' => $_SESSION['role'] ?? ''
        ]);
        break;

    case 'get_stats':
        echo json_encode($app->getDashboardStats());
        break;

    // =========================
    // PROJECTS
    // =========================
    case 'add_project':
        echo json_encode($app->addProject(
            $_POST['name'] ?? '',
            $_POST['client'] ?? '',
            $_POST['location'] ?? '',
            $_POST['desc'] ?? '',
            $_POST['foreman'] ?? '',
            $_POST['start_date'] ?? '',
            $_POST['block_no'] ?? '',
            $_POST['lot_no'] ?? '',
            $_POST['foreman_2'] ?? '',
            $_POST['completion_date'] ?? null,
            $_POST['work_description'] ?? null,
            $_POST['project_description'] ?? null,
            $_POST['total_amount'] ?? 0
        ));
        break;

    case 'update_project':
        echo json_encode($app->updateProject(
            $_POST['id'] ?? '',
            $_POST['name'] ?? '',
            $_POST['client'] ?? '',
            $_POST['location'] ?? '',
            $_POST['desc'] ?? '',
            $_POST['foreman'] ?? '',
            $_POST['start_date'] ?? '',
            $_POST['block_no'] ?? '',
            $_POST['lot_no'] ?? '',
            $_POST['foreman_2'] ?? '',
            $_POST['completion_date'] ?? null,
            $_POST['work_description'] ?? null,
            $_POST['project_description'] ?? null,
            $_POST['total_amount'] ?? 0
        ));
        break;

    case 'get_projects':
        echo json_encode($app->getProjects());
        break;

    case 'update_project_status':
        echo json_encode($app->updateProjectStatus($_POST['id'] ?? '', $_POST['status'] ?? ''));
        break;

    case 'delete_project':
        echo json_encode($app->deleteProject($_POST['id'] ?? ''));
        break;

    case 'get_project_data':
        echo json_encode($app->getProjectData($_POST['project_id'] ?? ''));
        break;

    // =========================
    // CHECKLIST
    // =========================
    case 'add_checklist_task':
        echo json_encode($app->addChecklistTask(
            $_POST['project_id'] ?? '',
            $_POST['category'] ?? '',
            $_POST['task_name'] ?? ''
        ));
        break;

    case 'update_checklist_status':
        echo json_encode($app->updateChecklistStatus($_POST['task_id'] ?? '', $_POST['status'] ?? ''));
        break;

    case 'edit_checklist_task':
        echo json_encode($app->editChecklistTask($_POST['task_id'] ?? '', $_POST['task_name'] ?? ''));
        break;

    case 'update_task_cost':
        echo json_encode($app->updateTaskCost($_POST['task_id'] ?? '', $_POST['cost'] ?? 0));
        break;

    case 'delete_checklist_task':
        echo json_encode($app->deleteChecklistTask($_POST['task_id'] ?? ''));
        break;

    case 'delete_checklist_category':
        echo json_encode($app->deleteChecklistCategory($_POST['project_id'] ?? '', $_POST['category'] ?? ''));
        break;

    case 'assign_worker':
        echo json_encode($app->assignWorker(
            $_POST['project_id'] ?? '',
            $_POST['category'] ?? '',
            $_POST['worker'] ?? ''
        ));
        break;

    case 'remove_worker':
        echo json_encode($app->removeWorkerAssignment($_POST['project_id'] ?? '', $_POST['category'] ?? ''));
        break;

    // =========================
    // SUPPLIERS / INVENTORY
    // =========================
    case 'get_suppliers':
        echo json_encode($app->getSuppliers());
        break;

    case 'add_supplier':
        echo json_encode($app->addSupplier(
            $_POST['name'] ?? '',
            $_POST['materials'] ?? '',
            $_POST['contact'] ?? '',
            $_POST['email'] ?? '',
            $_POST['contact_person'] ?? '',
            $_POST['address'] ?? '',
            $_POST['material_category'] ?? '',
            $_POST['project_id'] ?? '',
            $_POST['bom_id'] ?? '',
            $_POST['inventory_item_id'] ?? '',
            $_POST['price_quote'] ?? 0,
            $_POST['payment_terms'] ?? '',
            $_POST['status'] ?? 'Active',
            $_POST['remarks'] ?? ''
        ));
        break;

    case 'update_supplier':
        echo json_encode($app->updateSupplier(
            $_POST['id'] ?? 0,
            $_POST['name'] ?? '',
            $_POST['materials'] ?? '',
            $_POST['contact'] ?? '',
            $_POST['email'] ?? '',
            $_POST['contact_person'] ?? '',
            $_POST['address'] ?? '',
            $_POST['material_category'] ?? '',
            $_POST['project_id'] ?? '',
            $_POST['bom_id'] ?? '',
            $_POST['inventory_item_id'] ?? '',
            $_POST['price_quote'] ?? 0,
            $_POST['payment_terms'] ?? '',
            $_POST['status'] ?? 'Active',
            $_POST['remarks'] ?? ''
        ));
        break;

    case 'delete_supplier':
        echo json_encode($app->deleteSupplier($_POST['id'] ?? 0));
        break;

    case 'search_suppliers':
        echo json_encode($app->searchSuppliers($_POST['query'] ?? ''));
        break;

    case 'get_inventory_categories':
        echo json_encode($app->getInventoryCategories());
        break;

    case 'add_inventory_category':
        echo json_encode($app->addInventoryCategory($_POST['name'] ?? ''));
        break;

    case 'get_inventory':
        echo json_encode($app->getInventory());
        break;

    case 'add_inventory':
        echo json_encode($app->addInventory(
            $_POST['name'] ?? '',
            $_POST['category'] ?? '',
            $_POST['qty'] ?? 0,
            $_POST['unit'] ?? '',
            $_POST['cost'] ?? 0,
            $_POST['supplier'] ?? ''
        ));
        break;

    case 'issue_material':
        echo json_encode($app->issueMaterial(
            $_POST['project_id'] ?? '',
            $_POST['item_id'] ?? '',
            $_POST['qty'] ?? 0,
            $_POST['receiver'] ?? ''
        ));
        break;

    // =========================
    // MANPOWER
    // =========================
    case 'get_active_manpower':
        echo json_encode($app->getUsers());
        break;

    case 'get_manpower_skills':
        echo json_encode($app->getManpowerSkills());
        break;

    case 'get_manpower_by_skill':
        echo json_encode($app->getManpowerBySkill($_POST['skill'] ?? ''));
        break;

    case 'get_manpower_by_foreman':
        echo json_encode($app->getManpowerByForeman($_POST['foreman'] ?? ''));
        break;

    case 'get_foremen_list':
        echo json_encode($app->getForemenList());
        break;

    case 'get_unassigned_workers':
        echo json_encode($app->getUnassignedWorkers());
        break;

    case 'get_all_foreman_names':
        echo json_encode($app->getAllForemanNames());
        break;

    case 'search_manpower':
        echo json_encode($app->searchManpower($_POST['query'] ?? ''));
        break;

    case 'add_manpower':
        echo json_encode($app->addManpower(
            $_POST['name'] ?? '',
            $_POST['skills'] ?? '',
            $_POST['position'] ?? '',
            $_POST['salary'] ?? 0,
            $_POST['project_id'] ?? '',
            $_FILES['photo'] ?? null,
            $_POST['foreman'] ?? '',
            $_POST['contact_number'] ?? '',
            $_POST['address'] ?? '',
            $_POST['status'] ?? 'Active',
            $_POST['project_site_text'] ?? ''
        ));
        break;

    case 'update_manpower':
        echo json_encode($app->updateManpower(
            $_POST['id'] ?? '',
            $_POST['name'] ?? '',
            $_POST['skills'] ?? '',
            $_POST['position'] ?? '',
            $_POST['salary'] ?? 0,
            $_POST['project_id'] ?? '',
            $_POST['foreman'] ?? '',
            $_POST['contact_number'] ?? '',
            $_POST['address'] ?? '',
            $_POST['status'] ?? 'Active',
            $_FILES['photo'] ?? null,
            $_POST['project_site_text'] ?? ''
        ));
        break;

    case 'bulk_add_manpower':
        echo json_encode($app->bulkAddManpower($_POST['items'] ?? '[]'));
        break;

    case 'update_bio_data':
        echo json_encode($app->updateBioData($_POST['worker_id'] ?? '', $_FILES['photo'] ?? null));
        break;

    // =========================
    // MANPOWER FOLDERS
    // =========================
    case 'add_skill_category':
        echo json_encode($app->addSkillCategory($_POST['name'] ?? ''));
        break;

    case 'edit_skill_category':
        echo json_encode($app->editSkillCategory($_POST['old_name'] ?? '', $_POST['new_name'] ?? ''));
        break;

    case 'delete_skill_category':
        echo json_encode($app->deleteSkillCategory($_POST['name'] ?? ''));
        break;

    // =========================
    // ARCHIVED MANPOWER
    // =========================
    case 'get_archived_manpower':
        echo json_encode($app->getArchivedManpower());
        break;

    case 'archive_manpower':
        echo json_encode($app->archiveManpower($_POST['id'] ?? ''));
        break;

    case 'restore_manpower':
        echo json_encode($app->restoreManpower($_POST['id'] ?? ''));
        break;

    // =========================
    // AWARD COSTS
    // =========================
    case 'get_award_costs':
        echo json_encode($app->getAwardCosts());
        break;

    case 'get_award_cost':
        echo json_encode($app->getAwardCostById($_POST['id'] ?? ''));
        break;

    case 'add_award_cost':
        echo json_encode($app->addAwardCost(
            $_POST['project_id'] ?? '',
            $_POST['service_agreement_code'] ?? '',
            $_POST['block_no'] ?? '',
            $_POST['lot_no'] ?? '',
            $_POST['location'] ?? '',
            $_POST['item'] ?? '',
            $_POST['unit'] ?? '',
            $_POST['start_date'] ?? '',
            $_POST['completion_date'] ?? '',
            $_POST['work_description'] ?? '',
            $_POST['project_description'] ?? '',
            $_POST['total_amount'] ?? 0,
            $_FILES['attachment'] ?? null
        ));
        break;

    case 'edit_award_cost':
        echo json_encode($app->updateAwardCost(
            $_POST['id'] ?? '',
            $_POST['project_id'] ?? '',
            $_POST['service_agreement_code'] ?? '',
            $_POST['block_no'] ?? '',
            $_POST['lot_no'] ?? '',
            $_POST['location'] ?? '',
            $_POST['item'] ?? '',
            $_POST['unit'] ?? '',
            $_POST['start_date'] ?? '',
            $_POST['completion_date'] ?? '',
            $_POST['work_description'] ?? '',
            $_POST['project_description'] ?? '',
            $_POST['total_amount'] ?? 0,
            $_FILES['attachment'] ?? null
        ));
        break;

    case 'delete_award_cost':
        echo json_encode($app->deleteAwardCost($_POST['id'] ?? ''));
        break;

    case 'search_award_costs':
        echo json_encode($app->searchAwardCosts($_POST['query'] ?? ''));
        break;

    // =========================
    // BILL OF MATERIALS (BOM)
    // =========================
    case 'get_bom_items':
        echo json_encode($app->getBOMItems($_POST['project_id'] ?? ''));
        break;
    case 'get_bom_item':
        echo json_encode($app->getBOMItemById($_POST['id'] ?? ''));
        break;
    case 'add_bom_item':
        echo json_encode($app->addBOMItem(
            $_POST['project_id'] ?? '',
            $_POST['award_cost_id'] ?? '',
            $_POST['material_name'] ?? '',
            $_POST['description'] ?? '',
            $_POST['quantity'] ?? 0,
            $_POST['unit'] ?? '',
            $_POST['unit_cost'] ?? 0,
            $_POST['supplier_name'] ?? '',
            $_POST['remarks'] ?? '',
            $_POST['award_cost_text'] ?? ''
        ));
        break;
    case 'edit_bom_item':
        echo json_encode($app->updateBOMItem(
            $_POST['id'] ?? '',
            $_POST['project_id'] ?? '',
            $_POST['award_cost_id'] ?? '',
            $_POST['material_name'] ?? '',
            $_POST['description'] ?? '',
            $_POST['quantity'] ?? 0,
            $_POST['unit'] ?? '',
            $_POST['unit_cost'] ?? 0,
            $_POST['supplier_name'] ?? '',
            $_POST['remarks'] ?? '',
            $_POST['award_cost_text'] ?? ''
        ));
        break;
    case 'delete_bom_item':
        echo json_encode($app->deleteBOMItem($_POST['id'] ?? ''));
        break;
    case 'search_bom_items':
        echo json_encode($app->searchBOMItems($_POST['query'] ?? ''));
        break;
    case 'get_award_costs_for_bom':
        echo json_encode($app->getAwardCostsForBOM($_POST['project_id'] ?? ''));
        break;

    // =========================
    // BILLING PROGRESS
    // =========================
    case 'get_billing_records':
        echo json_encode($app->getBillingRecords($_POST['project_id'] ?? ''));
        break;
    case 'get_billing_record':
        echo json_encode($app->getBillingRecordById($_POST['id'] ?? ''));
        break;
    case 'get_billing_summary':
        echo json_encode($app->getBillingSummary($_POST['project_id'] ?? ''));
        break;
    case 'get_award_costs_for_billing':
        echo json_encode($app->getAwardCostsForBilling($_POST['project_id'] ?? ''));
        break;
    case 'add_billing_record':
        echo json_encode($app->addBillingRecord(
            $_POST['project_id'] ?? '',
            $_POST['award_cost_id'] ?? '',
            $_POST['billing_date'] ?? '',
            $_POST['billing_reference_no'] ?? '',
            $_POST['billing_description'] ?? '',
            $_POST['amount_billed'] ?? 0,
            $_POST['amount_collected'] ?? 0,
            $_POST['payment_method'] ?? '',
            $_POST['remarks'] ?? '',
            $_POST['status'] ?? 'Pending'
        ));
        break;
    case 'edit_billing_record':
        echo json_encode($app->updateBillingRecord(
            $_POST['id'] ?? '',
            $_POST['project_id'] ?? '',
            $_POST['award_cost_id'] ?? '',
            $_POST['billing_date'] ?? '',
            $_POST['billing_reference_no'] ?? '',
            $_POST['billing_description'] ?? '',
            $_POST['amount_billed'] ?? 0,
            $_POST['amount_collected'] ?? 0,
            $_POST['payment_method'] ?? '',
            $_POST['remarks'] ?? '',
            $_POST['status'] ?? 'Pending'
        ));
        break;
    case 'delete_billing_record':
        echo json_encode($app->deleteBillingRecord($_POST['id'] ?? ''));
        break;
    case 'search_billing_records':
        echo json_encode($app->searchBillingRecords($_POST['query'] ?? ''));
        break;

    // =========================
    // PAYROLL
    // =========================
    case 'get_all_completed_tasks':
        echo json_encode($app->getAllCompletedTasks());
        break;

    case 'get_payroll':
        echo json_encode($app->getPayroll());
        break;

    case 'add_payroll':
        echo json_encode($app->addPayroll(
            $_POST['date'] ?? '',
            $_POST['name'] ?? '',
            $_POST['job_desc'] ?? '',
            $_POST['award'] ?? 0,
            $_POST['advance'] ?? 0
        ));
        break;

    case 'edit_payroll_entry':
        echo json_encode($app->editPayrollEntry(
            $_POST['id'] ?? '',
            $_POST['award_cost'] ?? 0,
            $_POST['cash_advance'] ?? 0
        ));
        break;

    case 'delete_payroll_entry':
        echo json_encode($app->deletePayrollEntry($_POST['id'] ?? ''));
        break;

    case 'archive_and_reset_payroll':
        echo json_encode($app->archiveAndResetPayroll());
        break;

    case 'get_payroll_history':
        echo json_encode($app->getPayrollHistory());
        break;

    // =========================
    // PAYROLL ENTRIES (Manpower & Subcon)
    // =========================
    case 'get_payroll_entry_records':
        echo json_encode($app->getPayrollEntryRecords($_POST['project_id'] ?? ''));
        break;

    case 'get_payroll_entry_record':
        echo json_encode($app->getPayrollEntryRecordById($_POST['id'] ?? ''));
        break;

    case 'get_payroll_entry_record_summary':
        echo json_encode(['status' => 'success', 'data' => $app->getPayrollEntryRecordSummary($_POST['project_id'] ?? '')]);
        break;

    case 'add_payroll_entry_record':
        echo json_encode($app->addPayrollEntryRecord(
            $_POST['project_id'] ?? '',
            $_POST['worker_id'] ?? '',
            $_POST['foreman'] ?? '',
            $_POST['payroll_type'] ?? '',
            $_POST['payee_name'] ?? '',
            $_POST['position_or_role'] ?? '',
            $_POST['skill'] ?? '',
            $_POST['period_start'] ?? '',
            $_POST['period_end'] ?? '',
            $_POST['daily_rate'] ?? 0,
            $_POST['days_worked'] ?? 0,
            $_POST['overtime_hours'] ?? 0,
            $_POST['overtime_rate'] ?? 0,
            $_POST['deductions'] ?? 0,
            $_POST['payment_method'] ?? '',
            $_POST['payroll_status'] ?? 'Pending',
            $_POST['subcon_company'] ?? '',
            $_POST['subcon_scope'] ?? '',
            $_POST['subcon_reference_no'] ?? '',
            $_POST['remarks'] ?? '',
            $_POST['amount'] ?? 0
        ));
        break;

    case 'update_payroll_entry_record':
        echo json_encode($app->updatePayrollEntryRecord(
            $_POST['id'] ?? '',
            $_POST['project_id'] ?? '',
            $_POST['worker_id'] ?? '',
            $_POST['foreman'] ?? '',
            $_POST['payroll_type'] ?? '',
            $_POST['payee_name'] ?? '',
            $_POST['position_or_role'] ?? '',
            $_POST['skill'] ?? '',
            $_POST['period_start'] ?? '',
            $_POST['period_end'] ?? '',
            $_POST['daily_rate'] ?? 0,
            $_POST['days_worked'] ?? 0,
            $_POST['overtime_hours'] ?? 0,
            $_POST['overtime_rate'] ?? 0,
            $_POST['deductions'] ?? 0,
            $_POST['payment_method'] ?? '',
            $_POST['payroll_status'] ?? 'Pending',
            $_POST['subcon_company'] ?? '',
            $_POST['subcon_scope'] ?? '',
            $_POST['subcon_reference_no'] ?? '',
            $_POST['remarks'] ?? '',
            $_POST['amount'] ?? 0
        ));
        break;

    case 'delete_payroll_entry_record':
        echo json_encode($app->deletePayrollEntryRecord($_POST['id'] ?? ''));
        break;

    case 'search_payroll_entry_records':
        echo json_encode($app->searchPayrollEntryRecords($_POST['query'] ?? ''));
        break;

    // =========================
    // CASH RELEASE (Simple Cash Log)
    // =========================
    case 'get_cash_releases':
        echo json_encode($app->getCashReleases());
        break;

    case 'get_cash_release':
        echo json_encode($app->getCashReleaseById($_POST['id'] ?? ''));
        break;

    case 'get_cash_release_category_totals':
        echo json_encode(['status' => 'success', 'data' => $app->getCashReleaseCategoryTotals()]);
        break;

    case 'get_cash_release_summary':
        echo json_encode(['status' => 'success', 'data' => $app->getCashReleaseSummary($_POST['project_id'] ?? '')]);
        break;

    case 'get_award_costs_for_cash_release':
        $result = $app->getAwardCostsForCashRelease($_POST['project_id'] ?? '');
        echo json_encode(['status' => 'success', 'data' => $result]);
        break;

    case 'add_cash_release':
        echo json_encode($app->addCashRelease(
            $_POST['release_date'] ?? '',
            $_POST['category'] ?? '',
            $_POST['released_to'] ?? '',
            $_POST['release_description'] ?? '',
            $_POST['release_amount'] ?? 0
        ));
        break;

    case 'update_cash_release':
        echo json_encode($app->updateCashRelease(
            $_POST['id'] ?? '',
            $_POST['release_date'] ?? '',
            $_POST['category'] ?? '',
            $_POST['released_to'] ?? '',
            $_POST['release_description'] ?? '',
            $_POST['release_amount'] ?? 0
        ));
        break;

    case 'delete_cash_release':
        echo json_encode($app->deleteCashRelease($_POST['id'] ?? ''));
        break;

    case 'search_cash_releases':
        echo json_encode($app->searchCashReleases($_POST['query'] ?? ''));
        break;

    // =========================
    // NTP
    // =========================
    case 'get_all_ntps':
        echo json_encode($app->getAllNTPs());
        break;

    case 'upload_ntp_file':
        echo json_encode($app->uploadNTPFile(
            $_POST['project_id'] ?? '',
            $_POST['ticket'] ?? '',
            $_POST['date'] ?? '',
            $_POST['award_cost'] ?? 0,
            $_POST['due_date'] ?? '',
            $_POST['accept_date'] ?? '',
            $_FILES['file'] ?? null,
            $_POST['completion_date'] ?? null,
            $_POST['work_description'] ?? null,
            $_POST['project_description'] ?? null,
            $_POST['total_amount'] ?? 0
        ));
        break;

    // =========================
    // BULK ADD ALL
    // =========================
    case 'bulk_add_all':
        echo json_encode($app->bulkAddAll(
            $_POST['module'] ?? '',
            $_POST['items'] ?? '[]'
        ));
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
        break;
}
