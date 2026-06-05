# ASYCUDA Import Follow-Up

Date: 2026-06-05

Status: Pending expert confirmation.

## Decision So Far

Do not recommend or redesign the ASYCUDA Excel template. `docs/Import_data_from_ASYCUDA.xlsx` is a standard ASYCUDA export template used by the Ministry of Finance, so Tax-ETS should adapt to the export format instead of asking users to change it.

## Issue To Confirm

The ASYCUDA workbook includes separate provision columns:

| Column | Header |
| --- | --- |
| AR | `Provision_Customs` |
| AS | `Provision_Excise` |
| AT | `Provision_VAT` |

The current Tax-ETS importer reads only `AR` into `asycuda_imports.provision_customs`. It does not store `Provision_Excise` or `Provision_VAT`.

## Proposed Follow-Up After Expert Confirmation

If the expert confirms that `Provision_Excise` and `Provision_VAT` are needed for reporting/classification:

1. Add `provision_excise` and `provision_vat` columns to `asycuda_imports`.
2. Update `pages/import_asycuda.php` to read:
   - `AS` -> `provision_excise`
   - `AT` -> `provision_vat`
3. Update `pages/view_asycuda.php` and related ASYCUDA TE/report pages if those provisions should be displayed or filtered.
4. Add verification checks using a sample ASYCUDA import file.

