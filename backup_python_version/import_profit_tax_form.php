<?php
// src/Views/pages/import_profit_tax_form.php
?>

<div class="container-fluid">
    <h1>Import Data for Profit Tax TE Calculation</h1>
    <p class="lead">Please upload the complete XLSX file provided by the expert. The system will extract the necessary data to perform the TE calculation.</p>

    <?php
    // Display success or error messages
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'success') {
            echo '<div class="alert alert-success" role="alert">Data imported successfully!</div>';
        } elseif ($_GET['status'] === 'error') {
            $errorMessage = $_GET['message'] ?? 'An unknown error occurred.';
            echo '<div class="alert alert-danger" role="alert">Error: ' . htmlspecialchars($errorMessage) . '</div>';
        }
    }
    ?>

    <form action="./process_import_simple.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="profitTaxFile" class="form-label">Upload .xlsx File</label>
            <input class="form-control" type="file" id="profitTaxFile" name="profitTaxFile" accept=".xlsx" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload and Process File</button>
    </form>
</div>
