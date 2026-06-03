# Domestic VAT Test Result Notes

## Purpose

This note explains the Domestic VAT test result for discussion with the tax expert. The first sections are the discussion summary; the row-by-row output is kept at the end as a reference appendix.

## Summary For Discussion

| Item | Value |
|---|---:|
| Test file | `docs/Domestic VAT Test.xlsx` |
| Batch | `VAT_BATCH_20260603104127` |
| Records | 21 taxpayers |
| System TE | 99,025,992,565.89 LAK |
| Expert TE | 99,025,992,565.89 LAK |
| Difference, System - Expert | 0.00 LAK |
| Rows with TE discrepancy | 0 |

Domestic VAT matches the expert file exactly. Both methods value exempt domestic sales at the applicable VAT benchmark rate.

```text
System: exempt domestic sales x bm_vat rate
Expert: column I x column H / 100
```

## How The System Calculates Domestic VAT TE

```text
Benchmark output VAT = total sales x VAT benchmark rate
Calculated VAT payable = max(0, benchmark output VAT - input VAT)
Domestic VAT TE        = max(0, calculated VAT payable - VAT payable)
```

For this test file, only exempt domestic sales are present:

| Field | Test data value |
|---|---:|
| Standard-rated sales | 0 |
| Zero-rated sales | 0 |
| Exempt domestic sales | Imported from column I |
| Input VAT | 0 |
| VAT payable | 0 |

So the system formula simplifies to:

```text
Domestic VAT TE = exempt domestic sales x VAT benchmark rate
```

Benchmark rates come from `bm_vat`:

| Period | Rate |
|---|---:|
| 2010-01-01 to 2021-12-31 | 10% |
| 2022-01-01 to 2024-03-31 | 7% |
| 2024-04-01 onward | 10% |

## How The Expert Excel Calculates Domestic VAT TE

The expert template calculates TE directly in column J:

```text
Expert TE = Domestic sale exemption x Rate / 100
```

| Column | Meaning |
|---|---|
| H | VAT rate |
| I | Domestic sale exemption |
| J | Expert TE |

This matches the system formula because the test file has no input VAT and no VAT payable.

## Why There Is No Discrepancy

All 21 rows match because both methods apply the same rates to the same exempt sale base:

| Case | Result |
|---|---|
| 2019-2021 rows | 10% used by both system and expert |
| 2022-2023 rows | 7% used by both system and expert |

## Data Quality Notes

1. The Province column in the expert file contains `Department`, which is not a province name. The importer keeps this value and flags it as an unknown province in the import warning/log. This affects location classification only; it does not affect VAT TE.
2. Column J is imported as `expert_te` for admin-only comparison on the TE calculation page.
3. Column K is imported as the provision number when populated. In the current test file it is blank for all rows, so the TE is not classified by VAT provision.

## Points To Confirm With Expert

1. Should Domestic VAT TE for exempt sales always be valued as exempt sales x benchmark VAT rate?
2. Should input VAT be deducted when future templates include input VAT values?
3. Should the template replace `Department` with real province names for location reporting?
4. Which VAT provision number should be assigned to these exempt domestic sales?

## Recommended System Position

For Domestic VAT tax expenditure reporting, use:

```text
Domestic VAT TE = max(0, calculated VAT payable - VAT payable)
```

For simple exempt-sale templates with no input VAT and no VAT payable, this becomes:

```text
Domestic VAT TE = exempt domestic sales x benchmark VAT rate
```

Expert TE should remain a verification reference visible to `admin@example.com` only, while normal users should see only the system TE.

## Row-By-Row Reference

| # | TIN | Period | Exempt sales | BM output VAT | System TE | Expert TE | Difference |
|---:|---|---|---:|---:|---:|---:|---:|
| 1 | 880782489-900 | 2020-10 | 1,617,992,605 | 161,799,261 | 161,799,261 | 161,799,261 | 0 |
| 2 | 580761167-900 | 2021-06 | 1,871,120,832 | 187,112,083 | 187,112,083 | 187,112,083 | 0 |
| 3 | 543163802-900 | 2021-05 | 10,008,773,452 | 1,000,877,345 | 1,000,877,345 | 1,000,877,345 | 0 |
| 4 | 543163802-900 | 2021-04 | 11,461,800,111 | 1,146,180,011 | 1,146,180,011 | 1,146,180,011 | 0 |
| 5 | 543163802-900 | 2022-11 | 11,531,862,833 | 807,230,398 | 807,230,398 | 807,230,398 | 0 |
| 6 | 543163802-900 | 2022-01 | 11,570,907,479 | 809,963,524 | 809,963,524 | 809,963,524 | 0 |
| 7 | 580761167-900 | 2020-11 | 11,714,798,952 | 1,171,479,895 | 1,171,479,895 | 1,171,479,895 | 0 |
| 8 | 543163802-900 | 2019-05 | 11,920,668,318 | 1,192,066,832 | 1,192,066,832 | 1,192,066,832 | 0 |
| 9 | 543163802-900 | 2022-09 | 11,952,547,735 | 836,678,341 | 836,678,341 | 836,678,341 | 0 |
| 10 | 580761167-900 | 2021-11 | 113,822,746,584 | 11,382,274,658 | 11,382,274,658 | 11,382,274,658 | 0 |
| 11 | 583429006-900 | 2023-09 | 115,260,000 | 8,068,200 | 8,068,200 | 8,068,200 | 0 |
| 12 | 580761167-900 | 2021-04 | 116,249,562,000 | 11,624,956,200 | 11,624,956,200 | 11,624,956,200 | 0 |
| 13 | 580761167-900 | 2021-03 | 118,514,774,504 | 11,851,477,450 | 11,851,477,450 | 11,851,477,450 | 0 |
| 14 | 543163802-900 | 2022-10 | 12,703,316,686 | 889,232,168 | 889,232,168 | 889,232,168 | 0 |
| 15 | 580761167-900 | 2022-04 | 123,683,640,000 | 8,657,854,800 | 8,657,854,800 | 8,657,854,800 | 0 |
| 16 | 456977486-900 | 2023-02 | 129,360,522,819 | 9,055,236,597 | 9,055,236,597 | 9,055,236,597 | 0 |
| 17 | 543163802-900 | 2020-08 | 13,275,586,415 | 1,327,558,642 | 1,327,558,642 | 1,327,558,642 | 0 |
| 18 | 456977486-900 | 2022-12 | 134,454,752,727 | 9,411,832,691 | 9,411,832,691 | 9,411,832,691 | 0 |
| 19 | 543163802-900 | 2019-06 | 15,216,874,449 | 1,521,687,445 | 1,521,687,445 | 1,521,687,445 | 0 |
| 20 | 580761167-900 | 2021-10 | 150,805,831,780 | 15,080,583,178 | 15,080,583,178 | 15,080,583,178 | 0 |
| 21 | 456977486-900 | 2022-11 | 155,740,612,088 | 10,901,842,846 | 10,901,842,846 | 10,901,842,846 | 0 |
