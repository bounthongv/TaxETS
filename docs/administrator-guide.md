# Tax-ETS Administrator Guide

## 1. Purpose

This guide is for system administrators who manage Tax-ETS configuration, users, reference data, imports, backups, and operational troubleshooting.

It is not a developer guide. For code architecture and database details, see:

- `technical-documentation.md`
- `database-blueprint.md`
- `database-data-dictionary.md`

## 2. Administrator Responsibilities

Administrators are responsible for:

- managing user accounts and access,
- maintaining dictionaries and benchmark rates,
- maintaining provision/reference data,
- monitoring imported batches,
- helping users resolve import errors,
- running or supporting backups,
- checking calculation/report readiness,
- coordinating updates with the developer/technical team.

## 3. Main Admin Menu Areas

Administrator functions are mainly located in these menu sections:

| Menu | Purpose |
| --- | --- |
| System | Users, roles, logs, backup/restore, IP access, online users |
| Data Dictionary | Province, district, sector, enterprise type, GDP/revenue, etc. |
| Benchmark | Tax and non-tax benchmark rates |
| Repository | Legal provisions and reference repositories |
| Get Tax Data by Import from Excel | Import TE data and manage batches |
| TE Calculation | Run TE calculation by tax type |
| TE Reports | Review consolidated TE outputs |
| Notification | Concession compliance and notification management |

## 4. User Management

Navigation:

```text
System > User Management
```

Use this page to:

- create users,
- update user information,
- assign roles,
- activate or deactivate accounts,
- manage user photos or profile information where available.

Recommended practice:

- Use individual named accounts, not shared accounts.
- Deactivate users who no longer need access.
- Assign the lowest role that allows the user to perform their work.
- Keep at least one active administrator account.

## 5. Role Management

Navigation:

```text
System > Role Management
```

Roles control access levels. The system commonly includes:

| Role | Typical Purpose |
| --- | --- |
| SUPER ADMIN | Full system access |
| ADMIN | Administrative operation access |
| USER | Normal data entry/review access |

Recommended practice:

- Limit SUPER ADMIN access.
- Review role permissions before adding new users.
- Do not remove the last administrator role/user.

## 6. Operation Logs

Navigation:

```text
System > Operation Logs
```

Use operation logs to review user activities and support audit checks.

Typical use cases:

- checking who changed system data,
- reviewing login/activity history,
- investigating unexpected changes,
- supporting operational accountability.

## 7. IP Access Management

Navigation:

```text
System > IP access Management
```

Use this area to manage allowed or restricted IP access where configured.

Recommended practice:

- Keep rules simple and documented.
- Test access after changes.
- Avoid locking out all administrator users.

## 8. Online User Management

Navigation:

```text
System > User Online Management
```

Use this page to monitor active sessions.

Operational uses:

- confirm who is currently using the system,
- identify stale sessions,
- support maintenance windows.

## 9. Backup and Restore

Navigation:

```text
System > Backup/Restore Data
```

Backups are stored in:

```text
backups/
```

Recommended backup practice:

- Take a backup before major imports, benchmark changes, schema changes, or application updates.
- Keep dated backup files.
- Store copies outside the application folder when possible.
- Test restore procedures in a non-production environment before relying on them.

Before restore:

1. Confirm the selected backup file.
2. Notify users to stop working.
3. Take a current backup first.
4. Restore.
5. Verify login, imports, calculations, and reports.

## 10. Data Dictionary Maintenance

Navigation:

```text
Data Dictionary
```

Dictionary data supports standardization and import validation.

Common dictionary areas:

- Province
- District
- Investment Zone
- Village
- Enterprise Type
- Business Sector
- MOIC Categories
- GDP, Revenue
- Enterprise/Project Status

Recommended practice:

- Avoid duplicate province/district/sector names.
- Use consistent spelling.
- Do not delete dictionary records if historical imported data depends on them.
- Prefer deactivation where available instead of deletion.

## 11. GDP and Revenue Maintenance

Navigation:

```text
Data Dictionary > GDP, Revenue
```

GDP and revenue values are used as denominators in reports:

- TE by Tax Type (% of GDP)
- TE by Tax Type (% of Revenue)

Important:

- GDP/revenue values are reference values.
- Import-date filters in reports filter TE data only, not GDP/revenue reference values.

Recommended practice:

- Confirm units before entry/import.
- Keep one official value per year unless the system explicitly supports versions.
- Add notes for source and revision where possible.

## 12. Benchmark Maintenance

Navigation:

```text
Benchmark
```

Benchmark tables provide standard rates used by TE calculation engines.

Benchmark areas include:

- Corporate Income Tax
- Individual Income Tax
- Value-Added Tax
- Customs Duty
- Excise Tax
- MSME Definition
- Customs Regime Codes
- Payment Condition Code
- Non-Tax: Land Concession
- Non-Tax: Natural Resource
- Activities in Art 9 of IPL

Recommended practice:

- Confirm legal effective dates before updating rates.
- Keep start/end years or start/end dates accurate.
- Do not overwrite historical rates that are needed for past-year calculations.
- After benchmark changes, rerun affected TE calculations if reports must reflect the new benchmark.

## 13. Repository and Provision Maintenance

Navigation:

```text
Repository
```

Repository/provision data defines legal provisions and classification rules.

Areas include:

- Corporate Income Tax provisions
- Individual Income Tax provisions
- Value-Added Tax provisions
- Customs Duty provisions
- Excise Tax provisions
- SEZ Developer/Investor provisions
- Non-Tax provisions

Recommended practice:

- Keep provision numbers aligned with legal references.
- Avoid changing provision numbers after data has been calculated, unless you also review affected reports.
- Update descriptions and purpose fields for clarity.
- For CIT condition rules, verify that condition field names match actual company table fields.

## 14. Import Template Management

Templates are stored in:

```text
docs/
```

Import pages provide template download links for users.

Recommended practice:

- Keep one official template per tax type.
- Do not rename templates without updating the corresponding import page.
- When changing template columns, coordinate with the developer because import code may rely on fixed column positions or names.
- Keep old templates archived if they are needed to understand historical imports.

## 15. Batch Management

Navigation:

```text
Get Tax Data by Import from Excel
  > Data Requirement to estimate TE
    > Batch Management
```

The Batch Management page provides a central view of imported TE estimation batches.

Administrators can:

- search batch IDs,
- filter by tax type,
- view imported records,
- open TE calculation pages,
- download diagnostic logs,
- delete batches.

Manual-entry data also appears here as batches. Each time a user starts manual entry from an import page, the system creates a new timestamped manual batch, for example:

```text
MANUAL_ENTRY_VAT_2026_20260604153022
```

Records added during that same manual-entry session remain in the same batch. If the user starts manual entry again later, the system creates a new batch.

Important:

- Deleting a batch removes imported records for that batch.
- Some TE result tables are deleted through cascade or explicit delete logic.
- PIT results are matched by TIN/year, so PIT batch cleanup should be handled carefully.

Recommended practice:

- Download the diagnostic log before deleting a problematic batch.
- Confirm the batch ID and tax type before deletion.
- Take a backup before deleting large or important batches.
- For manual-entry batches, confirm the timestamp as well as the tax type/year before deletion.

## 16. Import Error Logs

Import logs are stored in:

```text
data/logs/{batch_id}.log
```

Users can download logs from import pages or Batch Management when available.

Common import issues:

| Issue | Likely Cause |
| --- | --- |
| Missing required field | Template incomplete |
| Unknown province/district | Template value does not match dictionary |
| Invalid date | Wrong Excel date format or text date |
| Invalid number | Text or formatted number in numeric column |
| Unexpected columns | Wrong template version |

Land Concession template notes:

- Current generated columns are `TIN, CompanyName, District, Province, TaxItem, Year, Receiptdate, Concessionarea, BenchmarkRate, ContractedRate, ConcessionFeePaid, ProvisionName`.
- `TaxItem` is accepted for compatibility but not stored separately yet.
- If `Year` is blank, the import page's selected tax year is used.
- `Benchmark Value` and `Non-Tax TE` are calculated by the system, not imported.
- If `ProvisionName` is blank, Land Concession TE remains `0` until policy/provision treatment is confirmed.

Administrator response:

1. Download and review the log.
2. Check whether the user used the correct template.
3. Verify dictionary values.
4. Correct the Excel file or dictionary data.
5. Re-import as a new batch.

## 17. TE Calculation Administration

Navigation:

```text
TE Calculation
```

Calculation pages are organized by tax type:

- Profit Tax TE
- Individual Tax TE
- Salary Tax TE
- Domestic VAT TE
- ASYCUDA: Customs Duty TE, Excise Tax TE, Import VAT TE
- SEZ Developer TE
- SEZ Investor TE
- Non-Tax: Land concession TE, Resource fee TE, Royalty fee TE

Recommended practice:

- Review imported records before calculation.
- Run calculation after import and after benchmark/provision changes.
- Check calculation summaries and Expert TE comparisons where available.
- If discrepancies are expected, document them.

## 18. Expert TE Visibility

Expert TE is used for verification.

Current intended behavior:

- hidden from ordinary imported-data view pages,
- visible on TE calculation pages where needed,
- restricted to admin verification where implemented.

Administrator responsibility:

- Do not expose Expert TE fields to ordinary users unless approved.
- Use Expert TE for validation and discussion with experts.
- Document known discrepancies separately.

## 19. Report Administration

Navigation:

```text
TE Reports
```

Reports include:

- TE by Tax Type
- TE by Sector
- TE by Location
- TE by Tax Type (% of GDP)
- TE by Tax Type (% of Revenue)
- TE by Provision
- Customs regime/payment condition reports

Common filters:

- From Year
- To Year
- Import From
- Import To

Important:

- Year filters control the reporting year.
- Import-date filters control which imported/input batches are included.
- GDP/revenue denominator values remain static reference values.

Recommended practice:

- Confirm calculations have been run before relying on reports.
- Use date filters when reviewing a specific import period.
- Export Excel reports for official review.

## 20. Troubleshooting Checklist

### User cannot log in

Check:

- user exists,
- user is active,
- role is assigned,
- IP access rule does not block the user,
- password reset/change is needed.

### Import failed

Check:

- correct template,
- required columns,
- province/district dictionary values,
- date and number formats,
- diagnostic log.

### Batch is missing

Check:

- correct tax type,
- Batch Management filter/search,
- import completed successfully,
- batch was not deleted,
- table uses `batch_id` or `import_batch_id`.

### Report shows no data

Check:

- TE calculation was run,
- year filter range,
- import-date filter range,
- benchmark/provision configuration,
- source batch exists.

### SQL collation error

Likely caused by comparing text columns with different collations.

Escalate to developer. Known sensitive fields include TIN/PTIN and provision numbers.

### Wrong TE value

Check:

- correct benchmark rate,
- correct provision matching,
- correct imported data,
- calculation was rerun after changes,
- Expert TE discrepancy notes.

## 21. Change Management

Before major changes:

1. Take a backup.
2. Record what will be changed.
3. Apply changes during low usage.
4. Test import, calculation, and reports.
5. Document the result.

Major changes include:

- benchmark updates,
- provision updates,
- template changes,
- schema/database changes,
- application code updates,
- bulk data deletion.

## 22. Recommended Admin Routine

Daily or active-use period:

- Check import errors/logs.
- Confirm users can access required pages.
- Review any failed calculations.

Weekly:

- Take or verify backup.
- Review batch list.
- Review user activity if required.

Before official reporting:

- Confirm benchmark/provision data.
- Confirm all required batches are imported.
- Run/re-run TE calculations.
- Review reports with filters.
- Export official report outputs.

## 23. Escalation to Developer

Escalate when:

- database schema changes are needed,
- import template structure changes,
- calculation formulas must change,
- SQL errors appear,
- reports show inconsistent totals,
- role/permission logic needs changes,
- batch cleanup affects multiple tables unexpectedly.

When escalating, provide:

- screenshot or exact error message,
- page URL,
- batch ID,
- tax type,
- import log if relevant,
- steps to reproduce.
