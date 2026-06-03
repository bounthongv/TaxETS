# CIT Test Result Notes

## Purpose

This note explains the Profit Tax (CIT) test result for discussion with the tax expert. The first sections are the discussion summary; the row-by-row output is kept at the end as a reference appendix.

## Summary For Discussion

| Item | Value |
|---|---:|
| Test file | `docs/CIT Test_Toukta1.xlsx` |
| Batch | `BATCH_20260603062354` |
| Records | 21 companies |
| System TE | 60,584,842 LAK |
| Expert TE | 122,164,842 LAK |
| Difference, System - Expert | -61,580,000 LAK |
| Rows with TE discrepancy | 5 |

The main issue is methodology for SME companies. The system currently uses the SME revenue-based rate as the benchmark for VAT-holder SME companies, while the expert file uses the normal profit-tax rate on profit as the benchmark.

```text
System: SME benchmark = revenue x SME rate
Expert: benchmark = profit x standard profit-tax rate
```

For tax expenditure reporting, the expert method is usually easier to defend because preferential SME rates are measured against the normal tax system.

## How The System Calculates CIT TE

```text
CIT TE = max(0, benchmark profit tax - profit tax paid)
```

The benchmark path depends on the company type:

| Case | System benchmark method |
|---|---|
| Non-VAT / mandatory taxpayer | Revenue x mandatory profit-base rate x standard PT rate |
| VAT holder, SME tier found | Revenue x SME rate |
| VAT holder, no SME tier | (Net profit + reinvested profit) x standard PT rate |

Provision matching is recorded for explanation/classification, but it does not change the CIT TE amount.

## How The Expert Excel Calculates CIT TE

```text
Expert benchmark PT = max(0, net profit + reinvested profit) x standard PT rate
Expert TE           = max(0, expert benchmark PT - profit tax paid)
```

Standard rates used in the template:

| Year | Standard PT rate |
|---|---:|
| 2018-2019 | 24% |
| 2020 onward | 20% |

The expert template does not apply the SME revenue-based rate in the benchmark.

## Key Discrepancy Rows

| ID | Year | Sector | Main reason | System TE | Expert TE | Difference |
|---:|---:|---|---|---:|---:|---:|
| 484 | 2021 | Agriculture | SME 1% on revenue vs 20% on profit | 0 | 22,000,000 | -22,000,000 |
| 485 | 2020 | Agriculture | SME 1% on revenue vs 20% on profit | 0 | 5,300,000 | -5,300,000 |
| 488 | 2018 | Agriculture | SME 5% on revenue vs 24% on profit | 0 | 780,000 | -780,000 |
| 493 | 2022 | Construction | SME 2% on revenue vs 20% on profit | 0 | 32,400,000 | -32,400,000 |
| 495 | 2020 | Consultancy | SME 1% on revenue vs 20% on profit | 0 | 1,100,000 | -1,100,000 |

Largest difference: company ID 493. The system benchmark is `730M x 2% = 14.6M`, which is below PT paid of 25M, so system TE is 0. The expert benchmark is `(187M + 100M) x 20% = 57.4M`, so expert TE is 32.4M.

## Points To Confirm With Expert

1. Should SME preferential rates be treated as tax expenditure?
2. If yes, should VAT-holder SME companies use the standard profit-tax rate on taxable profit as the benchmark?
3. Should SME status be kept as a matched provision/explanation, not as the benchmark formula?
4. For non-VAT mandatory taxpayers, should the benchmark remain revenue x profit-base rate x standard rate?

## Recommended System Position

For CIT tax expenditure reporting, use:

```text
Benchmark PT = max(0, net profit + reinvested profit) x standard PT rate
CIT TE       = max(0, benchmark PT - PT paid)
```

Then record SME status and other preferences as matched provisions for explanation. This aligns the result with the expert template and normal TE methodology.

## Data Note

Excel values expressed in billion LAK are multiplied by 1,000,000,000 during import for total assets and annual turnover. This affects provision matching, not the CIT TE amount.

## Row-By-Row Reference

| ID | Year | Sector | Revenue | Net profit | PT paid | System TE | Expert TE | Difference |
|---:|---:|---|---:|---:|---:|---:|---:|---:|
| 484 | 2021 | Agriculture | 200,000,000 | 120,000,000 | 2,000,000 | 0 | 22,000,000 | -22,000,000 |
| 485 | 2020 | Agriculture | 128,000,000 | 39,000,000 | 2,500,000 | 0 | 5,300,000 | -5,300,000 |
| 486 | 2018 | Agriculture | 45,000,000 | -7,000,000 | 0 | 0 | 0 | 0 |
| 487 | 2019 | Agriculture | 0 | -500,000 | 0 | 0 | 0 | 0 |
| 488 | 2018 | Agriculture | 92,000,000 | 84,500,000 | 19,500,000 | 0 | 780,000 | -780,000 |
| 489 | 2018 | Banking | 120,000,000 | 92,140,000 | 24,000,000 | 0 | 0 | 0 |
| 490 | 2018 | Banking | 3,470,000 | 1,370,000 | 310,000 | 18,800 | 18,800 | 0 |
| 491 | 2018 | Commerce | 0 | -4,000,000 | 0 | 0 | 0 | 0 |
| 492 | 2019 | Commerce | 12,389,000 | 9,100,200 | 1,500,000 | 684,048 | 684,048 | 0 |
| 493 | 2022 | Construction | 730,000,000 | 187,000,000 | 25,000,000 | 0 | 32,400,000 | -32,400,000 |
| 494 | 2018 | Consultancy | 3,460,000 | 1,460,000 | 150,000 | 200,400 | 200,400 | 0 |
| 495 | 2020 | Consultancy | 129,000,000 | 100,000,000 | 18,900,000 | 0 | 1,100,000 | -1,100,000 |
| 496 | 2018 | Consultancy | 378,000,000 | 99,000,000 | 19,000,000 | 4,760,000 | 4,760,000 | 0 |
| 497 | 2021 | Consultancy | 4,000,000 | 1,000,000 | 220,000 | 0 | 0 | 0 |
| 498 | 2018 | Consultancy | 39,000,000 | 11,000,000 | 1,890,000 | 750,000 | 750,000 | 0 |
| 499 | 2018 | Consultancy | 4,280,799,900 | 520,799,900 | 120,000,000 | 4,991,976 | 4,991,976 | 0 |
| 500 | 2018 | Consultancy | 12,000,000 | 12,000,000 | 1,800,000 | 1,080,000 | 1,080,000 | 0 |
| 501 | 2021 | Consultancy | 5,380,585,000 | 380,000,000 | 50,000,000 | 26,000,000 | 26,000,000 | 0 |
| 502 | 2019 | Education | 51,591,311,026 | 307,963,522 | 52,000,000 | 21,911,245 | 21,911,245 | 0 |
| 503 | 2018 | Education | 114,504,323,391 | -29,590,857,105 | 0 | 0 | 0 | 0 |
| 504 | 2018 | Energy | 1,098,415,500 | 55,784,885 | 13,200,000 | 188,372 | 188,372 | 0 |
