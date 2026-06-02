<?php
/**
 * Concession Milestone Checker
 * Scans for approaching deadlines and triggers notifications.
 */

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/notification_helper.php";

function scanConcessionMilestones() {
    try {
        $pdo = getDbConnection();
        
        // Find milestones that are:
        // 1. Active
        // 2. Within the remind_days threshold (or already overdue)
        // 3. Haven't been notified today (to avoid spam)
        
        $sql = "SELECT * FROM concession_milestones 
                WHERE status = 'Active' 
                AND CURDATE() >= DATE_SUB(end_date, INTERVAL remind_days DAY)
                AND (last_notified_at IS NULL OR DATE(last_notified_at) < CURDATE())";
        
        $stmt = $pdo->query($sql);
        $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $triggered_count = 0;
        
        foreach ($milestones as $m) {
            $days_left = (strtotime($m['end_date']) - time()) / (60 * 60 * 24);
            $days_left = ceil($days_left);
            
            $status_msg = ($days_left < 0) ? "OVERDUE by " . abs($days_left) . " days" : "due in $days_left days";
            
            $contents = "COMPLIANCE ALERT: The {$m['milestone_type']} period for project '{$m['project_name']}' is $status_msg (Deadline: {$m['end_date']}). ";
            $contents .= "Responsible: {$m['responsible_person']}. Action: Request progress report from {$m['company_name']} (TIN: {$m['tin']}).";
            
            // Trigger Notification
            $success = createNotification('Concession Compliance', $m['tin'], $contents, $m['contact_email']);
            
            if ($success) {
                // Update last notified date
                $pdo->prepare("UPDATE concession_milestones SET last_notified_at = NOW() WHERE id = ?")
                    ->execute([$m['id']]);
                $triggered_count++;
            }
        }
        
        return $triggered_count;
        
    } catch (Exception $e) {
        error_log("Milestone Scan Error: " . $e->getMessage());
        return 0;
    }
}
