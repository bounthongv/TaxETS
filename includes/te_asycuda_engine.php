<?php

class TEAsycudaEngine {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Run calculation for an ASYCUDA import batch.
     * Maps raw exempted values to the results table for reporting.
     */
    public function calculateBatch(string $batch_id): array {
        // 1. Clear existing results for this batch
        $deleteStmt = $this->pdo->prepare("
            DELETE tar FROM te_asycuda_result tar 
            JOIN asycuda_imports ai ON tar.asycuda_id = ai.id 
            WHERE ai.import_batch_id = ?
        ");
        $deleteStmt->execute([$batch_id]);

        // 2. Fetch records from imports
        $stmt = $this->pdo->prepare("SELECT * FROM asycuda_imports WHERE import_batch_id = ?");
        $stmt->execute([$batch_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_customs = 0.0;
        $total_excise = 0.0;
        $total_vat = 0.0;
        $count = 0;

        // 3. Persist calculation to results table
        $insertStmt = $this->pdo->prepare("
            INSERT INTO te_asycuda_result (asycuda_id, customs_te, excise_te, vat_te, total_te) 
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($records as $r) {
            $c_te = max(0, (float)($r['exemp_customs'] ?? 0) - (float)($r['paid_customs'] ?? 0));
            $e_te = max(0, (float)($r['exempt_excise'] ?? 0) - (float)($r['paid_excise'] ?? 0));
            $v_te = max(0, (float)($r['exempt_vat'] ?? 0) - (float)($r['paid_vat'] ?? 0));
            $total = $c_te + $e_te + $v_te;

            $insertStmt->execute([
                $r['id'],
                $c_te,
                $e_te,
                $v_te,
                $total
            ]);

            $total_customs += $c_te;
            $total_excise += $e_te;
            $total_vat += $v_te;
            $count++;
        }

        return [
            'calculated' => $count,
            'total_customs' => $total_customs,
            'total_excise' => $total_excise,
            'total_vat' => $total_vat,
            'total_te' => ($total_customs + $total_excise + $total_vat)
        ];
    }

    /**
     * Check if a batch has already been calculated.
     */
    public function isBatchCalculated(string $batch_id): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM te_asycuda_result tar 
            JOIN asycuda_imports ai ON tar.asycuda_id = ai.id 
            WHERE ai.import_batch_id = ?
        ");
        $stmt->execute([$batch_id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
