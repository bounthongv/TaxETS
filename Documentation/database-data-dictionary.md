# Tax-ETS Database Data Dictionary — Module Addendum

> **Note**: This addendum documents tables added or significantly modified after the original data dictionary was written.

---

## A. Domestic VAT — `import_vat_data`

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `batch_id` | VARCHAR(50) | Import batch ID (prefix: `VAT_BATCH_`) |
| `import_date` | DATETIME | When the record was imported |
| `province` | VARCHAR(100) | Province name |
| `tin` | VARCHAR(20) | Taxpayer ID |
| `company_name` | VARCHAR(255) | Enterprise name |
| `filing_type` | VARCHAR(50) | Monthly, quarterly, etc. |
| `filing_period` | DATE | Month/Year of filing |
| `input_date` | DATE | Record entry date |
| `description` | TEXT | Item description |
| `vat_rate` | DECIMAL(5,2) | VAT rate percentage |
| `sales_standard` | DECIMAL(20,2) | Standard-rated sales |
| `sales_zero_rate` | DECIMAL(20,2) | Zero-rated sales |
| `sales_exempt` | DECIMAL(20,2) | Exempt sales |
| `vat_payable` | DECIMAL(20,2) | VAT payable |
| `expert_te` | DECIMAL(20,2) | Legacy TE value from template |
| `system_te` | DECIMAL(20,2) | System-calculated TE (from engine) |
| `provision_number` | VARCHAR(50) | Applied provision |
| `benchmark_output_vat` | DECIMAL(20,2) | Benchmark output VAT |
| `calculated_vat_payable` | DECIMAL(20,2) | Calculated VAT payable |
| `use_user_fallback` | TINYINT(1) | User override flag |
| `user_benchmark_rate` | DECIMAL(10,2) | User override rate |
| `user_te` | DECIMAL(20,2) | User-entered TE |
| `user_comment` | TEXT | User comment |

---

## B. SEZ — `import_sez_data` (Shared: Developer + Investor)

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `batch_id` | VARCHAR(50) | Batch ID (`SEZDEV_BATCH_` or `SEZINV_BATCH_`) |
| `import_date` | DATETIME | Import timestamp |
| `type` | VARCHAR(20) | `Developer` or `Investor` |
| `tax_year` | SMALLINT | Fiscal year |
| `tin` | VARCHAR(20) | Taxpayer ID |
| `company_name` | VARCHAR(255) | Enterprise name |
| `license_date` | DATE | Investment license date |
| `province` | VARCHAR(100) | Province |
| `district` | VARCHAR(100) | District |
| `village` | VARCHAR(100) | Village |
| `sez_name` | VARCHAR(255) | SEZ name |
| `sector` | VARCHAR(100) | Industry sector |
| `amount_infra_basic` | DECIMAL(20,2) | Basic infrastructure (Developer) |
| `amount_infra_other` | DECIMAL(20,2) | Other infrastructure (Developer) |
| `amount_utility_usage` | DECIMAL(20,2) | Utility usage (Investor) |
| `amount_infra_dev` | DECIMAL(20,2) | Infrastructure development (Investor) |
| `benchmark_tax` | DECIMAL(20,2) | Benchmark tax amount |
| `te_amount` | DECIMAL(20,2) | Calculated TE |
| `provision_number` | VARCHAR(50) | Applied provision (VAT-D1, VAT-D2, etc.) |

---

## C. Land Concession — `repo_land_concession_data`

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `import_batch_id` | VARCHAR(50) | Batch ID (`LAND_BATCH_`) |
| `tax_year` | SMALLINT | Fiscal year |
| `tin` | VARCHAR(20) | Taxpayer ID |
| `company_name` | VARCHAR(255) | Enterprise name |
| `province` | VARCHAR(100) | Province |
| `district` | VARCHAR(100) | District |
| `confirm_date` | DATE | Confirmation date |
| `concession_area_ha` | DECIMAL(15,2) | Area in hectares |
| `benchmark_rate_usd` | DECIMAL(15,2) | Benchmark rate per ha |
| `contracted_rate_usd` | DECIMAL(15,2) | Contracted rate per ha |
| `concession_fee_paid_usd` | DECIMAL(20,2) | Fee paid |
| `benchmark_value_usd` | DECIMAL(20,2) | Area × benchmark rate |
| `non_tax_te_usd` | DECIMAL(20,2) | Calculated TE (max(0, benchmark - paid)) |
| `provision_code` | VARCHAR(50) | Applied provision code |
| `provision_name` | VARCHAR(255) | Provision description |
| `description` | TEXT | Concession type description |
| `paid_currency` | VARCHAR(10) | Currency code (USD, LAK, THB) |
| `exchange_rate` | DECIMAL(15,2) | Exchange rate to USD |
| `use_user_fallback` | TINYINT(1) | User override flag |
| `user_benchmark_rate` | DECIMAL(15,2) | User override rate |
| `user_benchmark_value` | DECIMAL(20,2) | User override benchmark |
| `user_nontax_te` | DECIMAL(20,2) | User TE value |
| `user_fallback_reason` | VARCHAR(255) | Reason for fallback |
| `user_comment` | TEXT | User comment |

---

## D. Resource Fee — `import_resource_data`

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `batch_id` | VARCHAR(50) | `RESOURCE_BATCH_` |
| `import_date` | DATETIME | Import timestamp |
| `tax_year` | SMALLINT | Fiscal year |
| `tin` | VARCHAR(20) | Taxpayer ID |
| `license_date` | DATE | Investment license date |
| `resource_type` | VARCHAR(50) | Resource type ID |
| `actual_rate` | DECIMAL(10,2) | Actual (benchmark) rate |
| `fee_collected` | DECIMAL(20,2) | Fee collected |
| `benchmark_rate` | DECIMAL(10,2) | System benchmark rate |
| `benchmark_fee` | DECIMAL(20,2) | System benchmark fee |
| `te_amount` | DECIMAL(20,2) | Calculated TE |
| `contracted_rate` | DECIMAL(10,2) | Contracted rate |
| `sale_quantity` | DECIMAL(15,2) | Quantity sold (tons) |
| `paid_currency` | VARCHAR(10) | Currency code |
| `exchange_rate` | DECIMAL(15,2) | Exchange rate |
| `use_user_fallback` | TINYINT(1) | User override flag |
| `user_benchmark_rate` | DECIMAL(10,2) | User override rate |
| `user_benchmark_fee` | DECIMAL(20,2) | User override fee |
| `user_te` | DECIMAL(20,2) | User TE |
| `user_fallback_reason` | VARCHAR(255) | Fallback reason |
| `user_comment` | TEXT | Comment |

---

## E. Royalty Fee — `import_royalty_data`

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `batch_id` | VARCHAR(50) | `ROYALTY_BATCH_` |
| `import_date` | DATETIME | Import timestamp |
| `tax_year` | SMALLINT | Fiscal year |
| `tin` | VARCHAR(20) | Taxpayer ID |
| `license_date` | DATE | License date |
| `electricity_sale_value` | DECIMAL(20,2) | Electricity sales (USD) |
| `actual_rate` | DECIMAL(10,2) | Actual rate |
| `fee_collected` | DECIMAL(20,2) | Fee collected |
| `benchmark_rate` | DECIMAL(10,2) | System benchmark rate |
| `benchmark_fee` | DECIMAL(20,2) | System benchmark fee |
| `te_amount` | DECIMAL(20,2) | Calculated TE |
| `contracted_rate` | DECIMAL(10,2) | Contracted rate |
| `paid_currency` | VARCHAR(10) | Currency code |
| `exchange_rate` | DECIMAL(15,2) | Exchange rate |
| `use_user_fallback` | TINYINT(1) | User override flag |
| `user_benchmark_rate` | DECIMAL(10,2) | User override rate |
| `user_benchmark_fee` | DECIMAL(20,2) | User override fee |
| `user_te` | DECIMAL(20,2) | User TE |
| `user_fallback_reason` | VARCHAR(255) | Fallback reason |
| `user_comment` | TEXT | Comment |

---

## F. Benchmark Tables

### `bm_royalty_fees`
| Column | Type | Description |
|--------|------|-------------|
| `id` | PK | Auto-increment |
| `provision_code` | VARCHAR(50) | Provision code |
| `provision_name` | VARCHAR(255) | Provision name |
| `rate_percentage` | DECIMAL(5,2) | Benchmark rate % |
| `start_year` | SMALLINT | Effective start year |
| `end_year` | SMALLINT | Effective end year |
| `active` | TINYINT(1) | Active flag |

### `bm_land_concession`
Stores land concession rates by article/item/zone. Key columns: `article_no`, `article_name`, `item_no`, `item_name`, `rate_zone1`, `rate_zone2`, `rate_zone3`, `unit`, `start_year`.

### `bm_natural_resource`
Stores resource fee rates. Key columns: `item_no`, `item_name`, `rate_percentage`, `start_year`, `end_year`.

### `bm_sez_provisions`
SEZ provisions for Developer and Investor types. Columns: `type`, `provision_number`, `provision_name`, `description`, `rate`, etc.

---

## G. TE Result Tables

### `te_individual_result`
Stores PIT calculation results. Key columns: `tin`, `tax_year`, `te_amount`, `matched_provisions`, `calculated_at`.

### `te_asycuda_result`
Stores ASYCUDA calculation results. Key columns: `asycuda_id` (FK), `customs_te`, `excise_te`, `vat_te`, `total_te`, `calculated_at`.

### `te_land_concession_result`
Stores Land Concession TE results. Key columns: `company_id` (FK), `te_land_concession`, `calculated_at`.

---

## H. Report Tables

### `repo_gdp_revenue`
Stores GDP and Revenue values for TE/GDP and TE/Revenue reports. Columns: `gdp_year`, `gdp_value` (USD billions), `revenue_value` (kip), `note`.

---

## I. Batch ID Prefixes Reference

| Module | Prefix | Table | Batch Column |
|--------|--------|-------|-------------|
| CIT | `BATCH_` | `companies` | `import_batch_id` |
| PIT | `BATCH_` | `import_pit_data` | `batch_id` |
| Domestic VAT | `VAT_BATCH_` | `import_vat_data` | `batch_id` |
| SEZ Developer | `SEZDEV_BATCH_` | `import_sez_data` | `batch_id` |
| SEZ Investor | `SEZINV_BATCH_` | `import_sez_data` | `batch_id` |
| Land Concession | `LAND_BATCH_` | `repo_land_concession_data` | `import_batch_id` |
| Resource Fee | `RESOURCE_BATCH_` | `import_resource_data` | `batch_id` |
| Royalty Fee | `ROYALTY_BATCH_` | `import_royalty_data` | `batch_id` |
| ASYCUDA | `ASYCUDA_BATCH_` | `asycuda_imports` | `import_batch_id` |
