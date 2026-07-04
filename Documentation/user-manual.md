# ຄູ່ມືຜູ້ໃຊ້ Tax-ETS

## 1. ຈຸດປະສົງຂອງຄູ່ມື

ຄູ່ມືນີ້ອະທິບາຍວິທີໃຊ້ງານລະບົບ Tax-ETS ສໍາລັບຜູ້ໃຊ້ທົ່ວໄປ. ເນື້ອຫາຈະເນັ້ນໃສ່ຂັ້ນຕອນການເຮັດວຽກຫຼັກ ເຊັ່ນ ການນໍາເຂົ້າຂໍ້ມູນ, ການກວດສອບ Batch, ການຄໍານວນ TE, ແລະການເບິ່ງລາຍງານ.

ແນວທາງຂອງຄູ່ມືນີ້ຈະບໍ່ອະທິບາຍຊໍ້າກັນທຸກປະເພດພາສີ, ເພາະຂັ້ນຕອນພື້ນຖານຄ້າຍຄືກັນ. ຈະອະທິບາຍລາຍລະອຽດຄັ້ງດຽວໃນຮູບແບບ Workflow, ແລ້ວສະຫຼຸບຈຸດທີ່ແຕກຕ່າງຂອງແຕ່ລະປະເພດພາສີ.

## 2. ຜູ້ໃຊ້ທີ່ເໝາະສົມ

ຄູ່ມືນີ້ເໝາະສໍາລັບ:

- ຜູ້ນໍາເຂົ້າຂໍ້ມູນຈາກ Excel
- ຜູ້ກວດສອບຂໍ້ມູນ Batch
- ຜູ້ດໍາເນີນການຄໍານວນ Tax Expenditure
- ຜູ້ເບິ່ງ ແລະ Export ລາຍງານ
- ຜູ້ປະສານງານກັບຜູ້ຊ່ຽວຊານດ້ານພາສີ

## 3. ຄໍາສັບສໍາຄັນ

| ຄໍາສັບ | ຄວາມໝາຍ |
| --- | --- |
| TE | Tax Expenditure, ມູນຄ່າລາຍຮັບທີ່ລັດສູນເສຍຈາກມາດຕະການພາສີ |
| Batch | ກຸ່ມຂໍ້ມູນທີ່ນໍາເຂົ້າ ຫຼື ປ້ອນດ້ວຍມືພ້ອມກັນ |
| Import | ການນໍາເຂົ້າຂໍ້ມູນຈາກ Excel |
| Template | ແບບຟອມ Excel ສໍາລັບນໍາເຂົ້າຂໍ້ມູນ |
| Expert TE | ຄ່າ TE ຈາກຜູ້ຊ່ຽວຊານ ຫຼື ແບບຟອມອ້າງອີງ |
| System TE | ຄ່າ TE ທີ່ລະບົບ Tax-ETS ຄໍານວນ |
| Benchmark | ອັດຕາຫຼືຫຼັກການມາດຕະຖານທີ່ໃຊ້ເພື່ອຄໍານວນ |
| Provision | ມາດຕາ ຫຼື ຂໍ້ກໍານົດທາງກົດໝາຍທີ່ກ່ຽວກັບ TE |
| Go to TE Calculation | ປຸ່ມຢູ່ໜ້າ View batch ສໍາລັບການເຂົ້າໄປໜ້າຄໍານວນ |
| Run TE Calculation | ປຸ່ມຢູ່ໜ້າ TE Calculation ທີ່ສັ່ງໃຫ້ລະບົບຄໍານວນ TE ຕົວຈິງ |

## 4. ການເຂົ້າລະບົບ

**System URL:** [https://tax-ets.apis.com.la/](https://tax-ets.apis.com.la/)

1. ເປີດ Browser ແລ້ວເຂົ້າ URL: **https://tax-ets.apis.com.la/**
2. ໃສ່ Email ແລະ Password.
3. ກົດປຸ່ມ Login.
4. ຫຼັງຈາກເຂົ້າລະບົບສໍາເລັດ ລະບົບຈະເຂົ້າສູ່ Dashboard.

### ບັນຊີຜູ້ໃຊ້ສໍາລັບການທົດສອບ

| Email | Password | ສິດ |
| --- | --- | --- |
| apis@example.com | trainer123 | ADMIN (ສໍາລັບຜູ້ທົດສອບ ແລະ ຝຶກອົບຮົມ) |

Screenshot placeholder:

```text
[Insert screenshot: Login page]
```

## 5. ໂຄງສ້າງເມນູຫຼັກ

ຫຼັງຈາກ Login, ຜູ້ໃຊ້ຈະເຫັນ Sidebar ດ້ານຊ້າຍ. ເມນູຫຼັກທີ່ຜູ້ໃຊ້ມັກໃຊ້ມີ:

| ເມນູ | ຈຸດປະສົງ |
| --- | --- |
| Dashboard | ໜ້າສະຫຼຸບພາບລວມ |
| Get Tax Data by Import from Excel | ນໍາເຂົ້າຂໍ້ມູນຈາກ Excel |
| Batch Management | ຈັດການ Batch ທີ່ນໍາເຂົ້າແລ້ວ |
| TE Calculation | ຄໍານວນ Tax Expenditure |
| TE Reports | ເບິ່ງ ແລະ Export ລາຍງານ |

Screenshot placeholder:

```text
[Insert screenshot: Main sidebar navigation]
```

## 6. ຂັ້ນຕອນການເຮັດວຽກຫຼັກ

ການໃຊ້ງານລະບົບສ່ວນໃຫຍ່ຈະເປັນຂັ້ນຕອນດັ່ງນີ້:

1. Download Template.
2. ຕື່ມຂໍ້ມູນໃນ Excel Template.
3. Import Excel ເຂົ້າລະບົບ.
4. ກວດສອບຜົນການ Import ແລະ Download Log ຖ້າມີ Error.
5. ກົດ Go to TE Calculation ເພື່ອເຂົ້າໜ້າຄໍານວນ.
6. ຢູ່ໜ້າ TE Calculation, ກົດ Run TE Calculation ເພື່ອຄໍານວນຕົວຈິງ.
7. ກວດສອບຜົນການຄໍານວນ.
8. ເບິ່ງລາຍງານ ແລະ Export Excel/PDF.

```text
Excel Template -> Import -> View Batch -> Go to TE Calculation -> Run TE Calculation -> Reports
```

**ຫມາຍສໍາຄັນ:** ປຸ່ມ "Go to TE Calculation" ຢູ່ໜ້າ View ແມ່ນສໍາລັບ **ນໍາທາງ** ໄປໜ້າຄໍານວນ. ປຸ່ມ "Run TE Calculation" ຢູ່ໜ້າ TE Calculation ແມ່ນ **ສັ່ງໃຫ້ຄໍານວນ** ຕົວຈິງ.

## 7. ຕົວຢ່າງ Workflow ລະອຽດ: Domestic VAT

ພາກນີ້ໃຊ້ Domestic VAT ເປັນຕົວຢ່າງລະອຽດ. ສໍາລັບປະເພດພາສີອື່ນ ຂັ້ນຕອນຈະຄ້າຍຄືກັນ.

### 7.1 Download Template

1. ໄປທີ່:

```text
Get Tax Data by Import from Excel
  > Data Requirement to estimate TE
    > Domestic VAT
```

2. ກົດ `Download Template`.
3. ບັນທຶກ Excel Template ໄວ້ໃນເຄື່ອງ.

Screenshot placeholder:

```text
[Insert screenshot: Domestic VAT import page and Download Template button]
```

### 7.2 ການກຽມ Excel

ໃຫ້ຕື່ມຂໍ້ມູນໃນ Template ຕາມຮູບແບບທີ່ກໍານົດ.

ຂໍ້ຄວນລະວັງ:

- ຢ່າປ່ຽນຊື່ຄໍລໍາ.
- ຢ່າລຶບ Sheet ຫຼື ຄໍລໍາທີ່ Template ກໍານົດ.
- ກວດສອບ Province/District ໃຫ້ຖືກຕາມ Dictionary.
- ວັນທີຄວນເປັນ Date ທີ່ Excel ອ່ານໄດ້.
- ຈໍານວນເງິນຄວນເປັນຕົວເລກ, ບໍ່ແມ່ນຂໍ້ຄວາມ.

### 7.3 Import Excel

1. ຢູ່ໜ້າ Domestic VAT Import, ເລືອກໄຟລ໌ Excel.
2. ກົດ Import.
3. ລະບົບຈະສ້າງ Batch ID ໃໝ່.
4. ຖ້າ Import ສໍາເລັດ ຈະເຫັນ Batch ໃນລາຍການ Recent Batches.
5. ຖ້າມີ Error, ກົດ Download Log ເພື່ອກວດສອບ.

Screenshot placeholder:

```text
[Insert screenshot: Import file selection and recent VAT batches]
```

### 7.4 ເບິ່ງ Batch ໃນ Batch Management

1. ໄປທີ່:

```text
Get Tax Data by Import from Excel
  > Data Requirement to estimate TE
    > Batch Management
```

2. ໃຊ້ Filter Tax Type ເລືອກ Domestic VAT.
3. ຄົ້ນຫາ Batch ID ຖ້າຈໍາເປັນ.
4. ກົດປຸ່ມ View ເພື່ອເບິ່ງຂໍ້ມູນໃນ Batch.

Screenshot placeholder:

```text
[Insert screenshot: Batch Management filtered by Domestic VAT]
```

### 7.5 ເບິ່ງ ແລະ ແກ້ໄຂຂໍ້ມູນໃນ Batch

ໃນໜ້າ Domestic VAT Records:

- ກວດສອບຈໍານວນ Records.
- ໃຊ້ Search ເພື່ອຫາ TIN ຫຼື ຊື່ຜູ້ເສຍພາສີ.
- ໃຊ້ Filter ເຊັ່ນ Province ຫຼື Period.
- ກົດ Edit ເພື່ອແກ້ໄຂຂໍ້ມູນ.
- ກົດ Add Record to Batch ຖ້າຕ້ອງການເພີ່ມລາຍການ (ທຸກລາຍການທີ່ເພີ່ມຈະຢູ່ໃນ Batch ດຽວກັນ).

Screenshot placeholder:

```text
[Insert screenshot: Domestic VAT records page with Batch Management back button]
```

### 7.6 Go to TE Calculation

1. ຢູ່ໜ້າ Domestic VAT Records, ກົດ **Go to TE Calculation**.
2. ລະບົບຈະນໍາໄປໜ້າ TE Calculation ທີ່ກ່ຽວຂ້ອງ.

Screenshot placeholder:

```text
[Insert screenshot: Domestic VAT TE calculation page - initial state showing Go to TE Calculation button]
```

### 7.7 Run TE Calculation

1. ຢູ່ໜ້າ TE Calculation.
2. ກວດສອບຈໍານວນ Records.
3. ກົດ **Run TE Calculation**.
4. ລໍຖ້າລະບົບຄໍານວນ (ຈະມີ Spinner ແລະ ປຸ່ມຈະຂຽນວ່າ "Processing...").
5. ກວດສອບຜົນສະຫຼຸບ.

Screenshot placeholder:

```text
[Insert screenshot: Run TE Calculation button and calculation results]
```

### 7.8 ການກວດສອບຜົນ

ຫຼັງຈາກຄໍານວນ:

- ກວດສອບ System TE.
- ກວດສອບລາຍການທີ່ບໍ່ມີ Provision ຫຼື ມີຄວາມຜິດປົກກະຕິ.
- ບັນທຶກຂໍ້ສັງເກດສໍາລັບປຶກສາຜູ້ຊ່ຽວຊານ.

## 8. ການໃຊ້ Batch Management

Batch Management ເປັນໜ້າກາງສໍາລັບກວດສອບ Batch ທັງໝົດຂອງ TE estimation.

Navigation:

```text
Get Tax Data by Import from Excel
  > Data Requirement to estimate TE
    > Batch Management
```

ໃນໜ້ານີ້ຜູ້ໃຊ້ສາມາດ:

- ເບິ່ງ Batch ທັງໝົດ.
- Filter ຕາມ Tax Type.
- Search Batch ID.
- ເບິ່ງຈໍານວນ Rows.
- ເບິ່ງ Years ທີ່ຢູ່ໃນ Batch.
- ເບິ່ງ Import Date.
- ເປີດ View.
- ເປີດ TE Calculation.
- Download Log ຖ້າມີ.
- Delete Batch ຖ້າຈໍາເປັນ ແລະ ມີສິດ.

ຂໍ້ຄວນລະວັງ:

- ການ Delete Batch ຈະລຶບຂໍ້ມູນທີ່ນໍາເຂົ້າຂອງ Batch ນັ້ນ.
- ຄວນຢືນຢັນ Batch ID ໃຫ້ຖືກກ່ອນລຶບ.
- ຖ້າ Batch ມີ Error Log, ຄວນ Download Log ໄວ້ກ່ອນລຶບ.

Screenshot placeholder:

```text
[Insert screenshot: Batch Management page]
```

## 9. ຂໍ້ມູນເຉພາະຂອງແຕ່ລະປະເພດພາສີ

ຂັ້ນຕອນ Import, View/Edit, TE Calculation ແລະ Reports ສ່ວນໃຫຍ່ຄ້າຍຄືກັນ. ຕາຕະລາງນີ້ສະຫຼຸບຈຸດສໍາຄັນຂອງແຕ່ລະປະເພດ.

| ປະເພດ | ເມນູ Import | ເມນູ TE Calculation | Template Columns | ຈຸດສັງເກດ |
| --- | --- | --- | --- | --- |
| Profit Tax | Profit Tax | Profit Tax TE | Custom format | ໃຊ້ຂໍ້ມູນບໍລິສັດ, ກໍາໄລ, ອັດຕາ Benchmark ແລະ Provisions |
| Individual Tax (PIT) | Individual Tax | Individual Tax TE | 35 cols (A–AI) | ອີງໃສ່ PTIN/TIN ແລະ Tax Year |
| Domestic VAT | Domestic VAT | Domestic VAT TE | 17 cols (A–Q) | ໃຊ້ຂໍ້ມູນ Filing Period, Sales, Input/Output VAT |
| SEZ Developer | For SEZ Developers | For SEZ Developers TE | 20 cols (A–T) | ໃຊ້ template ດຽວກັນກັບ Investor, ແຕ່ກວດທີ່ Column I = Yes |
| SEZ Investor | For SEZ Investors | For SEZ Investors TE | 20 cols (A–T) | ໃຊ້ template ດຽວກັນກັບ Developer, ແຕ່ກວດທີ່ Column J = Yes |
| Land Concession | Non-Tax: Land concession | Land concession TE | 19 cols (A–S) | ມີ Cascading District (ຂຶ້ນກັບ Province). TE = max(0, benchmark - paid) |
| Resource Fee | Non-Tax: Resource fee | Resource fee TE | 17 cols (A–Q) | ອີງໃສ່ປະເພດຊັບພະຍາກອນ ແລະ Fee collected |
| Royalty Fee | Non-Tax: Royalty fee | Royalty fee TE | 16 cols (A–P) | ອີງໃສ່ມູນຄ່າຂາຍໄຟຟ້າ ແລະ Royalty rate |
| ASYCUDA | Data from ASYCUDA > Import New Data | Customs Duty TE, Excise Tax TE, Import VAT TE | 46 cols (A–AT, standard MOF) | Import ຄັ້ງດຽວ ແຕ່ແຍກໄປ 3 ປະເພດ TE |

### Template Columns Overview

**Domestic VAT (17 columns):** A=Province, B=TIN, C=Company Name, D=Filing Type, E=Filing Period, F=Input Date, G=Description, H=VAT Rate %, I=Domestic Sale Exempt LAK, J=User TE, K=Provision Number, L=User Benchmark Rate, M=User Benchmark VAT, N=Use User Fallback?, O=System Benchmark Rate %, P=User Fallback Reason, Q=User Comment

**SEZ VAT (20 columns):** A=Tax Year, B=TIN, C=Company Name, D=License Date, E=Province (dropdown), F=District (cascading), G=Village, H=SEZ Name, I=SEZ Developer (Yes/No), J=SEZ Investor (Yes/No), K=Sector, L=Basic Infrastructure LAK, M=Other Infrastructure LAK, N=Utility Usage LAK, O=Support Infrastructure LAK, P=Use User Fallback?, Q=User Benchmark Rate, R=User Benchmark Fee, S=User TE, T=User Comment

**Land Concession (19 columns):** A=TIN, B=CompanyName, C=Province (dropdown), D=District (cascading), E=Description (dropdown from concession types), F=Year, G=Receiptdate, H=Concessionarea (ha), I=BenchmarkRate (USD), J=ContractedRate (USD), K=ConcessionFeePaid, L=Paid Currency, M=Exchange Rate, N=Use User Fallback?, O=User Benchmark Rate, P=User Benchmark Value, Q=User Non-Tax TE, R=User Fallback Reason, S=User Comment

**Resource Fee (17 columns):** A=TIN, B=License Date, C=Resource Type (dropdown), D=Year, E=Reciept Date, F=Benchmark Rate, G=Contracted Rate, H=Sale Quantity (Tons), I=Fee Collected, J=Paid Currency, K=Exchange Rate, L=Use User Fallback?, M=User Benchmark Rate, N=User Benchmark Fee, O=User TE, P=User Fallback Reason, Q=User Comment

**Royalty Fee (16 columns):** A=TIN, B=License Date, C=Year, D=Reciept Date, E=Benchmark Rate, F=Contracted Rate, G=Electricity Sale Value (USD), H=Fee Collected, I=Paid Currency, J=Exchange Rate, K=Use User Fallback?, L=User Benchmark Rate, M=User Benchmark Fee, N=User TE, O=User Fallback Reason, P=User Comment

**ASYCUDA (46 columns):** Standard MOF format. Import once via "Import New Data"

## 10. ການ Run TE Calculation

ມີ 2 ຂັ້ນຕອນ:

### ຂັ້ນຕອນທີ 1: Go to TE Calculation
- ຈາກໜ້າ View Batch, ກົດ **Go to TE Calculation** (ປຸ່ມສີຂຽວ).
- ຫຼື ຈາກເມນູ **TE Calculation** ເລືອກປະເພດທີ່ກ່ຽວຂ້ອງ.
- ເລືອກ Batch (ຖ້າມາຈາກເມນູ, ບໍ່ໄດ້ມາຈາກ View).

### ຂັ້ນຕອນທີ 2: Run TE Calculation
- ກົດ **Run TE Calculation** ຢູ່ໜ້າ TE Calculation.
- ລໍຖ້າລະບົບຄໍານວນ (ເບິ່ງ Spinner).
- ກວດສອບຜົນສະຫຼຸບ (Total TE, ຈໍານວນ Records).

ຫຼັງຈາກປ່ຽນ Benchmark ຫຼື Provision, ຜູ້ໃຊ້ຄວນ Run TE Calculation ໃໝ່ສໍາລັບ Batch ທີ່ກ່ຽວຂ້ອງ.

## 11. ການໃຊ້ລາຍງານ

Navigation:

```text
TE Reports
```

ລາຍງານຫຼັກ:

| ລາຍງານ | ຈຸດປະສົງ |
| --- | --- |
| TE by Tax Type | ສະຫຼຸບ TE ຕາມປະເພດພາສີ |
| TE by Sector | ສະຫຼຸບ TE ຕາມຂະແໜງທຸລະກິດ |
| TE by Location | ສະຫຼຸບ TE ຕາມແຂວງ |
| TE by Tax Type (% of GDP) | ປຽບທຽບ TE ກັບ GDP |
| TE by Tax Type (% of Revenue) | ປຽບທຽບ TE ກັບ Revenue |
| TE by Provision | ສະຫຼຸບ TE ຕາມ Provision |
| Customs Regime Reports | ສະຫຼຸບ TE ຂອງ ASYCUDA ຕາມ Regime |

### 11.1 ການ Filter ລາຍງານ

Filter ທີ່ພົບເລື້ອຍ:

| Filter | ຄວາມໝາຍ |
| --- | --- |
| From Year | ປີເລີ່ມຕົ້ນຂອງລາຍງານ |
| To Year | ປີສິ້ນສຸດຂອງລາຍງານ |
| Import From | ວັນທີ Import ເລີ່ມຕົ້ນ |
| Import To | ວັນທີ Import ສິ້ນສຸດ |

ຂໍ້ສໍາຄັນ:

- From Year / To Year ຄວບຄຸມປີຂອງຂໍ້ມູນລາຍງານ.
- Import From / Import To ຄວບຄຸມວ່າຈະນໍາ Batch ທີ່ Import ໃນຊ່ວງໃດມາຄິດ.
- ສໍາລັບລາຍງານ % of GDP ແລະ % of Revenue, GDP/Revenue ແມ່ນຄ່າອ້າງອີງ.

Screenshot placeholder:

```text
[Insert screenshot: Report filter controls]
```

### 11.2 Export ລາຍງານ

ລາຍງານສ່ວນໃຫຍ່ມີປຸ່ມ:

- `Export Excel`
- `Export PDF`

ໃຫ້ເລືອກ Filter ກ່ອນ, ແລ້ວຈຶ່ງ Export. ລະບົບຈະຮັກສາ Filter ທີ່ໃຊ້ໃນການ Export.

## 12. ຂໍ້ຜິດພາດທີ່ພົບເລື້ອຍ

| ອາການ | ສາເຫດທີ່ເປັນໄປໄດ້ | ວິທີແກ້ໄຂ |
| --- | --- | --- |
| Import ບໍ່ສໍາເລັດ | ໃຊ້ Template ຜິດ ຫຼື ຂໍ້ມູນຂາດ | Download Log ແລະກວດ Excel |
| Province/District Error | ຊື່ແຂວງ/ເມືອງບໍ່ກົງກັບ Dictionary | ແກ້ໃນ Excel ຫຼື ແຈ້ງ Admin |
| Date Error | Format ວັນທີບໍ່ຖືກ | ແກ້ໃຫ້ເປັນ Date ໃນ Excel |
| ລາຍງານບໍ່ມີຂໍ້ມູນ | ຍັງບໍ່ Run TE Calculation ຫຼື Filter ແຄບເກີນໄປ | Run Calculation ແລະກວດ Filter |
| Batch ຫາບໍ່ເຫັນ | ເລືອກ Tax Type ຜິດ ຫຼື Search ຜິດ | ເປີດ Batch Management ແລະ Clear Filter |
| TE ບໍ່ກົງຄາດໝາຍ | ສູດ, Benchmark, ຫຼືຂໍ້ມູນອາດຕ່າງກັນ | ກວດສອບ Row-by-row ແລະປຶກສາ Expert |

## 13. ການໃຊ້ Download Log

ຖ້າ Import ມີ Error ຫຼື Warning, ລະບົບອາດສ້າງ Log file.

ວິທີໃຊ້:

1. ຢູ່ໜ້າ Import ຫຼື Batch Management.
2. ກົດປຸ່ມ Log ຫຼື Download Log.
3. ເປີດໄຟລ໌ Log.
4. ກວດສອບບັນຫາເຊັ່ນ Row number, Column, Province, Date, ຫຼື Missing value.
5. ແກ້ໄຂ Excel ແລະ Import ໃໝ່.

## 14. ຂໍ້ຄວນລະວັງກ່ອນລຶບຂໍ້ມູນ

ການລຶບ Batch ເປັນການດໍາເນີນການທີ່ຄວນລະວັງ.

ກ່ອນລຶບ:

1. ກວດສອບ Tax Type.
2. ກວດສອບ Batch ID.
3. Download Log ຖ້າຈໍາເປັນ.
4. ຢືນຢັນວ່າບໍ່ໃຊ້ຂໍ້ມູນນັ້ນໃນລາຍງານແລ້ວ.
5. ຖ້າເປັນ Batch ສໍາຄັນ ໃຫ້ແຈ້ງ Admin ກ່ອນ.

## 15. ຄໍາແນະນໍາການເຮັດວຽກທີ່ດີ

- ໃຊ້ Template ລ່າສຸດຈາກລະບົບທຸກຄັ້ງ.
- ບໍ່ຄວນ Copy ຈາກ Template ເກົ່າທີ່ບໍ່ແນ່ໃຈ.
- ກວດສອບ Province, District, TIN, Tax Year ກ່ອນ Import.
- ຫຼັງ Import ຄວນເບິ່ງ Batch ກ່ອນ Run TE Calculation.
- ຫຼັງ Run TE Calculation ຄວນກວດສອບລາຍງານຫຼັກ.
- ເມື່ອພົບ Error ໃຫ້ເກັບ Batch ID ແລະ Log ໄວ້.

## 16. ສິ່ງທີ່ຄວນແຈ້ງ Admin ຫຼື Developer

ແຈ້ງ Admin ຫຼື Developer ເມື່ອ:

- Import ບໍ່ສໍາເລັດ ແລະບໍ່ສາມາດແກ້ຈາກ Log.
- ຕ້ອງການເພີ່ມ Province, District, Sector ຫຼື Dictionary value.
- ລາຍງານບໍ່ຖືກຕ້ອງຫຼັງ Run TE Calculation.
- TE ບໍ່ກົງຄາດໝາຍ ແລະຕ້ອງການກວດສູດ.
- ຕ້ອງການປ່ຽນ Template.
- ຕ້ອງການລຶບ Batch ສໍາຄັນ.

ເມື່ອແຈ້ງບັນຫາ ຄວນສົ່ງ:

- Page URL ຫຼື ຊື່ໜ້າຈໍ
- Batch ID
- Tax Type
- Screenshot ຖ້າມີ
- Log file ຖ້າມີ
- ຂັ້ນຕອນທີ່ເຮັດກ່ອນເກີດບັນຫາ

## 17. ພາກຜະນວກ: ຕໍາແໜ່ງເມນູສໍາຄັນ

| ວຽກ | ເມນູ |
| --- | --- |
| Import Profit Tax | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Profit Tax |
| Import PIT | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Individual Tax |
| Import Domestic VAT | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Domestic VAT |
| Import SEZ Developer | Get Tax Data by Import from Excel > Data Requirement to estimate TE > For SEZ Developers |
| Import SEZ Investor | Get Tax Data by Import from Excel > Data Requirement to estimate TE > For SEZ Investors |
| Import Land Concession | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Non-Tax: Land concession |
| Import Resource Fee | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Non-Tax: Resource fee |
| Import Royalty Fee | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Non-Tax: Royalty fee |
| Import ASYCUDA | Get Tax Data by Import from Excel > Data from ASYCUDA > Import New Data |
| Batch Management | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Batch Management |
| Run Profit Tax TE | TE Calculation > Profit Tax TE |
| Run PIT TE | TE Calculation > Individual Tax TE |
| Run Domestic VAT TE | TE Calculation > Domestic VAT TE |
| Run SEZ Developer TE | TE Calculation > For SEZ Developers TE |
| Run SEZ Investor TE | TE Calculation > For SEZ Investors TE |
| Run Land Concession TE | TE Calculation > Non-Tax > Land concession TE |
| Run Resource Fee TE | TE Calculation > Non-Tax > Resource fee TE |
| Run Royalty Fee TE | TE Calculation > Non-Tax > Royalty fee TE |
| Run ASYCUDA TE | TE Calculation > Data from ASYCUDA |
| View TE by Tax Type | TE Reports > TE by Tax Type |
| View TE by Sector | TE Reports > TE by Sector |
| View TE by Province | TE Reports > TE by Location |
| View TE by GDP | TE Reports > TE by Tax Type (% of GDP) |
| View TE by Revenue | TE Reports > TE by Tax Type (% of Revenue) |
| View TE by Provision | TE Reports > TE by Provision |

## 18. Screenshot Checklist for Final Manual

Screenshots to add later:

1. Login page
2. Dashboard and sidebar navigation
3. Download Template (e.g., Domestic VAT import page)
4. Import success and recent batches
5. Batch Management page (filtered by tax type)
6. View batch page with Go to TE Calculation button
7. TE Calculation page (before running)
8. TE Calculation page (after running, showing results)
9. TE by Tax Type report
10. Report filters and Export buttons (Excel + PDF)
11. Import error log example
12. Excel template examples (for key modules)

This checklist allows screenshots to be inserted later without rewriting the manual.
