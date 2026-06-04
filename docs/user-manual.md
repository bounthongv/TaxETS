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

## 4. ການເຂົ້າລະບົບ

1. ເປີດ Browser ແລ້ວເຂົ້າ URL ຂອງລະບົບ Tax-ETS.
2. ໃສ່ Email ແລະ Password.
3. ກົດປຸ່ມ Login.
4. ຫຼັງຈາກເຂົ້າລະບົບສໍາເລັດ ລະບົບຈະເຂົ້າສູ່ Dashboard.

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
5. ເປີດ Batch ຜ່ານ Batch Management.
6. ເບິ່ງ ຫຼື ແກ້ໄຂຂໍ້ມູນໃນ Batch.
7. Run TE Calculation.
8. ກວດສອບຜົນການຄໍານວນ.
9. ເບິ່ງລາຍງານ ແລະ Export Excel/PDF.

```text
Excel Template -> Import -> Batch Management -> View/Edit -> TE Calculation -> Reports
```

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

### 7.3.1 Manual Entry

ຖ້າບໍ່ໄດ້ Import ຈາກ Excel, ຜູ້ໃຊ້ສາມາດກົດ `Add Manual Entry` ຢູ່ໜ້າ Import.

1. ເລືອກ Tax Year.
2. ກົດ `Add Manual Entry`.
3. ລະບົບຈະສ້າງ Batch ID ໃໝ່ທີ່ມີປີ ແລະ ເວລາ, ຕົວຢ່າງ `MANUAL_ENTRY_VAT_2026_20260604153022`.
4. ລະບົບຈະເປີດໜ້າ View/Edit ແລະເປີດຟອມ Add Record.
5. ຖ້າເພີ່ມຫຼາຍລາຍການໃນໜ້ານັ້ນ, ລາຍການທັງໝົດຈະຢູ່ໃນ Batch ດຽວກັນ.
6. ຖ້າກັບໄປກົດ `Add Manual Entry` ໃໝ່ພາຍຫຼັງ, ລະບົບຈະສ້າງ Batch ໃໝ່.

Screenshot placeholder:

```text
[Insert screenshot: Manual entry year selection and add form]
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
- ກົດ Add Record to Batch ຖ້າຕ້ອງການເພີ່ມລາຍການ.

ຖ້າເຂົ້າມາຈາກ Batch Management, ຈະມີປຸ່ມ `Batch Management` ສໍາລັບກັບຄືນໄປໜ້າ Hub.

Screenshot placeholder:

```text
[Insert screenshot: Domestic VAT records page with Batch Management back button]
```

### 7.6 Run TE Calculation

1. ຢູ່ໜ້າ Domestic VAT Records, ກົດ `Run TE Calculation`.
2. ຫຼື ເປີດຈາກ:

```text
TE Calculation > Domestic VAT TE
```

3. ເລືອກ Batch ຫຼື ໃຫ້ລະບົບເປີດ Batch ທີ່ສົ່ງມາ.
4. ກົດ Run TE Calculation.
5. ກວດສອບຜົນການຄໍານວນ.

Screenshot placeholder:

```text
[Insert screenshot: Domestic VAT TE calculation page]
```

### 7.7 ການກວດສອບຜົນ

ຫຼັງຈາກຄໍານວນ:

- ກວດສອບ System TE.
- ກວດສອບ Expert TE ຖ້າຜູ້ໃຊ້ມີສິດເຫັນ.
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
- ສໍາລັບ Manual Entry Batch, ໃຫ້ກວດສອບ Tax Type, Year ແລະ Timestamp ໃນ Batch ID ກ່ອນລຶບ.
- ຖ້າ Batch ມີ Error Log, ຄວນ Download Log ໄວ້ກ່ອນລຶບ.

Screenshot placeholder:

```text
[Insert screenshot: Batch Management page]
```

## 9. ຂໍ້ມູນເຉພາະຂອງແຕ່ລະປະເພດພາສີ

ຂັ້ນຕອນ Import, View/Edit, Run TE Calculation ແລະ Reports ສ່ວນໃຫຍ່ຄ້າຍຄືກັນ. ຕາຕະລາງນີ້ສະຫຼຸບຈຸດສໍາຄັນຂອງແຕ່ລະປະເພດ.

| ປະເພດ | ເມນູ Import | ເມນູ TE Calculation | ຈຸດສັງເກດ |
| --- | --- | --- | --- |
| Profit Tax | Profit Tax | Profit Tax TE | ໃຊ້ຂໍ້ມູນບໍລິສັດ, ກໍາໄລ, ອັດຕາ Benchmark ແລະ Provisions |
| Individual Tax | Individual Tax | Individual Tax TE | ອີງໃສ່ PTIN/TIN ແລະ Tax Year |
| Salary Tax | Salary Tax | Salary Tax TE | ອີງໃສ່ຂໍ້ມູນເງິນເດືອນ ແລະຈໍານວນທີ່ຍົກເວັ້ນ |
| Domestic VAT | Domestic VAT | Domestic VAT TE | ໃຊ້ຂໍ້ມູນ Filing Period, Sales, Input/Output VAT |
| ASYCUDA | Data from ASYCUDA > Import New Data | Customs Duty TE, Excise Tax TE, Import VAT TE | Import ຄັ້ງດຽວ ແຕ່ແຍກໄປ 3 ປະເພດ TE |
| SEZ Developer | For SEZ Developers | For SEZ Developers TE | ສໍາລັບຜູ້ພັດທະນາ SEZ |
| SEZ Investor | For SEZ Investors | For SEZ Investors TE | ສໍາລັບນັກລົງທຶນໃນ SEZ |
| Land Concession | Non-Tax: Land concession | Land concession TE | ອີງໃສ່ເນື້ອທີ່, ອັດຕາສັນຍາ, ແລະ Benchmark |
| Resource Fee | Non-Tax: Resource fee | Resource fee TE | ອີງໃສ່ປະເພດຊັບພະຍາກອນ ແລະ Fee collected |
| Royalty Fee | Non-Tax: Royalty fee | Royalty fee TE | ອີງໃສ່ມູນຄ່າຂາຍ ແລະ Royalty rate |

Land Concession template columns:

```text
TIN, CompanyName, District, Province, TaxItem, Year, Receiptdate,
Concessionarea, BenchmarkRate, ContractedRate, ConcessionFeePaid, ProvisionName
```

ຂໍ້ສັງເກດ:

- `TaxItem` ສາມາດມີໃນ Excel ໄດ້, ແຕ່ລະບົບຍັງບໍ່ໄດ້ເກັບເປັນຄໍລໍາແຍກ.
- ຖ້າ `Year` ໃນ Excel ຫວ່າງ, ລະບົບຈະໃຊ້ Tax Year ທີ່ເລືອກຢູ່ໜ້າ Import.
- `Benchmark Value` ແລະ `Non-Tax TE` ບໍ່ຕ້ອງມີໃນ Excel; ລະບົບຈະຄໍານວນໃນໜ້າ TE.
- ຖ້າ `ProvisionName` ຫວ່າງ, Land Concession TE ຈະເປັນ `0` ກ່ອນ, ຈົນກວ່າຈະຢືນຢັນນະໂຍບາຍ/ກົດໝາຍການຍົກເວັ້ນ.

## 10. ການ Run TE Calculation

ຜູ້ໃຊ້ສາມາດເຂົ້າໜ້າຄໍານວນໄດ້ 2 ທາງ:

1. ຈາກ View Page ຂອງ Batch ໂດຍກົດ `Run TE Calculation`.
2. ຈາກເມນູ `TE Calculation`.

ຂັ້ນຕອນທົ່ວໄປ:

1. ເລືອກ Batch.
2. ກວດສອບຈໍານວນ Records.
3. ກົດ `Run TE Calculation`.
4. ລໍຖ້າລະບົບຄໍານວນ.
5. ກວດສອບຜົນສະຫຼຸບ.

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
| Customs Regime Reports | ສະຫຼຸບ TE ຂອງ ASYCUDA ຕາມ Regime/Payment Condition |

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
- ສໍາລັບລາຍງານ % of GDP ແລະ % of Revenue, GDP/Revenue ແມ່ນຄ່າອ້າງອີງ. Import Date Filter ຈະ Filter ສະເພາະ TE.

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
| TE ບໍ່ກົງ Expert TE | ສູດ, Benchmark, ຫຼືຂໍ້ມູນອາດຕ່າງກັນ | ກວດສອບ Row-by-row ແລະປຶກສາ Expert |

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
- ຫຼັງ Import ຄວນເບິ່ງ Batch ກ່ອນ Run Calculation.
- ຫຼັງ Run Calculation ຄວນກວດສອບລາຍງານຫຼັກ.
- ເມື່ອພົບ Error ໃຫ້ເກັບ Batch ID ແລະ Log ໄວ້.

## 16. ສິ່ງທີ່ຄວນແຈ້ງ Admin ຫຼື Developer

ແຈ້ງ Admin ຫຼື Developer ເມື່ອ:

- Import ບໍ່ສໍາເລັດ ແລະບໍ່ສາມາດແກ້ຈາກ Log.
- ຕ້ອງການເພີ່ມ Province, District, Sector ຫຼື Dictionary value.
- ລາຍງານບໍ່ຖືກຕ້ອງຫຼັງ Run Calculation.
- TE ບໍ່ກົງ Expert TE ແລະຕ້ອງການກວດສູດ.
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
| Import Domestic VAT | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Domestic VAT |
| Batch Management | Get Tax Data by Import from Excel > Data Requirement to estimate TE > Batch Management |
| Run Profit Tax TE | TE Calculation > Profit Tax TE |
| Run Domestic VAT TE | TE Calculation > Domestic VAT TE |
| Run ASYCUDA TE | TE Calculation > Data from ASYCUDA |
| View TE by Tax Type | TE Reports > TE by Tax Type |
| View TE by Sector | TE Reports > TE by Sector |
| View TE by Provision | TE Reports > TE by Provision |

## 18. Screenshot Checklist for Final Manual

Screenshots to add later:

1. Login page
2. Dashboard and sidebar
3. Domestic VAT import page
4. Excel template download area
5. Import success and recent batches
6. Batch Management page
7. View batch page with Batch Management back button
8. TE Calculation page
9. TE by Tax Type report
10. Report filters and Export buttons
11. Import error log example

This checklist allows screenshots to be inserted later without rewriting the manual.
