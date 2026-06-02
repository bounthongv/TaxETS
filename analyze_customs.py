import openpyxl
import json
import os

file_path = r"D:\Tax-ETS\docs\For print AHTN 2017 + NOR + WTO + ATIGA + AC+ AI+AANZ+AJ+AK+APTA+LV LATEST 12.9.19 .xlsx"

if not os.path.exists(file_path):
    print(f"Error: File not found at {file_path}")
    exit(1)

try:
    wb = openpyxl.load_workbook(file_path, data_only=True, read_only=True)
    sheet = wb.active
    
    rows = []
    # Read first 100 rows to catch Section 1, Chapter 1, and some items
    for row in sheet.iter_rows(min_row=1, max_row=100, values_only=True):
        rows.append(list(row))
        
    print(json.dumps(rows, indent=2))
except Exception as e:
    print(f"Error reading Excel: {e}")
