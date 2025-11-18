# parse_excel.py
# To be called from PHP.
# This script reads an Excel file, processes the data, and prints a JSON string to stdout.
#
# Pre-requisites: You must have python and pandas installed.
# pip install pandas openpyxl
#
import pandas as pd
import numpy as np
import sys
import json
import re

def clean_number(value):
    if pd.isna(value) or value == '':
        return None
    s = str(value)
    s = re.sub(r"[^-0-9.]", "", s)
    if s.count('.') > 1:
        parts = s.split('.')
        s = parts[0] + '.' + ''.join(parts[1:])
    if s == '' or s == '-':
        return None
    try:
        return float(s)
    except ValueError:
        return None

def format_date(value):
    if pd.isna(value):
        return None
    try:
        return pd.to_datetime(value).strftime('%Y-%m-%d')
    except (ValueError, TypeError):
        return None

def to_int_safe(value, default=0):
    if pd.isna(value):
        return default
    try:
        return int(float(value))
    except (ValueError, TypeError):
        return default

def main(filepath):
    try:
        # Use openpyxl engine to handle .xlsx files and formulas
        df = pd.read_excel(filepath, sheet_name="System testing", engine='openpyxl')

        # Rename columns for easier access, handling potential errors if columns don't exist
        column_rename_map = {
            "Year": "calculation_year",
            "Investment License date": "investment_license_date",
            "date first revenue": "date_first_revenue",
            "Name of enterprise / branch representative": "company_name",
            "TIN": "tin",
            "Province": "province",
            "District": "district",
            "Zone 1": "zone_1",
            "Zone 2": "zone_2",
            "Zone 3": "zone_3",
            "Sector": "sector",
            "Revenue": "revenue",
            "Expense": "expense",
            "Re-invest net profit": "reinvested_profit_amount",
            "PT paid": "pt_paid",
            "Tax holiday period (years)": "tax_holiday_period_years",
            "Date of Registration License": "registration_date",
            "Businesses engaged in human resource development": "is_human_resource_dev",
            "Businesses that apply innovative technologies that are environmentally friendly, resource-efficient, and use clean energy for production": "is_innovative_green_tech",
            "SEZ developer": "is_sez_developer",
            "SEZ investor": "is_sez_investor",
            "Activities on production industry, services on tourism industry development, services in public health, education, sport and physical activity, and real estate development": "is_in_sez_specified_activity",
            "Income from activities that provide a public benefit or social purpose, such as art, sport etc": "is_public_benefit_income",
            "Rent from the assets of a business operator who complies with their income tax obligations": "is_asset_rent_compliant",
            "Income from the transfer of real estate rights recorded in the balance sheet of a business operator": "is_real_estate_transfer",
            "Activity 1 of Art. 9, IPL": "ipl_activity_1",
            "Activity 2 of Art. 9, IPL": "ipl_activity_2",
            "Activity 3 of Art. 9, IPL": "ipl_activity_3",
            "Activity 4 of Art. 9, IPL": "ipl_activity_4",
            "Activity 5 of Art. 9, IPL": "ipl_activity_5",
            "Activity 6 of Art. 9, IPL": "ipl_activity_6",
            "Activity 7 of Art. 9, IPL": "ipl_activity_7",
            "Activity 8 of Art. 9, IPL": "ipl_activity_8",
            "Activity 9 of Art. 9, IPL": "ipl_activity_9",
            "List of enterprises holding VAT system": "is_vat_holder",
            "date of re-invest": "reinvest_date",
            "Amount of total assets (billio Kip)": "total_assets_billion",
            "Annual turnover (billion Kip)": "annual_turnover_billion",
            "Numbers of staff": "staff_count",
            "Registration date of firms in Lao Stock Exchange": "stock_listing_date",
            "TE#": "applied_te_ids_from_import"
        }
        
        existing_columns = {k: v for k, v in column_rename_map.items() if k in df.columns}
        df.rename(columns=existing_columns, inplace=True)

        results = []
        for index, row in df.iterrows():
            if pd.isna(row.get('tin')) or pd.isna(row.get('calculation_year')):
                continue

            zone = None
            if row.get('zone_1') == 1: zone = 1
            elif row.get('zone_2') == 1: zone = 2
            elif row.get('zone_3') == 1: zone = 3

            ipl_flags = {}
            for i in range(1, 10):
                col_name = f'ipl_activity_{i}'
                ipl_flags[f'activity_{i}'] = 1 if row.get(col_name) == 1 else 0
            
            applied_te_ids = []
            raw_te_string = row.get('applied_te_ids_from_import')
            if raw_te_string and not pd.isna(raw_te_string):
                te_strings = str(raw_te_string).split(',')
                for te_string in te_strings:
                    trimmed_te_string = te_string.strip()
                    if 'other' in trimmed_te_string.lower():
                        applied_te_ids.append(21)
                    elif trimmed_te_string.isnumeric():
                        applied_te_ids.append(int(trimmed_te_string))
            
            record = {
                "tin": str(row.get('tin')),
                "company_name": row.get('company_name'),
                "calculation_year": to_int_safe(row.get('calculation_year')),
                "revenue": clean_number(row.get('revenue')),
                "expense": clean_number(row.get('expense')),
                "pt_paid": clean_number(row.get('pt_paid')),
                "reinvested_profit_amount": clean_number(row.get('reinvested_profit_amount')),
                "reinvest_date": format_date(row.get('reinvest_date')),
                "province": row.get('province'),
                "district": row.get('district'),
                "sector": row.get('sector'),
                "zone": zone,
                "is_vat_holder": 1 if row.get('is_vat_holder') == 1 else 0,
                "staff_count": to_int_safe(row.get('staff_count')),
                "total_assets_billion": clean_number(row.get('total_assets_billion')),
                "annual_turnover_billion": clean_number(row.get('annual_turnover_billion')),
                "investment_license_date": format_date(row.get('investment_license_date')),
                "date_first_revenue": format_date(row.get('date_first_revenue')),
                "registration_date": format_date(row.get('registration_date')),
                "stock_listing_date": format_date(row.get('stock_listing_date')),
                "tax_holiday_period_years": to_int_safe(row.get('tax_holiday_period_years')),
                "is_human_resource_dev": 1 if row.get('is_human_resource_dev') == 1 else 0,
                "is_innovative_green_tech": 1 if row.get('is_innovative_green_tech') == 1 else 0,
                "is_sez_developer": 1 if row.get('is_sez_developer') == 1 else 0,
                "is_sez_investor": 1 if row.get('is_sez_investor') == 1 else 0,
                "is_in_sez_specified_activity": 1 if row.get('is_in_sez_specified_activity') == 1 else 0,
                "is_public_benefit_income": 1 if row.get('is_public_benefit_income') == 1 else 0,
                "is_asset_rent_compliant": 1 if row.get('is_asset_rent_compliant') == 1 else 0,
                "is_real_estate_transfer": 1 if row.get('is_real_estate_transfer') == 1 else 0,
                "ipl_activity_flags": json.dumps(ipl_flags),
                "applied_te_ids_from_import": json.dumps(list(set(applied_te_ids)))
            }
            results.append(record)
        
        print(json.dumps(results, indent=4))

    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python parse_excel.py <path_to_excel_file>", file=sys.stderr)
        sys.exit(1)
    main(sys.argv[1])
