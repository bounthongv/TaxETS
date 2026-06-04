# Tax-ETS Database Data Dictionary

## 1. Purpose

This document is the table-level and column-level data dictionary for the main Tax-ETS database tables. It complements `database-blueprint.md`.

The blueprint explains the database design and relationships. This data dictionary explains what each important table and column means.

Scope of this first version:

- Main TE import/input tables
- Main TE result tables
- Main benchmark/provision tables
- Main repository/reference tables
- Main system user tables

For exact SQL definitions, indexes, and constraints, use the live database or SQL files in `db/`.

## 2. Conventions

| Term | Meaning |
| --- | --- |
| PK | Primary key |
| FK | Foreign key or logical relationship |
| Batch | Group of records imported or manually entered together |
| TE | Tax Expenditure |
| Expert TE | TE value provided by external expert/template for verification |
| System TE | TE calculated by Tax-ETS |

Column type information is based on the current live database metadata at the time this document was created.

Manual-entry batch IDs are timestamped to identify the manual-entry session, using the pattern `MANUAL_ENTRY_{TYPE}_{YEAR}_{YYYYMMDDHHMMSS}`. Several records added in the same opened manual-entry view page share the same batch ID.

## 3. Core TE Input and Result Tables

## 3.1 `companies`

Purpose: Main imported company data for Profit Tax/CIT TE calculation. Also used by some sector/location reports and some legacy land concession paths.

Primary key: `id`

Batch column: `import_batch_id`

Related tables:

- `te_profit_result.company_id -> companies.id`
- `te_land_concession_result.company_id -> companies.id` in legacy land paths
- PIT and salary reports may match to this table by TIN for sector/location classification

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `import_batch_id` | `varchar(50)` | Batch ID for imported company data |
| `tax_year` | `int(11)` | Tax year of the company record |
| `tin` | `varchar(50)` | Taxpayer identification number |
| `company_name` | `varchar(255)` | Company name |
| `province` | `varchar(100)` | Province name |
| `pro_id` | `varchar(10)` | Province code |
| `district` | `varchar(100)` | District name |
| `dis_id` | `varchar(50)` | District code |
| `sector` | `varchar(100)` | Business sector name |
| `sector_id` | `int(11)` | Business sector dictionary ID |
| `is_vat_holder` | `tinyint(1)` | Whether company is VAT registered |
| `zone_1`, `zone_2`, `zone_3` | `tinyint(1)` | Investment zone flags used by provision logic |
| `revenue` | `decimal(20,2)` | Revenue amount |
| `expense` | `decimal(20,2)` | Expense amount |
| `net_profit` | `decimal(20,2)` | Net profit amount |
| `re_invested_profit` | `decimal(20,2)` | Reinvested profit amount |
| `pt_paid` | `decimal(20,2)` | Profit tax paid |
| `activity_type` | `varchar(100)` | Activity type/category |
| `staff_count` | `int(11)` | Number of staff |
| `total_assets` | `decimal(20,2)` | Total assets |
| `registration_date` | `date` | Enterprise registration date |
| `investment_license_date` | `date` | Investment license date |
| `annual_turnover` | `decimal(20,2)` | Annual turnover |
| `tax_holiday_years` | `int(11)` | Tax holiday years |
| `flag_hr_dev` | `tinyint(1)` | Human resource development flag |
| `flag_eco_friendly` | `tinyint(1)` | Eco-friendly activity flag |
| `flag_sez_developer` | `tinyint(1)` | SEZ developer flag |
| `flag_sez_investor` | `tinyint(1)` | SEZ investor flag |
| `flag_act_production_services` | `tinyint(1)` | Production/services activity flag |
| `flag_public_benefit` | `tinyint(1)` | Public benefit activity flag |
| `flag_compliant_rental` | `tinyint(1)` | Compliant rental flag |
| `flag_real_estate_transfer` | `tinyint(1)` | Real estate transfer flag |
| `flag_act_1_4_7_8_9` | `tinyint(1)` | Investment promotion law activity group flag |
| `flag_act_2_3_5_6` | `tinyint(1)` | Investment promotion law activity group flag |
| `stock_exchange_listing_date` | `date` | Lao Stock Exchange listing date |
| `reinvest_date` | `date` | Reinvestment date |
| `reinvest_amount` | `decimal(20,2)` | Reinvestment amount |
| `expert_te` | `decimal(20,2)` | Expert TE from template, used for verification |
| `land_area_sqm` | `decimal(15,2)` | Land area in square meters |
| `land_concession_article` | `varchar(50)` | Land concession article code |
| `land_concession_item` | `varchar(50)` | Land concession item code |
| `land_concession_zone` | `int(11)` | Land concession zone |
| `resource_extraction_item` | `varchar(50)` | Natural resource item |
| `sales_value_kip` | `decimal(15,2)` | Sales value in Kip |
| `zone_type` | `char(1)` | Zone type, default `A` |

## 3.2 `te_profit_result`

Purpose: Stores Profit Tax/CIT TE calculation output by company.

Primary key: `id`

Relationship:

```text
te_profit_result.company_id -> companies.id
```

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal result ID |
| `company_id` | `int(11)` | Related company row |
| `benchmark_rate_applied` | `decimal(5,2)` | Benchmark profit tax rate applied |
| `benchmark_pt` | `decimal(20,2)` | Benchmark profit tax amount |
| `pt_te` | `decimal(20,2)` | Profit tax TE amount, legacy/intermediate |
| `matched_provisions` | `varchar(255)` | Comma-separated matched provision numbers |
| `profit_tax_te` | `decimal(20,2)` | Final Profit Tax TE amount |
| `expert_te` | `decimal(20,2)` | Expert TE value for comparison |

## 3.3 `import_pit_data`

Purpose: Imported PIT/Individual Tax data. Used as input for the PIT engine.

Primary key: `id`

Batch column: `batch_id`

Logical relationship:

```text
import_pit_data.ptin + tax_year -> te_individual_result.tin + tax_year
```

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `batch_id` | `varchar(50)` | Import batch ID |
| `import_date` | `datetime` | Date/time imported |
| `tax_year` | `int(4)` | Tax year |
| `employee_name` | `varchar(255)` | Individual/taxpayer name |
| `filing_date` | `date` | Filing date |
| `ptin` | `varchar(50)` | Personal taxpayer identification number |
| `amount_21` to `amount_29` | `decimal(15,2)` | PIT taxable/exempt amounts by template/provision category |
| `is_ss_member` | `tinyint(1)` | Social security membership flag |
| `expert_te_21` to `expert_te_29` | `decimal(15,2)` | Expert TE by PIT category |
| `expert_te_total` | `decimal(15,2)` | Total Expert TE from template |

## 3.4 `te_individual_result`

Purpose: Stores PIT/Individual Tax TE calculation output.

Primary key: `id`

Logical relationship:

```text
te_individual_result.tin + tax_year -> import_pit_data.ptin + tax_year
```

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal result ID |
| `tax_year` | `int(11)` | Tax year |
| `tin` | `varchar(50)` | Taxpayer TIN/PTIN |
| `individual_name` | `varchar(255)` | Individual name |
| `filing_date` | `date` | Filing date |
| `employment_income` | `decimal(20,2)` | Employment income |
| `other_income` | `decimal(20,2)` | Other income |
| `actual_tax_paid` | `decimal(20,2)` | Actual tax paid |
| `benchmark_calculated_tax` | `decimal(20,2)` | Tax calculated under benchmark |
| `te_amount` | `decimal(20,2)` | PIT TE amount |
| `matched_provisions` | `varchar(255)` | Matched provision numbers |
| `created_at` | `timestamp` | Result creation timestamp |

## 3.5 `import_salary_tax_data`

Purpose: Imported salary tax data and salary TE calculation output.

Primary key: `id`

Batch column: `batch_id`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `batch_id` | `varchar(50)` | Import batch ID |
| `tax_year` | `int(4)` | Tax year |
| `import_date` | `datetime` | Date/time imported |
| `tin` | `varchar(50)` | Employer taxpayer identification number |
| `filing_type` | `varchar(50)` | Filing type |
| `filing_period` | `varchar(50)` | Filing period |
| `input_date` | `date` | Input date |
| `total_salaries_wages_cash` | `decimal(20,2)` | Salary and wage amount |
| `other_fringe_benefits` | `decimal(20,2)` | Other fringe benefits |
| `total_taxable_amount` | `decimal(20,2)` | Total taxable amount |
| `tax_exempt_amount` | `decimal(20,2)` | Tax exempt amount |
| `tax_amount` | `decimal(20,2)` | Tax amount |
| `adjustment_amount` | `decimal(20,2)` | Adjustment amount |
| `carryforward_amount` | `decimal(20,2)` | Carry-forward amount |
| `total_amount_due` | `decimal(20,2)` | Total amount due |
| `benchmark_tax` | `decimal(20,2)` | Benchmark salary tax |
| `te_amount` | `decimal(20,2)` | Salary tax TE |
| `provision_number` | `varchar(50)` | Matched provision number |
| `calculated_at` | `datetime` | Last calculation timestamp |

## 3.6 `import_vat_data`

Purpose: Imported Domestic VAT data and VAT TE calculation output.

Primary key: `id`

Batch column: `batch_id`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `batch_id` | `varchar(50)` | Import batch ID |
| `import_date` | `datetime` | Date/time imported |
| `province`, `district` | `varchar(255)` | Location names |
| `pro_id`, `dis_id` | `varchar` | Location codes |
| `sector_id` | `int(11)` | Business sector ID |
| `tin` | `varchar(50)` | Taxpayer identification number |
| `name` | `varchar(255)` | Taxpayer name |
| `filing_type` | `varchar(50)` | Filing type |
| `filing_period` | `date` | VAT filing period |
| `input_date` | `date` | Input date |
| `purchase_domestic_nonexempt` | `decimal(15,2)` | Domestic non-exempt purchases |
| `purchase_domestic_exempt` | `decimal(15,2)` | Domestic exempt purchases |
| `purchase_import_nonexempt` | `decimal(15,2)` | Import non-exempt purchases |
| `purchase_import_exempt` | `decimal(15,2)` | Import exempt purchases |
| `total_input_vat` | `decimal(15,2)` | Total input VAT |
| `sales_standard` | `decimal(15,2)` | Standard-rate sales |
| `sales_zero_rate` | `decimal(15,2)` | Zero-rated sales |
| `sales_exempt` | `decimal(15,2)` | Exempt sales |
| `total_output_vat` | `decimal(15,2)` | Total output VAT |
| `vat_payable` | `decimal(15,2)` | VAT payable |
| `vat_credit` | `decimal(15,2)` | VAT credit |
| `expert_te` | `decimal(15,2)` | Expert TE from template |
| `system_te` | `decimal(15,2)` | TE calculated by Tax-ETS |
| `benchmark_output_vat` | `decimal(15,2)` | Benchmark output VAT |
| `calculated_vat_payable` | `decimal(15,2)` | VAT payable calculated by system |
| `provision_number` | `varchar(20)` | Matched VAT provision number |

## 3.7 `asycuda_imports`

Purpose: Imported ASYCUDA customs declaration data. One batch supports Customs Duty TE, Excise TE, and Import VAT TE.

Primary key: `id`

Batch column: `import_batch_id`

Relationship:

```text
asycuda_imports.id -> te_asycuda_result.asycuda_id
```

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `import_batch_id` | `varchar(50)` | ASYCUDA import batch ID |
| `import_date` | `datetime` | Date/time imported |
| `province`, `pro_id` | `varchar` | Province name/code |
| `tin` | `varchar(50)` | Importer TIN |
| `no_seq` | `varchar(50)` | Sequence number |
| `border_code`, `border_name` | `varchar` | Border office code/name |
| `type_customs` | `varchar(20)` | Customs type |
| `process_type` | `varchar(20)` | Process type |
| `regime_f` | `varchar(20)` | Regime field |
| `special_role` | `varchar(50)` | Special role |
| `regime_code` | `varchar(20)` | Customs regime code |
| `doc_number`, `doc_date` | `varchar/date` | Declaration document number/date |
| `assess_number`, `assess_date` | `varchar/date` | Assessment number/date |
| `receipt_no`, `receipt_date` | `varchar/date` | Receipt number/date |
| `importer_name` | `text` | Importer name |
| `declarant_tin`, `declarant_name` | `varchar/text` | Declarant information |
| `export_country`, `dest_country`, `origin_country` | `varchar(10)` | Country codes |
| `list_no` | `varchar(20)` | List/item number |
| `hs_code` | `varchar(20)` | HS code |
| `goods_description` | `text` | Goods description |
| `quantity`, `unit` | `decimal/varchar` | Quantity and unit |
| `declare_weight`, `actual_weight` | `decimal(20,4)` | Declared and actual weights |
| `invoice_usd` | `decimal(20,4)` | Invoice amount in USD |
| `invoice_amount_lak` | `decimal(20,2)` | Invoice amount in LAK |
| `paid_customs`, `paid_excise`, `paid_vat` | `decimal(20,2)` | Tax paid amounts |
| `paid_profit`, `paid_road_fund`, `paid_total` | `decimal(20,2)` | Other paid amounts |
| `status_aj` | `varchar(50)` | Status field |
| `exemp_customs` | `decimal(20,2)` | Benchmark/exempt customs amount |
| `exempt_excise` | `decimal(20,2)` | Benchmark/exempt excise amount |
| `exempt_vat` | `decimal(20,2)` | Benchmark/exempt VAT amount |
| `te_customs_excel` | `decimal(20,2)` | Expert/customs TE from Excel |
| `te_excise_excel` | `decimal(20,2)` | Expert/excise TE from Excel |
| `te_vat_excel` | `decimal(20,2)` | Expert/import VAT TE from Excel |
| `provision_customs` | `varchar(100)` | Customs provision/reference |

## 3.8 `te_asycuda_result`

Purpose: Stores ASYCUDA TE result components.

Primary key: `id`

Relationship:

```text
te_asycuda_result.asycuda_id -> asycuda_imports.id
```

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal result ID |
| `asycuda_id` | `int(11)` | Related ASYCUDA import row |
| `customs_te` | `decimal(20,2)` | Customs Duty TE |
| `excise_te` | `decimal(20,2)` | Excise Tax TE |
| `vat_te` | `decimal(20,2)` | Import VAT TE |
| `total_te` | `decimal(20,2)` | Total TE across the three components |

## 3.9 `import_sez_data`

Purpose: Imported SEZ Developer and SEZ Investor data. TE output is stored in this same table.

Primary key: `id`

Batch column: `batch_id`

Important classifier: `type` is either `Developer` or `Investor`.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `batch_id` | `varchar(50)` | Import batch ID |
| `tax_year` | `int(4)` | Tax year |
| `import_date` | `datetime` | Date/time imported |
| `tin` | `varchar(50)` | Taxpayer identification number |
| `license_date` | `date` | License date |
| `province`, `district` | `varchar(255)` | Location names |
| `pro_id`, `dis_id` | `varchar` | Location codes |
| `province_id`, `district_id` | `int(11)` | Dictionary IDs |
| `sector` | `varchar(50)` | Business sector |
| `type` | `enum` | `Developer` or `Investor` |
| `amount_infra_basic` | `decimal(20,2)` | Basic infrastructure amount |
| `amount_infra_other` | `decimal(20,2)` | Other infrastructure amount |
| `amount_utility_usage` | `decimal(20,2)` | Utility usage amount |
| `amount_infra_dev` | `decimal(20,2)` | Infrastructure development amount |
| `benchmark_tax` | `decimal(20,2)` | Benchmark tax |
| `te_amount` | `decimal(20,2)` | SEZ TE |
| `provision_number` | `varchar(50)` | Matched SEZ provision |
| `calculated_at` | `datetime` | Last calculation timestamp |

## 3.10 `repo_land_concession_data`

Purpose: Imported land concession data and land concession non-tax TE output.

Current import template columns:

```text
TIN, CompanyName, District, Province, TaxItem, Year, Receiptdate,
Concessionarea, BenchmarkRate, ContractedRate, ConcessionFeePaid, ProvisionName
```

`TaxItem` is accepted in the workbook but not stored in a dedicated column yet. If workbook `Year` is blank, the selected import-page tax year is stored.

Primary key: `id`

Batch column: `import_batch_id`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `import_batch_id` | `varchar(50)` | Import batch ID |
| `tax_year` | `int(11)` | Tax year |
| `tin` | `varchar(50)` | Taxpayer identification number |
| `company_name` | `varchar(255)` | Company name |
| `pro_id`, `province` | `varchar` | Province code/name |
| `dis_id`, `district` | `varchar` | District code/name |
| `confirm_date` | `date` | Confirmation date |
| `concession_area_ha` | `decimal(20,4)` | Concession area in hectares |
| `benchmark_rate_usd` | `decimal(20,4)` | Benchmark rate in USD |
| `contracted_rate_usd` | `decimal(20,4)` | Contracted rate in USD |
| `concession_fee_paid_usd` | `decimal(20,4)` | Actual concession fee paid |
| `benchmark_value_usd` | `decimal(20,4)` | Benchmark fee value |
| `non_tax_te_usd` | `decimal(20,4)` | Non-tax TE amount in USD; currently `0` when `provision_name` is blank |
| `provision_code` | `varchar(50)` | Provision code |
| `provision_name` | `varchar(255)` | Provision name |
| `created_at` | `timestamp` | Row creation timestamp |

## 3.11 `import_resource_data`

Purpose: Imported natural resource fee data and calculated resource fee TE.

Primary key: `id`

Batch column: `batch_id`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `batch_id` | `varchar(50)` | Import batch ID |
| `import_date` | `datetime` | Date/time imported |
| `tax_year` | `int(4)` | Tax year |
| `tin` | `varchar(50)` | Taxpayer identification number |
| `license_date` | `date` | License date |
| `resource_type` | `varchar(255)` | Natural resource type |
| `actual_rate` | `decimal(15,2)` | Actual fee rate |
| `fee_collected` | `decimal(20,2)` | Actual fee collected |
| `benchmark_rate` | `decimal(15,2)` | Benchmark fee rate |
| `benchmark_fee` | `decimal(20,2)` | Benchmark fee amount |
| `te_amount` | `decimal(20,2)` | Resource fee TE |
| `calculated_at` | `datetime` | Last calculation timestamp |
| `created_at` | `timestamp` | Row creation timestamp |

## 3.12 `import_royalty_data`

Purpose: Imported royalty fee data and calculated royalty fee TE.

Primary key: `id`

Batch column: `batch_id`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `batch_id` | `varchar(50)` | Import batch ID |
| `tax_year` | `int(4)` | Tax year |
| `import_date` | `datetime` | Date/time imported |
| `tin` | `varchar(50)` | Taxpayer identification number |
| `license_date` | `date` | License date |
| `electricity_sale_value` | `decimal(20,2)` | Electricity sale value |
| `actual_rate` | `decimal(15,2)` | Actual royalty rate |
| `fee_collected` | `decimal(20,2)` | Actual fee collected |
| `benchmark_rate` | `decimal(15,2)` | Benchmark royalty rate |
| `benchmark_fee` | `decimal(20,2)` | Benchmark fee amount |
| `te_amount` | `decimal(20,2)` | Royalty fee TE |
| `calculated_at` | `datetime` | Last calculation timestamp |

## 4. GDP and Revenue Reference Table

## 4.1 `repo_gdp_revenue`

Purpose: Stores GDP and revenue reference values used as denominators in comparison reports.

Primary key: `id`

Important: import-date filters in reports do not filter GDP or revenue values. They filter only the TE numerator.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal row ID |
| `import_batch_id` | `varchar(50)` | Batch ID for GDP/revenue import |
| `gdp_year` | `int(11)` | Year |
| `gdp_value` | `decimal(20,2)` | GDP value, stored in billions in current report logic |
| `revenue_value` | `decimal(20,2)` | Revenue value |
| `note` | `text` | Notes |
| `created_at` | `timestamp` | Created timestamp |
| `updated_at` | `timestamp` | Updated timestamp |

## 5. Provision Tables

## 5.1 `profit_provisions`

Purpose: Stores CIT legal provisions.

Primary key: `id`

Related table:

```text
profit_provision_conditions.provision_id -> profit_provisions.id
```

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal provision ID |
| `provision_number` | `varchar(10)` | Provision number |
| `start_year` | `int(4)` | First applicable year |
| `end_year` | `int(4)` | Last applicable year |
| `legal_reference` | `varchar(255)` | Legal reference |
| `description` | `text` | Provision description |
| `target_rate` | `decimal(5,2)` | Target/relief rate where applicable |
| `is_exemption` | `tinyint(1)` | Whether provision is full exemption |

## 5.2 `profit_provision_conditions`

Purpose: Stores condition logic for CIT provision matching.

Primary key: `id`

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal condition ID |
| `provision_id` | `int(11)` | Related CIT provision |
| `field_name` | `varchar(100)` | Field in `companies` to evaluate |
| `operator` | `varchar(20)` | Condition operator |
| `value_1` | `varchar(255)` | First comparison value |
| `value_2` | `varchar(255)` | Second comparison value, used by range operators |

## 5.3 `individual_provisions`

Purpose: Stores PIT legal provisions.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal provision ID |
| `provision_number` | `varchar(10)` | Provision number |
| `start_year` | `int(4)` | First applicable year |
| `end_year` | `int(4)` | Last applicable year |
| `legal_basis` | `varchar(255)` | Legal basis |
| `type_of_te` | `varchar(50)` | TE type |
| `description` | `text` | Provision description |
| `purpose` | `varchar(255)` | Policy purpose |
| `limit_amount` | `decimal(20,2)` | Limit amount where applicable |

## 5.4 `salary_provisions`

Purpose: Stores salary tax provisions.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal provision ID |
| `provision_number` | `varchar(10)` | Provision number |
| `legal_basis` | `varchar(255)` | Legal basis |
| `type_of_te` | `varchar(50)` | TE type |
| `description` | `text` | Provision description |
| `purpose` | `varchar(255)` | Policy purpose |
| `is_active` | `tinyint(1)` | Active flag |
| `created_at` | `timestamp` | Created timestamp |

## 5.5 `vat_provisions`

Purpose: Stores Domestic VAT provisions.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal provision ID |
| `provision_number` | `varchar(20)` | VAT provision number |
| `start_year` | `int(4)` | First applicable year |
| `end_year` | `int(4)` | Last applicable year |
| `legal_basis` | `varchar(255)` | Legal basis |
| `description` | `text` | Provision description |
| `purpose` | `varchar(255)` | Policy purpose |
| `type_of_te` | `enum` | `Exemption` or `Rate Relief` |

## 6. Benchmark Tables

## 6.1 `bm_profit_standard`

Purpose: Stores standard Profit Tax benchmark rates.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal benchmark ID |
| `start_year` | `int(11)` | First applicable year |
| `end_year` | `int(11)` | Last applicable year |
| `category` | `varchar(50)` | Benchmark category |
| `rate_percentage` | `decimal(5,2)` | Rate percentage |

## 6.2 `bm_pit_employment`

Purpose: Stores PIT employment income progressive tax brackets.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal rate ID |
| `start_year` | `int(11)` | First applicable year |
| `end_year` | `int(11)` | Last applicable year |
| `min_income` | `decimal(20,2)` | Lower income bound |
| `max_income` | `decimal(20,2)` | Upper income bound; nullable for open-ended bracket |
| `rate_percentage` | `decimal(5,2)` | PIT rate |

## 6.3 `bm_vat`

Purpose: Stores VAT benchmark rate by effective date.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal rate ID |
| `start_date` | `date` | First effective date |
| `end_date` | `date` | Last effective date |
| `rate_percentage` | `decimal(5,2)` | VAT rate |

## 6.4 `bm_land_concession`

Purpose: Stores benchmark rates for land concession fees.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal benchmark ID |
| `article_no` | `varchar(50)` | Article number |
| `article_name` | `varchar(255)` | Article name |
| `item_no` | `varchar(50)` | Item number |
| `item_name` | `text` | Item description |
| `rate_zone1`, `rate_zone2`, `rate_zone3` | `decimal(15,2)` | Zone-specific benchmark rates |
| `rate_search`, `rate_survey`, `rate_analysis` | `decimal(15,2)` | Other land-related benchmark rates |
| `unit` | `varchar(50)` | Rate unit |
| `start_year` | `smallint(6)` | First applicable year |
| `end_year` | `smallint(6)` | Last applicable year |
| `active` | `tinyint(1)` | Active flag |
| `created_at` | `timestamp` | Created timestamp |

## 6.5 `bm_natural_resource`

Purpose: Stores benchmark rates for natural resource fees.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal benchmark ID |
| `item_no` | `varchar(50)` | Item number |
| `item_name` | `text` | Resource item name |
| `rate_percentage` | `decimal(15,2)` | Benchmark rate |
| `start_year` | `smallint(6)` | First applicable year |
| `end_year` | `smallint(6)` | Last applicable year |
| `active` | `tinyint(1)` | Active flag |
| `created_at` | `timestamp` | Created timestamp |

## 7. User and Role Tables

## 7.1 `users`

Purpose: Stores system user accounts.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal user ID |
| `name` | `varchar(100)` | User full name |
| `email` | `varchar(100)` | Login email, unique |
| `password` | `varchar(255)` | Password hash |
| `position` | `varchar(100)` | Job position |
| `phone` | `varchar(20)` | Phone number |
| `role_id` | `int(11)` | Related role |
| `photo` | `varchar(255)` | User photo path |
| `active` | `tinyint(1)` | Active flag |
| `created_at` | `timestamp` | Created timestamp |
| `updated_at` | `timestamp` | Updated timestamp |

## 7.2 `roles`

Purpose: Stores user role definitions.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | `int(11)` | Internal role ID |
| `role_name` | `varchar(50)` | Role name |
| `role_description` | `varchar(255)` | Role description |
| `created_at` | `timestamp` | Created timestamp |

## 8. Important Index and Relationship Notes

The live database has indexes on many key columns, including:

- batch columns such as `batch_id` and `import_batch_id`,
- taxpayer identifiers such as `tin` and `ptin`,
- year columns such as `tax_year`,
- ASYCUDA `regime_code`,
- result foreign keys such as `company_id` and `asycuda_id`.

Some relationships are enforced by foreign keys, while others are logical relationships only. For example:

- `te_profit_result.company_id` is a direct relationship to `companies.id`.
- `te_asycuda_result.asycuda_id` is a direct relationship to `asycuda_imports.id`.
- `te_individual_result` is matched to imports by `tin/ptin` plus `tax_year`, not by batch ID.

## 9. Known Data Dictionary Caveats

1. Batch ID columns use mixed names: `batch_id` and `import_batch_id`.
2. Location reference tables are mixed between newer `provinces/districts` and older `province/district`. The active Land Concession import/view path uses `provinces/districts`.
3. Some modules store TE result data in dedicated result tables; others store TE fields in the import table.
4. PIT results do not store the source `batch_id`, so batch-level PIT cleanup and reporting need careful handling.
5. Some text columns have different collations; cross-table comparisons should explicitly apply collation.
6. Expert TE columns are verification fields and should not be shown to ordinary users unless specifically allowed.

## 10. Maintenance Guidance

When adding or changing a table:

1. Update the SQL schema or migration file in `db/`.
2. Update `database-blueprint.md` if relationships or data flow change.
3. Update this data dictionary if table purpose or important columns change.
4. Update report source mapping if the table feeds reports.
5. Add indexes for common filters: batch, year, TIN, import date.
6. Check collation consistency for text joins.
