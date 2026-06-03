# PIT Test Result Notes

## Purpose

This note explains the Personal Income Tax (PIT) test result for discussion with the tax expert. The first sections are the discussion summary; the row-by-row output is kept at the end as a reference appendix.

## Summary For Discussion

| Item | Value |
|---|---:|
| Test file | `docs/PIT TE Test_Toukta.xlsx` |
| Batch | `BATCH_20260603094908` |
| Records | 11 individuals |
| System TE | 37,310,000 LAK |
| Expert TE | 64,060,000 LAK |
| Difference, System - Expert | -26,750,000 LAK |
| Rows with TE discrepancy | 4 |

The largest issue is provision treatment. The expert template values each exemption/deduction directly by provision amount and applicable rate. The system reconstructs benchmark PIT and currently estimates actual tax paid.

## How The System Calculates PIT TE

The current engine groups income into two categories:

| Category | Provisions |
|---|---|
| Employment income | 21, 22, 24, 25, 29 |
| Other income | 23_1, 23_2, 26, 27, 28_1, 28_2 |

Then it calculates:

```text
Employment benchmark tax = progressive PIT on total employment income
Other benchmark tax      = sum(other income amount x flat rate)
Total benchmark tax      = employment benchmark + other benchmark
Actual tax paid          = max(0, total provision income x 10% - expert TE)
PIT TE                   = max(0, total benchmark tax - actual tax paid)
```

The actual-tax-paid value is not imported directly. The engine currently estimates it using a 10% assumption and the expert TE value.

## How The Expert Excel Calculates PIT TE

The expert template calculates TE directly by provision:

```text
Expert TE = sum(provision amount x expert rate)
```

Example treatments:

| Provision | Expert treatment |
|---|---|
| 21 Overtime | 15% |
| 22 Uniform/equipment | 20% |
| 23_1 Spouse allowance | marginal PIT rate, 25% in the test case |
| 23_2 Child allowance | marginal PIT rate, 5% in the test case |
| 24 Government allowance | 5% |
| 25 Student allowance | 0% |
| 26 Shares | 10% |
| 27 Dividends | 10% |
| 28_1 Deposit interest | 10% if bank deposit, otherwise 0% |
| 28_2 Bond interest | 10% |
| 29 Performance bonus | 5% |

## Main Reasons For Discrepancy

1. The current `bm_pit_employment` table has progressive brackets for 2020 onward. For 2019 records, the employment benchmark can become 0 or incomplete.
2. Provisions `23_1` and `23_2` are currently mapped to rental income at 10%, but the expert treats them as personal allowances/deductions using marginal PIT rates.
3. Actual tax paid is estimated from total provision income and Expert TE, so the system is not fully independent from the expert reference.

## Key Discrepancy Rows

| PTIN | Name | Main reason | System TE | Expert TE | Difference |
|---|---|---|---:|---:|---:|
| 12345678 | A | 2019 employment brackets missing; actual tax estimated | 9,750,000 | 14,250,000 | -4,500,000 |
| 12345683 | F | 2019 employment brackets missing; 28_1 bank-deposit condition differs | 4,000,000 | 8,000,000 | -4,000,000 |
| 12345686 | I | Spouse allowance treated as rental 10%, expert uses 25% marginal rate | 12,000,000 | 30,000,000 | -18,000,000 |
| 12345687 | j | Actual-tax estimate exceeds benchmark; expert uses direct 5% rate | 0 | 250,000 | -250,000 |

Largest difference: individual I, PTIN 12345686. The system treats the 120M spouse allowance as rental income at 10%, giving 12M. The expert treats it as a personal allowance at a 25% marginal rate, giving 30M.

## Points To Confirm With Expert

1. For PIT TE reporting, should each exemption/deduction be valued directly by provision amount x applicable marginal or withholding rate?
2. Should provisions `23_1` and `23_2` be treated as personal allowances using marginal PIT rate, not rental income?
3. What progressive PIT brackets should be used for 2019 and earlier years?
4. Should the import template include actual tax paid, so the system does not need to derive it from Expert TE?
5. For provision `28_1`, should TE be 10% only when the income is eligible bank deposit interest and 0 otherwise?

## Recommended System Position

For PIT, refine the current engine before final reporting:

1. Add historical PIT brackets for 2019 and any other test years.
2. Reclassify provisions `23_1` and `23_2` as personal allowances/deductions valued at marginal PIT rate.
3. Add/import actual tax paid if available.
4. Apply provision-specific eligibility conditions, especially for deposit interest.

Expert TE should remain a verification reference visible to `admin@example.com` only, while normal users should see only the system TE.

## Row-By-Row Reference

| PTIN | Name | Year | Benchmark tax | Actual tax paid | System TE | Expert TE | Difference | Matched provisions |
|---|---|---:|---:|---:|---:|---:|---:|---|
| 12345678 | A | 2019 | 10,000,000 | 250,000 | 9,750,000 | 14,250,000 | -4,500,000 | 21, 26, 29, 30 |
| 12345679 | B | 2019 | 0 | 0 | 0 | 0 | 0 | - |
| 12345680 | C | 2020 | 3,000,000 | 0 | 3,000,000 | 3,000,000 | 0 | 27 |
| 12345681 | D | 2021 | 3,000,000 | 0 | 3,000,000 | 3,000,000 | 0 | 27 |
| 12345682 | E | 2023 | 5,000,000 | 0 | 5,000,000 | 5,000,000 | 0 | 28, 28.1 |
| 12345683 | F | 2019 | 5,000,000 | 1,000,000 | 4,000,000 | 8,000,000 | -4,000,000 | 22, 28, 28.1 |
| 12345684 | G | 2019 | 500,000 | 0 | 500,000 | 500,000 | 0 | 28.2 |
| 12345685 | H | 2019 | 120,000 | 60,000 | 60,000 | 60,000 | 0 | 23.2 |
| 12345686 | I | 2020 | 12,000,000 | 0 | 12,000,000 | 30,000,000 | -18,000,000 | 23, 23.1 |
| 12345687 | j | 2023 | 185,000 | 250,000 | 0 | 250,000 | -250,000 | 24 |
| 12345688 | k | 2022 | 0 | 75,000 | 0 | 0 | 0 | 25 |
