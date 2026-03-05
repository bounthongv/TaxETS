# Profit Tax

## Tax Expenditure (TE) Calculation Rules

This document outlines the systematic flow used by the Tax-ETS engine to calculate Profit Tax Expenditures. This logic is implemented in the `TEEngine` class.

### 1. Systematic Flow

| Step | Action | Logic Details |
|:-----|:-------|:--------------|
| **Step 1** | **Check Accounting System** | Identify if the company is a **VAT Holder** (Accounting System Holder) or a **Non-VAT Holder**. |
| **Step 2** | **Determine Calculation Path** | **Path A: Non-VAT Holder**<br>Automatically classified as a **Mandatory Base** company.<br><br>**Path B: VAT Holder**<br>Classified by **Annual Turnover** into **SME/Micro** or **Standard/Large Enterprise**. |
| **Step 3** | **Calculate Benchmark PT** | **For Path A (Mandatory):**<br>`Estimated Profit = Revenue × Profit Base Rate`<br>`Benchmark PT = Estimated Profit × Standard PT Rate`<br><br>**For Path B (SME/Micro):**<br>`Benchmark PT = Revenue × SME Rate`<br><br>**For Path B (Standard):**<br>`Benchmark PT = (Net Profit + Re-invested Profit) × Standard PT Rate` |
| **Step 4** | **Calculate Tax Expenditure (TE)** | `PT TE = Benchmark PT − Actual PT Paid` |
| **Step 5** | **Classify by Law Provisions** | Evaluate the company data against the 20 rule-based provisions (ITL/IPL) to identify specific tax incentives applied. |

---

### 2. Key Terms

*   **Profit Base Rate**: The percentage (per sector) used by the tax authority to estimate the taxable profit from total revenue for mandatory/lump-sum payers.
*   **Standard PT Rate**: The statutory tax rate (e.g., 20% for Standard, 24% for Tobacco/Mining) applied to the estimated or actual profit.
*   **VAT Holder**: Companies that maintain a formal accounting system and are registered for VAT.
*   **SME Rate**: Simplified tiered rates for small businesses based on annual turnover thresholds.

---
---
### 3. Benchmark Rates Summary (Profit Tax)

The following tables are based on the official benchmark reference (`1a-Benchmark by year_Profit Tax.pdf`).

#### 3.1 Standard PT Rate (Step 3)
Calculated on **Estimated Profit** (for Non-VAT) or **Net Profit/Re-invested** (for VAT).

| Period | Standard Rate | Special (Tobacco/Mining) |
| :--- | :--- | :--- |
| **2018–2019** | 24% | 26% |
| **2020–2021** | 20% | 22% |
| **2022–2099** | 20% | 22% |

---

#### 3.2 Profit Base Rates (Step 3 - Non-VAT Holders Only)
Used to estimate profit from total annual revenue.

| Sector | Sub-Sector | 2018-2019 | 2020-2021 | 2022-2099 |
| :--- | :--- | :--- | :--- | :--- |
| **Production** | Agri & Industrial | 3% | **5%** | **5%** |
| **Commerce** | General | 5% | **3%** | **3%** |
| **Service** | Transport (Goods/Pax) | 5% | 5% | 5% |
| **Service** | Construction & Repairs | 10% | 10% | 10% |
| **Service** | Wood/Minerals (Trading) | 20% | 20% | 20% |
| **Service** | Planted Trees (Trading) | 5% | 5% | 5% |
| **Service** | Extraction (Soil/Sand/Rock) | 15% | 15% | 15% |
| **Service** | Real Estate | - | - | 10% |
| **Service** | Entertainment | 25% | 25% | 25% |

> [!IMPORTANT]
> Note that **Production** and **Commerce** rates swapped roles starting in 2020.

---

#### 3.3 SME / Micro Rates (VAT Holders < Benchmark)
Calculated directly on annual revenue if the company is a VAT holder but falls below the benchmark threshold.

##### Period: 2018–2019
| Annual Turnover Band | Production | Commerce | Service |
| :--- | :--- | :--- | :--- |
| **50M – 120M** | 3% | 4% | 5% |
| **120M – 240M** | 4% | 5% | 6% |

##### Period: 2020–2099
| Annual Turnover Band | Flat Rate (All Sectors) |
| :--- | :--- |
| **50M – 400M** | 1% |
| **400M – 1.2B** | 2% |
| **1.2B – 4.0B** | 3% |

---

## Technical Note for Administrators
- The rules are stored in `bm_profit_standard`, `bm_profit_mandatory`, and `bm_profit_sme` tables.
- The engine automatically selects the correct rate based on the **Year** of the tax data being evaluated.
- To add new rates for future years, use the **Benchmark Rates** menu in the Config & Rules section.

*Date: 2026-03-04*
