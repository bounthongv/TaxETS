# Directory Overview

This directory contains the planning documents and SQL scripts for a "Tax Expenditure Simulation" (TaxETS) system. The system is designed to calculate benchmark profit taxes and apply tax expenditure (TE) provisions to determine the final tax liability for companies.

The core logic is split into two main parts:
1.  **Benchmark Calculation:** Determining the theoretical tax a company should pay based on its size, sector, and the active tax regime for a given year.
2.  **TE Repository Application:** Applying specific tax breaks (exemptions, rate reliefs, deductions) from a repository to the benchmark tax to calculate the actual tax payable and the final TE amount.

The directory also contains reference documents in PDF format.

# Key Files

*   `1-benchmark_plan.txt`: Contains the detailed algorithm and a text-based flowchart for calculating the benchmark profit tax. It outlines the decision logic for classifying enterprises (Micro vs. Standard) and applying different tax calculation methods based on user inputs and company data.
*   `2-benchmark_sql.txt`: Provides the SQL schema and data for the benchmark calculation tables. This includes tables for tax regimes, sector-specific rates, mandatory estimation rates, and rules for micro-enterprises.
*   `3-repository_plan.txt`: Details the algorithm for the second half of the calculation—applying the TE provisions. It describes how the system finds eligible TEs for a company, selects the best one, and calculates the final tax liability. It also includes the database design for the TE repository.
*   `4-repository_sql.txt`: Contains the SQL schema and data for the TE repository. This includes tables for TE provisions, the conditions that trigger them, and the effects they have on the tax calculation.
*   `Benchmark by year_Profit Tax (1).pdf` & `TE_Workshop.pdf`: These appear to be reference documents, likely containing background information, legal texts, or workshop materials related to the tax expenditure project.

# Usage

The files in this directory are intended to be used as the blueprint for developing the TaxETS application.

1.  **Database Setup:** The `_sql.txt` files should be executed on a MySQL-compatible database (named `taxets`) to create the necessary tables and populate them with the initial rule-set.
2.  **Application Logic:** The `_plan.txt` files provide the step-by-step instructions for developers to implement the calculation engine. The algorithms describe the required inputs, database queries, decision points, and final calculations.
3.  **Reference:** The PDF files serve as background and reference material for understanding the context and legal basis of the tax rules being implemented.
