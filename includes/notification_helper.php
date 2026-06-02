<?php
/**
 * Notification Helper Utility
 * Part of the Tax-ETS Early Warning System
 */

require_once __DIR__ . "/db.php";

if (!function_exists('createNotification')) {
    /**
     * Creates a new notification entry in the database.
     * 
     * @param string $source   The source system (e.g., 'MPI', 'TaxRIS', 'ASYCUDA')
     * @param string $ref_id   Reference ID like TIN, Batch ID, or Declaration No.
     * @param string $contents The actual message body
     * @param string $emails   Optional comma-separated emails
     * @param string $phones   Optional comma-separated phone numbers
     * @return bool            Success status
     */
    function createNotification($source, $ref_id, $contents, $emails = '', $phones = '') {
        try {
            $pdo = getDbConnection();
            $sql = "INSERT INTO notifications (source, ref_id, contents, notification_date, emails, phones, status) 
                    VALUES (?, ?, ?, NOW(), ?, ?, 'Unsent')";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$source, $ref_id, $contents, $emails, $phones]);
        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }
}
