<?php

function fromBatchHub(): bool {
    return ($_GET["from"] ?? "") === "batches";
}

function batchHubBackButton(): string {
    if (!fromBatchHub()) {
        return "";
    }

    return '<a href="batches.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Batch Management</a>';
}

function batchHubParam(): string {
    return fromBatchHub() ? "&from=batches" : "";
}
