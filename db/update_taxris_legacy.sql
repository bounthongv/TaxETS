USE tax_ets;

-- Expand repo_taxris with legacy system fields
ALTER TABLE repo_taxris
ADD COLUMN te_dummy VARCHAR(255) AFTER tax_paid,
ADD COLUMN is_public_income TINYINT(1) DEFAULT 2,
ADD COLUMN is_asset_rent TINYINT(1) DEFAULT 2,
ADD COLUMN is_real_estate_transfer TINYINT(1) DEFAULT 2,
ADD COLUMN is_vat_enterprise TINYINT(1) DEFAULT 2,
ADD COLUMN reinvest_date DATE AFTER reinvest_net_profit,
ADD COLUMN total_assets_bn DECIMAL(20, 2) DEFAULT 0,
ADD COLUMN annual_turnover_bn DECIMAL(20, 2) DEFAULT 0;
