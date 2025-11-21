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
    if value is None: # Check for None first, as np.nan is already converted to None
        return default
    try:
        return int(float(str(value)))
    except (ValueError, TypeError):
        return default

# Helper to convert Excel column letter to 0-based index
def excel_col_to_index(col_letter):
    index = 0
    for char in col_letter.upper():
        index = index * 26 + (ord(char) - ord('A') + 1)
    return index - 1

def main(filepath):
    try:
        # Use openpyxl engine to handle .xlsx files and formulas
        df = pd.read_excel(filepath, sheet_name="System testing", engine='openpyxl', header=0) # Assuming headers are in the first row

        # --- NEW: Replace all NaN values in the DataFrame with None for JSON compatibility ---
        df = df.replace({np.nan: None})
        # --- END NEW ---

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
            "is_in_sez_specified_activity": "is_in_sez_specified_activity", # Assuming this is the header for column Y
            "is_public_benefit_income": "is_public_benefit_income", # Assuming this is the header for column Z
            "is_asset_rent_compliant": "is_asset_rent_compliant", # Assuming this is the header for column AA
            "is_real_estate_transfer": "is_real_estate_transfer", # Assuming this is the header for column AB
            "List of enterprises holding VAT system": "is_vat_holder",
            "date of re-invest": "reinvest_date",
            "Amount of total assets (billio Kip)": "total_assets_billion",
            "Annual turnover (billion Kip)": "annual_turnover_billion",
            "Numbers of staff": "staff_count",
            "Registration date of firms in Lao Stock Exchange": "stock_listing_date",
        }
        
        # Apply renaming for columns that exist
        existing_columns = {k: v for k, v in column_rename_map.items() if k in df.columns}
        df.rename(columns=existing_columns, inplace=True)

        results = []
        for index, row in df.iterrows():
            # Access columns by their new names or original names if not mapped
            tin = row.get('tin')
            calculation_year = row.get('calculation_year')

            if tin is None or calculation_year is None: # Check for None instead of pd.isna
                continue

            zone = None
            if row.get('zone_1') == 1: zone = 1
            elif row.get('zone_2') == 1: zone = 2
            elif row.get('zone_3') == 1: zone = 3

            ipl_flags = {}
            # --- MODIFIED: Corrected IPL activity flags mapping based on user input ---
            excel_ipl_col_map = {
                1: excel_col_to_index('AD'),
                2: excel_col_to_index('AE'),
                3: excel_col_to_index('AF'),
                4: excel_col_to_index('AG'),
                5: excel_col_to_index('AH'),
                6: excel_col_to_index('AI'),
                7: excel_col_to_index('AJ'),
                8: excel_col_to_index('AK'),
                9: excel_col_to_index('AL'),
            }
            for i in range(1, 10):
                col_idx = excel_ipl_col_map.get(i)
                if col_idx is not None and col_idx < len(df.columns):
                    value = df.iloc[index, col_idx] # Access by iloc
                    ipl_flags[f'activity_{i}'] = 1 if to_int_safe(value) == 1 else 0
                else:
                    ipl_flags[f'activity_{i}'] = 0 # Default to 0 for unmapped activities or out of bounds
            # --- END MODIFIED ---
            
            applied_te_ids = []
            te_columns_data = {}

            # Map Excel column letters to 0-based indices for TE columns
            te_col_indices = {
                'te_1': excel_col_to_index('AT'), 'te_2': excel_col_to_index('AU'), 'te_3': excel_col_to_index('AV'),
                'te_4': excel_col_to_index('AW'), 'te_5': excel_col_to_index('AX'), 'te_6': excel_col_to_index('AY'),
                'te_7': excel_col_to_index('AZ'), 'te_8': excel_col_to_index('BA'), 'te_9': excel_col_to_index('BB'),
                'te_10': excel_col_to_index('BC'), 'te_11': excel_col_to_index('BD'), 'te_12': excel_col_to_index('BE'),
                'te_13': excel_col_to_index('BF'), 'te_14': excel_col_to_index('BG'), 'te_15': excel_col_to_index('BH'),
                'te_16': excel_col_to_index('BI'), 'te_17': excel_col_to_index('BJ'), 'te_18': excel_col_to_index('BK'),
                'te_19': excel_col_to_index('BL'), 'te_20': excel_col_to_index('BM'),
                'te_other': excel_col_to_index('BN')
            }

            # Extract TE values using iloc (numerical index)
            for i in range(1, 21): # TE#1 to TE#20
                db_field = f'te_{i}'
                col_idx = te_col_indices[db_field]
                cell_value = df.iloc[index, col_idx] # Access by numerical index
                
                te_columns_data[db_field] = cell_value # Already None if it was NaN
                
                if cell_value is not None:
                    try:
                        int_val = int(float(str(cell_value).strip()))
                        if int_val > 0:      # prevent 0 from being included
                            applied_te_ids.append(int_val)
                    except:
                        pass


            # Handle TE Other (column BN)
            te_other_value = df.iloc[index, te_col_indices['te_other']]
            te_columns_data['te_other'] = te_other_value # Already None if it was NaN
            
            if te_other_value is not None and 'other' in str(te_other_value).lower(): # Check for None
                applied_te_ids.append(21) # TE ID for "Other"
            
            record = {
                "tin": str(tin),
                "company_name": row.get('company_name'),
                "calculation_year": to_int_safe(calculation_year),
                "revenue": clean_number(row.get('revenue')),
                "expense": clean_number(row.get('expense')),
                "pt_paid": clean_number(row.get('pt_paid')),
                "reinvested_profit_amount": clean_number(row.get('reinvested_profit_amount')),
                "reinvest_date": format_date(row.get('reinvest_date')),
                "province": row.get('province'),
                "district": row.get('district'), # This will now be None if it was NaN
                "sector": row.get('sector'),
                "zone": zone,
                "is_vat_holder": 1 if to_int_safe(row.get('is_vat_holder')) == 1 else 0, # Use to_int_safe
                "staff_count": to_int_safe(row.get('staff_count')),
                "total_assets_billion": clean_number(row.get('total_assets_billion')),
                "annual_turnover_billion": clean_number(row.get('annual_turnover_billion')),
                "investment_license_date": format_date(row.get('investment_license_date')),
                "date_first_revenue": format_date(row.get('date_first_revenue')),
                "registration_date": format_date(row.get('registration_date')),
                "stock_listing_date": format_date(row.get('stock_listing_date')),
                "tax_holiday_period_years": to_int_safe(row.get('tax_holiday_period_years')),
                "is_human_resource_dev": 1 if to_int_safe(row.get('is_human_resource_dev')) == 1 else 0, # Use to_int_safe
                "is_innovative_green_tech": 1 if to_int_safe(row.get('is_innovative_green_tech')) == 1 else 0, # Use to_int_safe
                "is_sez_developer": 1 if to_int_safe(row.get('is_sez_developer')) == 1 else 0, # Use to_int_safe
                "is_sez_investor": 1 if to_int_safe(row.get('is_sez_investor')) == 1 else 0, # Use to_int_safe
                "is_in_sez_specified_activity": 1 if to_int_safe(row.get('is_in_sez_specified_activity')) == 1 else 0, # Use to_int_safe
                "is_public_benefit_income": 1 if to_int_safe(row.get('is_public_benefit_income')) == 1 else 0, # Use to_int_safe
                "is_asset_rent_compliant": 1 if to_int_safe(row.get('is_asset_rent_compliant')) == 1 else 0, # Use to_int_safe
                "is_real_estate_transfer": 1 if to_int_safe(row.get('is_real_estate_transfer')) == 1 else 0, # Use to_int_safe
                "ipl_activity_flags": json.dumps(ipl_flags),
                "applied_te_ids_from_import": json.dumps(list(set(applied_te_ids)))
            }
            record.update(te_columns_data) # Add individual TE columns to the record
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