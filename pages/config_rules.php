<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();
$message = '';
$msg_type = 'success';

$provision_id = isset($_GET['provision_id']) ? (int)$_GET['provision_id'] : 0;
if (!$provision_id) { die("Invalid Provision ID"); }

$stmt = $pdo->prepare("SELECT * FROM profit_provisions WHERE id = ?");
$stmt->execute([$provision_id]);
$provision = $stmt->fetch();
if (!$provision) { die("Provision not found"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'add_rule') {
            $field = !empty($_POST['custom_field_name']) ? $_POST['custom_field_name'] : $_POST['field_name'];
            $stmt = $pdo->prepare("INSERT INTO profit_provision_conditions (provision_id, field_name, operator, value_1, value_2) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$provision_id, $field, $_POST['operator'], $_POST['value_1'], $_POST['value_2'] !== '' ? $_POST['value_2'] : null]);
            $message = "Rule condition added.";
        } elseif ($_POST['action'] == 'delete_rule') {
            $pdo->prepare("DELETE FROM profit_provision_conditions WHERE id = ?")->execute([$_POST['id']]);
            $message = "Rule deleted.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $msg_type = 'danger';
    }
}

$stmt = $pdo->prepare("SELECT * FROM profit_provision_conditions WHERE provision_id = ? ORDER BY id ASC");
$stmt->execute([$provision_id]);
$rules = $stmt->fetchAll();

$available_fields = [
    'is_vat_holder' => 'Is VAT Holder (1/0)',
    'staff_count' => 'Number of Staff',
    'total_assets' => 'Total Assets (LAK)',
    'revenue' => 'Total Revenue (LAK)',
    'activity_type' => 'Activity Type (text)',
    'zone_1' => 'In Zone 1 (1/0)',
    'zone_2' => 'In Zone 2 (1/0)',
    'zone_3' => 'In Zone 3 (1/0)',
    'registration_date' => 'Registration Date',
    'investment_license_date' => 'Investment License Date',
];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row mb-3">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h2><a href="config_provisions.php" class="text-dark text-decoration-none"><i class="fas fa-arrow-left me-2"></i></a>Rule Builder: Provision <?= htmlspecialchars($provision['provision_number']) ?></h2>
      <p class="text-muted"><strong><?= htmlspecialchars($provision['legal_reference']) ?></strong> — <?= htmlspecialchars($provision['description']) ?></p>
    </div>
    <div>
      <?php if ($provision['is_exemption']): ?><span class="badge bg-success fs-6 px-3 py-2">Target: Exemption (0%)</span>
      <?php elseif ($provision['target_rate'] !== null): ?><span class="badge bg-info text-dark fs-6 px-3 py-2">Target Rate: <?= $provision['target_rate'] ?>%</span><?php endif; ?>
    </div>
  </div>
</div>
<?php if ($message): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> All rules below use <strong>AND</strong> logic — every condition must be TRUE for this provision to apply to a company.</div>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header bg-white fw-bold"><i class="fas fa-list-ul me-2"></i> Active Condition Rules</div>
      <div class="card-body p-0">
        <?php if (empty($rules)): ?>
          <div class="p-4 text-center text-muted"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>No rules configured. Add conditions on the right.</div>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($rules as $i => $rule): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
              <div>
                <span class="badge bg-secondary me-2">Rule <?= $i+1 ?></span>
                <code class="fs-6 text-primary"><?= htmlspecialchars($rule['field_name']) ?></code>
                <span class="badge bg-dark mx-2"><?= htmlspecialchars($rule['operator']) ?></span>
                <code class="fs-6 text-success"><?= htmlspecialchars($rule['value_1']) ?></code>
                <?php if ($rule['operator'] == 'BETWEEN'): ?>
                  <span class="mx-2 text-muted">AND</span>
                  <code class="fs-6 text-success"><?= htmlspecialchars($rule['value_2']) ?></code>
                <?php endif; ?>
              </div>
              <form method="POST" class="m-0" onsubmit="return confirm('Delete this rule?')">
                <input type="hidden" name="action" value="delete_rule">
                <input type="hidden" name="id" value="<?= $rule['id'] ?>">
                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
              </form>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="card bg-light">
      <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Add New Condition</h5></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add_rule">
          <div class="mb-3">
            <label class="form-label fw-bold">Company Field</label>
            <select name="field_name" id="fieldSelect" class="form-select" required>
              <option value="">Select a field...</option>
              <?php foreach ($available_fields as $key => $label): ?>
              <option value="<?= $key ?>"><?= $label ?></option>
              <?php endforeach; ?>
              <option value="__custom__">-- Custom Field Name --</option>
            </select>
            <input type="text" name="custom_field_name" id="customField" class="form-control mt-2 d-none" placeholder="Type DB column name...">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Operator</label>
            <select name="operator" id="operatorSelect" class="form-select" required>
              <option value="=">=  (Equals)</option>
              <option value=">=">&gt;= (Greater or equal)</option>
              <option value="<=">&lt;= (Less or equal)</option>
              <option value=">">&gt; (Greater than)</option>
              <option value="<">&lt; (Less than)</option>
              <option value="BETWEEN">BETWEEN (Range)</option>
              <option value="YEARS_PASSED_LESS_THAN">Years Since Date &lt; X (Tax Holiday)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold opacity-75">Value</label>
            <input type="text" name="value_1" class="form-control font-monospace" required placeholder="e.g. 1, 50, 1500000000">
          </div>
          <div class="mb-4 d-none" id="value2Block">
            <label class="form-label fw-bold opacity-75">Value 2 (BETWEEN)</label>
            <input type="text" name="value_2" id="value2" class="form-control font-monospace" placeholder="Upper bound">
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> Add Rule</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('operatorSelect').addEventListener('change', function() {
    const v2 = document.getElementById('value2Block');
    const v2i = document.getElementById('value2');
    if (this.value === 'BETWEEN') {
        v2.classList.remove('d-none'); v2i.setAttribute('required','required');
    } else {
        v2.classList.add('d-none'); v2i.removeAttribute('required');
    }
});
document.getElementById('fieldSelect').addEventListener('change', function() {
    const cf = document.getElementById('customField');
    if (this.value === '__custom__') { cf.classList.remove('d-none'); cf.setAttribute('required','required'); this.removeAttribute('required'); }
    else { cf.classList.add('d-none'); cf.removeAttribute('required'); this.setAttribute('required','required'); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
