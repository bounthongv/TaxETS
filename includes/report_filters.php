<?php

function reportFilterInput(): array {
    $from = trim($_GET["import_from"] ?? "");
    $to = trim($_GET["import_to"] ?? "");

    return [
        "import_from" => preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : "",
        "import_to" => preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : "",
    ];
}

function reportDateParams(array $filters): array {
    $params = [];
    if (!empty($filters["import_from"])) {
        $params[] = $filters["import_from"] . " 00:00:00";
    }
    if (!empty($filters["import_to"])) {
        $params[] = $filters["import_to"] . " 23:59:59";
    }
    return $params;
}

function reportAppendFilters(array $params = []): string {
    $filters = reportFilterInput();
    foreach (["import_from", "import_to"] as $key) {
        if (!empty($filters[$key])) {
            $params[$key] = $filters[$key];
        }
    }
    return http_build_query($params);
}

function reportBatchDateExpression(string $alias, string $batchColumn, ?string $dateColumn = null): string {
    $batchExpr = "{$alias}.{$batchColumn}";
    $parsed = "STR_TO_DATE(REGEXP_SUBSTR({$batchExpr}, '[0-9]{14}'), '%Y%m%d%H%i%s')";

    if ($dateColumn) {
        return "COALESCE(NULLIF({$alias}.{$dateColumn}, '0000-00-00 00:00:00'), {$parsed})";
    }

    return $parsed;
}

function reportImportDateCondition(string $dateExpression, array $filters, array &$params): string {
    $conditions = [];
    if (!empty($filters["import_from"])) {
        $conditions[] = "{$dateExpression} >= ?";
        $params[] = $filters["import_from"] . " 00:00:00";
    }
    if (!empty($filters["import_to"])) {
        $conditions[] = "{$dateExpression} <= ?";
        $params[] = $filters["import_to"] . " 23:59:59";
    }
    return $conditions ? " AND " . implode(" AND ", $conditions) : "";
}

function reportImportDateFilterControl(string $resetPage, int $fromYear = 0, int $toYear = 0): string {
    $filters = reportFilterInput();
    ob_start();
    ?>
    <div class="col-md-2">
        <label class="form-label small fw-bold text-muted text-uppercase">Import From</label>
        <input type="date" name="import_from" class="form-control border-0 bg-light" value="<?= htmlspecialchars($filters["import_from"]) ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-bold text-muted text-uppercase">Import To</label>
        <input type="date" name="import_to" class="form-control border-0 bg-light" value="<?= htmlspecialchars($filters["import_to"]) ?>">
    </div>
    <?php
    return ob_get_clean();
}

function reportFetchKeyPair(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
    if ($params) {
        $stmt->execute($params);
    }
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function reportTaxTypeData(PDO $pdo, array $filters): array {
    $result = [];

    $queries = [
        "profit" => [
            "sql" => "SELECT c.tax_year, SUM(r.profit_tax_te) FROM companies c JOIN te_profit_result r ON r.company_id = c.id WHERE c.tax_year > 0",
            "date" => reportBatchDateExpression("c", "import_batch_id"),
            "group" => " GROUP BY c.tax_year",
        ],
        "pit" => [
            "sql" => "SELECT r.tax_year, SUM(r.te_amount) FROM te_individual_result r WHERE r.tax_year > 0",
            "date" => "(SELECT MAX(ipd.import_date) FROM import_pit_data ipd WHERE ipd.ptin COLLATE utf8mb4_unicode_ci = r.tin COLLATE utf8mb4_unicode_ci AND ipd.tax_year = r.tax_year)",
            "group" => " GROUP BY r.tax_year",
        ],
        "salary" => [
            "sql" => "SELECT s.tax_year, SUM(s.te_amount) FROM import_salary_tax_data s WHERE s.tax_year > 0 AND s.te_amount > 0",
            "date" => reportBatchDateExpression("s", "batch_id", "import_date"),
            "group" => " GROUP BY s.tax_year",
        ],
        "vat_domestic" => [
            "sql" => "SELECT YEAR(v.filing_period), SUM(v.expert_te) FROM import_vat_data v WHERE v.filing_period IS NOT NULL AND v.filing_period != '0000-00-00' AND YEAR(v.filing_period) > 0",
            "date" => reportBatchDateExpression("v", "batch_id", "import_date"),
            "group" => " GROUP BY YEAR(v.filing_period)",
        ],
        "customs" => [
            "sql" => "SELECT YEAR(COALESCE(NULLIF(ai.receipt_date, '0000-00-00'), NULLIF(ai.assess_date, '0000-00-00'), ai.doc_date)) AS report_year, SUM(r.customs_te) FROM te_asycuda_result r JOIN asycuda_imports ai ON r.asycuda_id = ai.id WHERE 1=1",
            "date" => reportBatchDateExpression("ai", "import_batch_id", "import_date"),
            "group" => " GROUP BY report_year HAVING report_year > 0",
        ],
        "excise" => [
            "sql" => "SELECT YEAR(COALESCE(NULLIF(ai.receipt_date, '0000-00-00'), NULLIF(ai.assess_date, '0000-00-00'), ai.doc_date)) AS report_year, SUM(r.excise_te) FROM te_asycuda_result r JOIN asycuda_imports ai ON r.asycuda_id = ai.id WHERE 1=1",
            "date" => reportBatchDateExpression("ai", "import_batch_id", "import_date"),
            "group" => " GROUP BY report_year HAVING report_year > 0",
        ],
        "vat_import" => [
            "sql" => "SELECT YEAR(COALESCE(NULLIF(ai.receipt_date, '0000-00-00'), NULLIF(ai.assess_date, '0000-00-00'), ai.doc_date)) AS report_year, SUM(r.vat_te) FROM te_asycuda_result r JOIN asycuda_imports ai ON r.asycuda_id = ai.id WHERE 1=1",
            "date" => reportBatchDateExpression("ai", "import_batch_id", "import_date"),
            "group" => " GROUP BY report_year HAVING report_year > 0",
        ],
        "sez_dev" => [
            "sql" => "SELECT s.tax_year, SUM(s.te_amount) FROM import_sez_data s WHERE s.type = 'Developer' AND s.tax_year > 0 AND s.te_amount > 0",
            "date" => reportBatchDateExpression("s", "batch_id", "import_date"),
            "group" => " GROUP BY s.tax_year",
        ],
        "sez_inv" => [
            "sql" => "SELECT s.tax_year, SUM(s.te_amount) FROM import_sez_data s WHERE s.type = 'Investor' AND s.tax_year > 0 AND s.te_amount > 0",
            "date" => reportBatchDateExpression("s", "batch_id", "import_date"),
            "group" => " GROUP BY s.tax_year",
        ],
        "resource" => [
            "sql" => "SELECT n.tax_year, SUM(n.te_amount) FROM import_resource_data n WHERE n.tax_year > 0 AND n.te_amount > 0",
            "date" => reportBatchDateExpression("n", "batch_id", "import_date"),
            "group" => " GROUP BY n.tax_year",
        ],
        "royalty" => [
            "sql" => "SELECT r.tax_year, SUM(r.te_amount) FROM import_royalty_data r WHERE r.tax_year > 0 AND r.te_amount > 0",
            "date" => reportBatchDateExpression("r", "batch_id", "import_date"),
            "group" => " GROUP BY r.tax_year",
        ],
        "land" => [
            "sql" => "SELECT l.tax_year, SUM(l.non_tax_te_usd) FROM repo_land_concession_data l WHERE l.tax_year > 0 AND l.non_tax_te_usd > 0",
            "date" => reportBatchDateExpression("l", "import_batch_id", "created_at"),
            "group" => " GROUP BY l.tax_year",
        ],
    ];

    foreach ($queries as $key => $query) {
        $params = [];
        $sql = $query["sql"] . reportImportDateCondition($query["date"], $filters, $params) . $query["group"];
        $result[$key] = reportFetchKeyPair($pdo, $sql, $params);
    }

    return $result;
}
