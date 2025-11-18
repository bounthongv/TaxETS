<?php
// src/Views/pages/import_profit_tax.php
?>

<div class="container-fluid">
    <h1>Import Profit Tax Data</h1>

    <form action="../process_import_simple.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="profitTaxFile" class="form-label">Upload .xlsx File</label>
            <input class="form-control" type="file" id="profitTaxFile" name="profitTaxFile" accept=".xlsx" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload and Process</button>
    </form>
</div>
