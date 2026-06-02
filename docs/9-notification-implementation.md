# Notification Implementation Plan: Automated Early Warning System

## 1. Overview
The Notification Management module will transition from a manual entry system to an **Automated Early Warning System (EWS)**. It will automatically flag significant events, data discrepancies, and high-value tax expenditures across all integrated data sources (MPI, TaxRIS, ASYCUDA, etc.).

## 2. Notification Triggers

### A. MPI (New Investment Alerts)
*   **Event**: Successful import of new project data from MPI.
*   **Trigger**: End of the `import_mpi.php` execution.
*   **Auto-Content**: "PROJECT ALERT: [Batch ID] - [Count] new investment projects imported. Please review Tax Holiday periods and verify benchmark eligibility."

### B. TaxRIS (Revenue & TE Discrepancies)
*   **Event**: Corporate Income Tax (CIT) calculation detects a large gap between Actual and Benchmark tax.
*   **Trigger**: After `te_profit_tax_engine` calculation for a batch.
*   **Auto-Content**: "DISCREPANCY ALERT: TIN [TIN] has a potential Tax Expenditure of [Amount]. Variance exceeds threshold of [X%]."

### C. ASYCUDA (Customs Duty Alerts)
*   **Event**: High-value import declarations utilizing specific exemption codes.
*   **Trigger**: During `import_asycuda.php` or subsequent calculation.
*   **Auto-Content**: "HIGH-VALUE EXEMPTION: Declaration [ID] for [HS Code] resulted in [Amount] TE. Sector: [Sector]."

### D. Concession Compliance (Milestones & Deadlines)
*   **Event**: A project milestone (MOU, Prospecting, Survey, etc.) is approaching its end date or is overdue.
*   **Trigger**: Background check script (manual or CRON).
*   **Auto-Content**: "COMPLIANCE ALERT: [Project Name] - [Milestone Type] period ends on [End Date]. Please request report from [Company Name] / [Responsible Person]."

## 3. Implementation Strategy

### Centralized Helper Function
Create a shared utility `includes/notification_helper.php`:
```php
function createNotification($source, $ref_id, $contents, $emails = '', $phones = '') {
    // Inserts record into `notifications` table with status 'Unsent'
}
```

### Batch Processing Integration
Insert the helper function at the end of each import/calculation loop.

### Background Monitoring Script
Create `includes/check_milestones.php` to perform daily scans:
1.  **Scan**: Query `concession_milestones` where `CURDATE() >= DATE_SUB(end_date, INTERVAL remind_days DAY)`.
2.  **Alert**: If milestone is active and not yet notified, call `createNotification`.

### Hybrid Data Entry Strategy (Concessions)
To streamline manual compliance tracking, the system implements a **Fetch-to-Fill** logic for new milestones:
*   **Data Hierarchy**:
    1.  **MOIC (Primary)**: Fetches official Company Name.
    2.  **MPI (Secondary)**: Fetches official Project Name.
    3.  **Main Directory (Fallback)**: Checks global company registration if repositories are empty.
*   **Implementation**: A dedicated backend service (`api_get_company_info.php`) serves the `repo_milestones.php` interface to reduce typing errors and ensure cross-module data integrity.

## 4. User Workflow
1.  **Automation**: System detects event or approaching deadline and creates notification (Status: Unsent).
2.  **Review**: Admin opens "Notification Management".
3.  **Action**: Admin clicks "Send Email" or "Send SMS" after verifying the content.
4.  **Audit**: Status changes to "Sent" with timestamp.
