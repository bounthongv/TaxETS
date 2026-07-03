# Profit Tax (CIT) Import Test #1 — TE Calculation Report

**Date:** 2026-06-26
**Data Source:** `docs/CIT Test_Toukta1.xlsx` → `CIT_Import_Template.xlsx`
**Records Imported:** 21 (20 unique companies)
**TE Engine:** `te_profit_tax_engine.php` (TEEngine)
**Result:** 10 records with PT TE, 11 records with zero PT TE

---

## 1. Calculation Methodology

The engine determines **Benchmark PT** (the expected tax under standard rules) and then calculates:

```
PT TE = max(0, Benchmark PT − PT Paid)
```

Two calculation paths exist depending on company type:

### Path A: SME (Revenue between 50M–4B LAK depending on year)

| Year Range | SME Threshold | Rate |
|-----------|--------------|------|
| 2018–2019 | 50M–240M LAK | 3%–6% on revenue |
| 2020–2021 | 50M–4B LAK | 1%–3% on revenue |
| 2022+ | 50M–4B LAK | 1%–3% on revenue |

```
Benchmark PT = Revenue × SME Rate ÷ 100
```

### Path B: Standard / Large Enterprise (Revenue outside SME thresholds)

```
Benchmark PT = (Net Profit + Re-invested Profit) × Standard Rate ÷ 100
```

Standard rates:

| Year | Standard | Tobacco/Mining |
|-----|---------|---------------|
| 2018–2019 | 24% | 26% |
| 2020–2021 | 20% | 22% |
| 2022+ | 20% | 22% |

Path B applies **only when Net Profit ≥ 0**. Loss companies produce zero benchmark.

### Classification Rules

| Condition | Path |
|-----------|------|
| Non-VAT holder (Mandatory) | Revenue × Profit Base Rate × Standard Rate |
| VAT holder + SME revenue | SME Rate × Revenue |
| VAT holder + non-SME revenue + NP ≥ 0 | Standard Rate × (NP + Re-invested Profit) |
| VAT holder + NP < 0 | Zero benchmark |

---

## 2. Results Summary

| Category | Count | Total TE |
|---------|:-----:|:--------:|
| Records WITH PT TE | 10 | 60,584,842 LAK |
| Records with ZERO PT TE | 11 | 0 LAK |
| **Total** | **21** | **60,584,842 LAK** |

---

## 3. Records WITH PT TE (10 records)

All 10 records took **Path B (Standard)** — they are non-SME companies (revenue outside SME thresholds, including both micro-enterprises below 50M and large enterprises above SME max) with positive net profit, and they paid less tax than the standard rate would produce.

| # | Company | Year | Sector | Revenue | Net Profit | Rate | Bench PT | PT Paid | **PT TE** |
|:-:|---------|:----:|--------|--------:|----------:|:----:|---------:|--------:|:--------:|
| 1 | Bank Commerce (909954240-900) | 2018 | Banking | 3,470,000 | 1,370,000 | 24% | 328,800 | 310,000 | **18,800** |
| 2 | Lao Kham Tai Pharmaceutical (765173026-000) | 2019 | Commerce | 12,389,000 | 9,100,200 | 24% | 2,184,048 | 1,500,000 | **684,048** |
| 3 | Namkham Consultancy (508054437-000) | 2018 | Consultancy | 3,460,000 | 1,460,000 | 24% | 350,400 | 150,000 | **200,400** |
| 4 | Houng Heuongtham Law (126321627-000) | 2018 | Consultancy | 378,000,000 | 99,000,000 | 24% | 23,760,000 | 19,000,000 | **4,760,000** |
| 5 | Phouthong Consultancy (822868467-000) | 2018 | Consultancy | 39,000,000 | 11,000,000 | 24% | 2,640,000 | 1,890,000 | **750,000** |
| 6 | K&N Reeco Consultancy (057258273-000) | 2018 | Consultancy | 4,280,799,900 | 520,799,900 | 24% | 124,991,976 | 120,000,000 | **4,991,976** |
| 7 | PHS Law Consultancy (773663486-000) | 2018 | Consultancy | 12,000,000 | 12,000,000 | 24% | 2,880,000 | 1,800,000 | **1,080,000** |
| 8 | Chien Yee Consultancy (210408429-000) | 2021 | Consultancy | 5,380,585,000 | 380,000,000 | 20% | 76,000,000 | 50,000,000 | **26,000,000** |
| 9 | CMC Education Center (588616406-900) | 2019 | Education | 51,591,311,026 | 307,963,522 | 24% | 73,911,245 | 52,000,000 | **21,911,245** |
| 10 | Electricity Generator (232280925-900) | 2018 | Energy | 1,098,415,500 | 55,784,885 | 24% | 13,388,372 | 13,200,000 | **188,372** |

---

## 4. Records with ZERO PT TE (11 records) — Why?

### Category A: Loss Companies — No Tax Due (4 records)

Net Profit < 0 → Benchmark PT = 0 → PT TE = 0.

| Company | Year | Net Profit | Reason |
|---------|:----:|-----------:|--------|
| Laos Thailand Co. (602733805-900) | 2018 | −7,000,000 | Operating loss |
| Maekhong Pluk Ton Mai (379505006-900) | 2019 | −500,000 | Operating loss |
| Trade Co. (729549606-900) | 2018 | −4,000,000 | Operating loss |
| Sida School (087635121-000) | 2018 | −29,590,857,105 | Large operating loss |

### Category B: SME Path — PT Paid Exceeds SME Benchmark (6 records)

These companies qualified as SMEs (revenue within threshold). The SME benchmark (Revenue × 1–5%) is very low. Actual tax paid already covers or exceeds it, so TE = 0.

| Company | Year | Revenue | SME Rate | Bench PT | PT Paid | Gap |
|---------|:----:|--------:|:--------:|---------:|--------:|:---:|
| Tin Ngeuy Lao (2021) | 2021 | 200,000,000 | 1% | 2,000,000 | 2,000,000 | Equal |
| Tin Ngeuy Lao (2020) | 2020 | 128,000,000 | 1% | 1,280,000 | 2,500,000 | Paid more |
| STD Farm (140243135-000) | 2018 | 92,000,000 | 5% | 4,600,000 | 19,500,000 | Paid much more |
| Bank Taiwan (851705381-000) | 2018 | 120,000,000 | 5% | 6,000,000 | 24,000,000 | Paid much more |
| Phatthana Construction (670341668-000) | 2022 | 730,000,000 | 2% | 14,600,000 | 25,000,000 | Paid more |
| Sindah Consultancy (170631442-000) | 2020 | 129,000,000 | 1% | 1,290,000 | 18,900,000 | Paid much more |

These companies are all taxed at standard rates in practice, but the SME benchmark (on revenue at 1–5%) is much lower than what standard-rate taxation (20–24% on profit) produces. Since actual tax paid already exceeds the low SME benchmark, no TE gap exists.

### Category C: Standard Path — PT Paid Exceeds Benchmark (1 record)

| Company | Year | Net Profit | Std Rate | Bench PT | PT Paid | Gap |
|---------|:----:|----------:|:--------:|---------:|--------:|:---:|
| Phomsoupha Consultancy (587445017-000) | 2021 | 1,000,000 | 20% | 200,000 | 220,000 | Paid 20K above |

Revenue was below SME threshold, so the standard path was taken, but the company still paid slightly more tax than the standard rate would produce.

---

## 5. Key Insights for Expert

1. **SME classification is the primary TE determinant.** SME companies benchmark on revenue at 1–6%, producing a very low benchmark. They almost never generate TE because actual tax paid at standard rates nearly always exceeds it.

2. **TE arises only from non-SME companies** (below 50M or above SME max threshold) where PT Paid < Standard Rate × Net Profit.

3. **Loss-making companies naturally produce zero TE** — if there is no profit, there is no tax obligation and therefore no expenditure gap.

4. **The total TE across this test dataset is 60,584,842 LAK**, concentrated in 10 consultancy/service-sector companies where the difference between standard-rate tax and actual PT Paid was largest.

5. **The matched provisions** (shown in the database but not detailed here) identify which specific legal provisions the company qualifies for, enabling per-provision TE attribution in future analysis.
