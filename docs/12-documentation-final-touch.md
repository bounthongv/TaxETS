# Final Touches: 540 Projects, Documentation, & Security Requirements

**Date:** 2026-06-03
**Status:** Plan — not yet started
**Prerequisite:** Hybrid testing (docs/11-hybrid-testing.md) should be completed first so the pipeline is verified before the large import.

---

## 1. Import 540 UNDP Projects

### 1.1 Background
The donor (UNDP) needs to see TE results and reports for approximately 540 companies/projects. This is the primary deliverable that justifies the system. The data likely spans multiple tax types and years.

### 1.2 Pre-Import Checklist
- [ ] Confirm all bugs found during hybrid testing (Phase 4 of `11-hybrid-testing.md`) are fixed
- [ ] Confirm the expert TE column and comparison workflow are working correctly
- [ ] Confirm benchmark rates and provisions are fully populated for all relevant years
- [ ] Ensure MySQL settings can handle the volume:
  ```sql
  -- Check max_allowed_packet (should be at least 64M for large Excel files)
  SHOW VARIABLES LIKE 'max_allowed_packet';
  -- Check memory limits
  SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
  ```

### 1.3 Data Inventory
- [ ] Identify all Excel files covering the 540 projects
  - Which tax types are represented?
  - What years do they cover?
  - Which companies are included? (cross-reference with existing company list)
- [ ] Check for duplicates across files (same company appearing in CIT + VAT files)
- [ ] Check for companies that appear in multiple years (panel data)
- [ ] Determine the canonical list of 540 companies

### 1.4 Import Strategy
Since 540 projects is a large batch, do NOT import everything at once:

```
Phase A: Pilot batch (~20 projects)
  -> Import, calculate, compare with expert TE
  -> Verify all pipelines work at scale
  -> Fix any issues

Phase B: Bulk batches (100 projects each, 5 batches)
  -> Each batch: import, calculate, log discrepancies
  -> Monitor PHP memory limit, execution time, DB locks

Phase C: Remaining projects + outliers
  -> Catch any files that didn't fit the pattern
```

### 1.5 PHP/Server Considerations
- [ ] Set `max_execution_time = 300` (5 min) or use chunked processing in import scripts
- [ ] Set `memory_limit = 256M` or higher (large Excel files consume memory)
- [ ] Test with a known large file first — measure import time + memory usage
- [ ] If a single import page times out, consider splitting the Excel file
- [ ] Consider adding a progress indicator for the import UI (large batches are nerve-racking without feedback)

### 1.6 Post-Import Validation
| Check | Method |
|-------|--------|
| Row count matches | Compare Excel rows vs `raw_*` rows vs `te_*_result` rows |
| No orphan companies | Every company in results has a matching record in `companies` table |
| Year coverage | All expected years are present |
| Provision coverage | Every result row has a non-null provision_id |
| Expert TE present | Every result row has expert_te populated (or explicitly NULL with reason) |
| Total TE aggregation | Sum of TE per tax type is reasonable (not missing by order of magnitude) |

### 1.7 UNDP Reporting
- [ ] Prepare a consolidated report across all 540 projects:
  - Total TE by tax type (CIT, VAT, PIT, etc.)
  - Total TE by year
  - Total TE by sector / province / zone
  - Top 10 companies by TE received
  - Provision-wise breakdown (which legal provisions account for most TE)
- [ ] Generate export files (Excel/CSV) for UNDP
- [ ] Create a dashboard view that shows these aggregates at a glance
- [ ] Prepare a brief narrative explaining methodology:
  * Which modules calculate vs import expert TE
  * How benchmark rates were determined
  * What confidence level applies to each module
- [ ] (If time allows) Translate key summary into Lao

### 1.8 Timeline Estimate
```
Pilot batch (20)      1–2 days
Bulk batches (5x100)  3–5 days
Validation + fixes    2–3 days
UNDP report prep      2–3 days
Total:                ~8–13 days
```

---

## 2. System Documentation & User Manual

### 2.1 Audience
| Document | Audience | Format |
|----------|----------|--------|
| **Technical Documentation** | Developers maintaining the system | Markdown in `docs/` |
| **Administrator Manual** | System admin who manages users, imports, benchmarks | PDF / printable HTML |
| **User Manual** | Daily operators (importing data, running calc, viewing reports) | PDF / printable HTML |
| **UNDP / Donor Brief** | Non-technical stakeholders | PDF (PPT summary) |

### 2.2 Technical Documentation (Developer-Facing)
- [ ] **Architecture overview** — already in `docs/10-summary-hybrid-implementation.md` — review and finalize
- [ ] **Database schema reference** — generate from actual schema with table descriptions
  - Can use `mysqldump --no-data --routines` as base, annotate key tables
- [ ] **Engine class reference** — for each of the 10 engine classes:
  - What it does
  - Which benchmark/provision tables it reads
  - Which result table it writes to
  - Key formulas (in plain math, not just code)
- [ ] **Import page reference** — per import page:
  - Expected Excel column layout
  - Smart mapping behavior
  - Error handling logic
- [ ] **Report page reference** — what each report shows and how filters work
- [ ] **Deployment guide**:
  - Server requirements (PHP version, extensions, MySQL version)
  - Installation steps (copy files, create DB, run schema, seed data, configure config.php)
  - First-run setup (create admin user, load benchmark data)
  - Backup/restore procedure
- [ ] **Maintenance guide**:
  - How to add a new benchmark rate
  - How to add a new provision
  - How to add a new data dictionary entry
  - How to troubleshoot common import errors

### 2.3 User Manual (Operator-Facing)
- [ ] **Getting Started**
  - Logging in
  - Dashboard overview
  - Navigation guide
- [ ] **Data Dictionary Management**
  - How to add/view provinces, sectors, districts, zones
  - Why this matters (smart mapping)
- [ ] **Importing Data**
  - Step-by-step: select file, upload, check logs
  - Understanding import diagnostics (warnings, errors, skips)
  - How to fix common import issues
- [ ] **Setting Up Provisions**
  - How to create/modify provisions
  - How provisions affect calculation
- [ ] **Running Calculations**
  - When and why to run calculations
  - How to verify results
- [ ] **Viewing Reports**
  - Per-tax-type reports
  - Consolidated reports
  - Exporting to Excel
- [ ] **Manual Data Entry**
  - When to use it (no Excel file available)
  - How to enter data manually
- [ ] **Troubleshooting FAQ**
  - "Import succeeded but 0 rows imported"
  - "Calculation shows zero TE for all companies"
  - "Report is empty"

### 2.4 UNDP / Donor Brief
- [ ] One-page executive summary of the system
- [ ] Methodology overview (plain language)
- [ ] Key findings from 540 projects
- [ ] Data quality and confidence assessment
- [ ] Recommendations for future data collection

### 2.5 Documentation Tools & Format
| Tool | Use |
|------|-----|
| Markdown | All developer docs in `docs/` |
| PHP Markdown / Doxygen | Auto-generate class reference from PHPDoc comments |
| Pandoc | Convert Markdown to PDF for user/ admin manuals |
| Draw.io / Mermaid | Architecture diagrams (in markdown as Mermaid code blocks) |

### 2.6 Timeline Estimate
```
Review existing docs       1 day
Technical docs writing     3–5 days
User manual writing        3–5 days
Admin manual writing       2–3 days
UNDP brief                 1–2 days
Review + polish            2–3 days
Total:                     ~12–20 days
```

---

## 3. Security Requirements (UNDP Request — Non-Urgent)

### 3.1 Sensitive Data Encryption
UNDP requires encryption of personally identifiable information (PII) and commercially sensitive data stored in the system.

**Scope:** Determine what data needs encryption:

| Data Type | Sensitivity | Recommendation |
|-----------|-------------|----------------|
| Company name | Low-Medium | Not encrypted (needed for reports) |
| Tax ID / VAT number | Medium-High | Encrypt at rest |
| Owner/Director names | High (PII) | Encrypt at rest |
| Shareholder information | High | Encrypt at rest |
| Address / contact info | Medium (PII) | Encrypt at rest |
| Financial figures (revenue, profit) | Medium | Not encrypted (needed for calc) |
| Login credentials | Critical | Already hashed (password_hash) |
| Session tokens | Critical | Already handled by PHP sessions |

**Implementation Options (from simplest to most thorough):**

#### Option A: Column-Level Encryption (Recommended for now)
- Use PHP's `openssl_encrypt()` / `openssl_decrypt()` with AES-256-GCM
- Store encryption key in a separate config file NOT in the repo (`config.encryption.php` excluded via `.gitignore`)
- Encrypt on INSERT, decrypt on SELECT (for authorized users only)
- Affected tables: `companies` (sensitive columns), `data_dictionary` (if it contains PII)
- Pro: Minimal schema changes, targeted protection
- Con: Cannot use encrypted columns in WHERE/ORDER BY (unless deterministic encryption)

```php
// Example helper
function encryptField(string $plaintext): string {
    $key = hex2bin(ENCRYPTION_KEY);
    $iv = random_bytes(12); // GCM uses 12-byte IV
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $ciphertext);
}

function decryptField(string $stored): string {
    $key = hex2bin(ENCRYPTION_KEY);
    $data = base64_decode($stored);
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    return openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
}
```

#### Option B: Full Database Encryption (Future)
- MySQL Enterprise TDE (not available in community edition)
- Third-party: MariaDB Data-at-Rest Encryption (if using MariaDB)
- Encrypted filesystem (LUKS/dm-crypt on Linux, BitLocker on Windows server)
- Pro: Transparent, no code changes
- Con: More infrastructure, overkill for this project scale

#### Option C: Application-Level Encrypted Fields (Alternative)
- Store encrypted data as JSON blob in a single `encrypted_data` TEXT column
- Easier schema management
- Harder to query or debug

**Plan:**
- [ ] Identify specific columns in each table that need encryption
- [ ] Add `ENCRYPTION_KEY` to a new `config.encryption.php` (gitignored)
- [ ] Create `includes/encryption_helper.php` with `encryptField()` / `decryptField()` functions
- [ ] Modify relevant CRUD pages to encrypt on save, decrypt on display (admin only)
- [ ] Add a one-time migration script to encrypt existing data in-place
- [ ] Document key management procedure (who holds the key, backup of key)

### 3.2 Multi-Factor Authentication (MFA)

#### Requirements
- UNDP requires more than a password to access the system
- Typical approach: TOTP (Time-based One-Time Password) via authenticator app (Google Authenticator, Authy, etc.)

#### Implementation Options

#### Option A: TOTP (Recommended)
- Use `spomky-labs/otphp` Composer library or a standalone PHP TOTP implementation
- Admin user generates a QR code during setup
- User scans with Google Authenticator / Authy
- On login: password + 6-digit TOTP code

**Steps:**
1. [ ] Install TOTP library: `composer require spomky-labs/otphp`
2. [ ] Add to `users` table:
   - `totp_secret VARCHAR(64) DEFAULT NULL` — encrypted secret key
   - `totp_enabled TINYINT(1) DEFAULT 0` — whether MFA is active
   - `backup_codes TEXT DEFAULT NULL` — 8–10 one-time recovery codes (hashed)
3. [ ] Create profile page where admin can enable MFA:
   - Show QR code
   - Verify first TOTP code before enabling
4. [ ] Modify login flow:
   - After password verification, if `totp_enabled`, show TOTP input
   - Verify against secret
   - Allow backup codes as fallback
5. [ ] Add `remember_mfa` cookie (optional, reduces friction for trusted devices)

#### Option B: Email OTP (Simpler)
- On login with correct password, send a 6-digit code to the user's registered email
- User enters the code to complete login
- Pro: No app required, familiar flow
- Con: Requires working email on the server, slower login

#### Option C: Hardware Key / WebAuthn (Advanced)
- Requires HTTPS and modern browser
- Most secure but complex to implement in a non-framework PHP app
- Overkill for current project stage

**Plan:**
- [ ] Decide approach (TOTP recommended)
- [ ] Update schema with MFA columns
- [ ] Implement TOTP setup page (admin profile)
- [ ] Modify `login.php` to support 2-step login
- [ ] Generate and store hashed backup codes
- [ ] Test the full flow
- [ ] Add documentation on how to use MFA

### 3.3 Additional Security Checklist
- [ ] Ensure all pages use HTTPS (server config, not app code)
- [ ] Review existing session management:
  - Session timeout configured?
  - Session regeneration after login?
  - CSRF tokens on all forms?
- [ ] Review existing input validation and output encoding
- [ ] Rate limiting on login page (prevents brute force)
- [ ] Audit log for all sensitive actions (import, calculation, user management, data export) — `audit_log` table already exists? Check schema.
- [ ] `.env` or `config.php` — ensure DB credentials use a restricted DB user (not root)

### 3.4 Security Timeline Estimate
```
Identify sensitive data scope     1 day
Implement encryption              2–3 days
Implement MFA                     2–4 days
Additional security hardening     1–2 days
Testing + documentation           1–2 days
Total:                            ~7–12 days
```

---

## Overall Project Timeline

```
Phase                          Days     Dependency
────────────────────────────────────────────────────
Hybrid testing (Phase 0–6)     5–10     — (already planned in 11-hybrid-testing.md)
Fix bugs found                  2–5      on testing completion
Import 540 UNDP projects        8–13     on bug fixes completion
System doc + user manual       12–20     parallel with import
Security: Encryption + MFA      7–12     can start after import, low urgency

Total estimated:               34–60 days
```

The ranges are wide because they depend heavily on:
1. How clean the expert Excel files are
2. How many bugs surface during hybrid testing
3. Whether encryption/MFA becomes a hard deadline or stays "nice to have"

---

## Deliverables Checklist

```
[  ] 540 projects imported and verified
[  ] Consolidated TE report delivered to UNDP
[  ] Technical documentation complete (docs/)
[  ] User manual complete
[  ] Admin manual complete
[  ] UNDP methodology brief complete
[  ] Sensitive data encryption implemented
[  ] Multi-factor authentication implemented
[  ] All docs saved in docs/ for maintainability
```
