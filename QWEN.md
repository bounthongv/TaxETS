# Tax Expenditure Simulation (TaxETS) System

## Project Overview

TaxETS is a PHP and Python-based application designed to calculate tax expenditures (TE) for companies in Laos. The system imports company financial data from Excel files, calculates benchmark profit taxes based on enterprise size, sector, and tax regime, then applies tax expenditure provisions to determine final tax liability and calculate the actual tax expenditure amounts.

### Architecture
- **Backend**: PHP with MySQL database
- **Data Processing**: Python (pandas, openpyxl) for Excel file parsing
- **Frontend**: HTML/PHP with Bootstrap UI
- **Directory Structure**: Organized with `/public` for web assets, `/src` for PHP services/models/controllers, `/db_setup` for database initialization scripts

### Core Functionality
1. **Excel Data Import**: Python script (`parse_excel.py`) processes Excel files containing company data
2. **Benchmark Tax Calculation**: Calculates theoretical tax based on company profile and active tax regime
3. **TE Application**: Applies tax expenditure provisions from repository to calculate final tax liability
4. **Result Storage**: Stores calculation results in MySQL database

## Building and Running

### Requirements
- PHP 7.4+
- Python 3.7+
- MySQL 5.7+ or MariaDB
- Web server (Apache/Nginx)

### Setup Instructions

1. **Database Setup**:
   ```bash
   # Create the database and run the table creation script
   mysql -u username -p < db_setup/create_import_tables.php
   # Note: You may need to execute the PHP script to create the database tables
   ```

2. **Python Dependencies**:
   ```bash
   pip install -r requirements.txt
   # Or specifically: pip install pandas openpyxl
   ```

3. **PHP Dependencies**:
   ```bash
   # If using Composer (not currently in the project but may be added later)
   composer install
   ```

4. **Web Server Configuration**:
   - Point your web server to the `/public` directory
   - Ensure PHP and MySQL extensions are enabled

### Running the Application

1. **Import Excel Data**:
   ```bash
   python parse_excel.py <path_to_excel_file>
   ```

2. **Process Data**:
   - The PHP application (to be implemented) will interact with the database to perform calculations
   - The parsed JSON output from the Python script can be consumed by PHP services

3. **View Results**:
   - Access the web interface through your configured web server
   - Navigate through the menu to access benchmark calculations, TE repository, and reports

## Development Conventions

### Python Code
- Uses pandas for data processing
- Includes data cleaning functions (`clean_number`, `format_date`, etc.)
- Outputs JSON to stdout for consumption by PHP
- Handles different data types and formats from Excel files

### PHP Code
- Object-oriented approach with Services, Models, and Controllers
- Database interactions through Database service class
- Follows MVC pattern for organization

### Database Schema
- Two main tables:
  - `calculation_data_profit_tax`: Stores imported company data
  - `calculation_results_profit_tax`: Stores calculation results
- Uses proper indexing and foreign key relationships
- Supports different enterprise classifications (Micro vs. Standard)

### Data Processing
The system handles complex Excel file structures with specific column mappings defined in `parse_excel.py`. It can process various financial metrics, enterprise characteristics, and eligibility flags for different tax provisions.

## Project Planning and Documentation

The `/docs` directory contains comprehensive planning documents:
- `1-benchmark_plan.txt`: Algorithm for calculating benchmark tax
- `2-benchmark_sql.txt`: Database schema for benchmark calculations
- `3-repository_plan.txt`: Algorithm for applying TE provisions
- `4-repository_sql.txt`: Database schema for TE repository
- `5-*` files: Implementation action plans for different phases

The system is designed to handle both accounting holder and non-accounting holder enterprises, with different tax calculation methods for each type. It supports various enterprise characteristics like VAT holder status, zone classification, sector-specific rates, and special provisions under Investment Promotion Law (IPL).

## Qwen Added Memories
- Fixed the TaxETS import process by resolving the SQLSTATE[HY093] error in process_import.php. The issue was that the $companyData array contained extra fields not present in the database schema. Solution: Created a filtered data array with only fields that exist in the database table, then used that for the INSERT statement. Also corrected the column name cross_check_difference (with 2 's') and made sure it exists in the database schema.
