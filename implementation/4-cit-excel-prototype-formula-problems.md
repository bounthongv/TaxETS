# CIT Excel Prototype Formula - Insufficiencies

Last Updated: 2026-03-04

## Overview

The Excel file (`docs/CIT Test_Toukta.xlsx`) contains a simplified prototype formula for calculating Corporate Income Tax (CIT) Benchmark PT and Tax Expenditure. This document outlines the insufficiencies and differences between the Excel prototype and the actual tax rules.

---

## Excel Prototype Formulas

### Column P - Benchmark Rate
```excel
=IF(A2=2022,0.2,IF(J2="Mining",0.35,(IF(A2<2020,0.24,0.2))))
```

### Column Q - Benchmark PT
```excel
=IF(AND(M2>=0,N2>=0),(M2+N2)*P2,"")
```

### Column R - PT TE
```excel
=IF(AND(M2>=0,O2<=Q2),Q2-O2,"")
```

---

## Insufficiencies

### 1. Ignores VAT Holder Status

**Excel**: Assumes all companies are VAT holders
**Reality**: Must check column AL (VAT Holder flag)

- **Non-VAT Holder (Mandatory)**: Use mandatory formula
- **VAT Holder**: Use standard or SME formula

### 2. Non-VAT (Mandatory) Calculation Missing

**Excel**: Uses only `(Net Profit + Re-invested) × Rate`

**Should be for Non-VAT**:
```
Estimated Profit = Revenue × Profit Base Rate (by sector)
Benchmark PT = Estimated Profit × Standard Rate
```

### 3. SME Tiers Ignored

**Excel**: Applies flat rate to all VAT holders

**Should be for VAT Holders with small turnover**:
| Annual Turnover | Rate |
|----------------|------|
| 0 - 50M LAK | 0.1% |
| 50M - 100M LAK | 0.5% |
| 100M - 200M LAK | 1% |
| 200M - 400M LAK | 1.5% |
| 400M+ LAK | 2% |

### 4. Profit Base Rates by Sector Missing

**Excel**: Does not implement sector-specific profit base rates

**Should be for Non-VAT holders**:
| Sector | Profit Base Rate |
|--------|------------------|
| Production | 5% |
| Commerce | 3% |
| Services (General) | 15% |
| Services (Restaurant/Hotel) | 10% |
| Services (Transport) | 10% |
| Services (Professional) | 20% |

### 5. Special Sectors Incomplete

**Excel**: Only checks `J2="Mining"`

**Should also check**:
- **Tobacco** - 24%/26% rate
- **Electricity** - 24%/26% rate

### 6. Staff Count Not Considered

**Excel**: No consideration for staff count

**Note**: Staff count is used for **provision classification**, not for SME tier determination (turnover is the key factor).

---

## Comparison Summary

| Aspect | Excel Prototype | Tax-ETS System |
|--------|-----------------|----------------|
| VAT Holder Check | ❌ Ignored | ✅ Implemented |
| Non-VAT Formula | ❌ Missing | ✅ Implemented |
| SME Tiers | ❌ Missing | ✅ Implemented |
| Profit Base Rates | ❌ Missing | ✅ Implemented |
| Sector Rates | ⚠️ Partial | ✅ Complete |
| Provision Matching | ❌ Not implemented | ✅ Configurable |

---

## Conclusion

The Excel prototype is a **simplified calculation** that does not reflect the full complexity of Lao PDR tax rules. The Tax-ETS system correctly implements:

1. VAT holder status determination
2. Non-VAT (Mandatory) calculation path
3. SME tier classification by turnover
4. Sector-specific profit base rates
5. Configurable provision matching

**The Tax-ETS system is the authoritative implementation of the tax rules.**
