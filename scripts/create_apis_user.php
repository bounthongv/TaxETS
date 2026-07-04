<?php
/** Create apis@example.com user and sync to Ubuntu */
$pdo = new PDO('mysql:host=localhost;dbname=tax_ets;charset=utf8mb4', 'root', '');
$pdo->exec("DELETE FROM users WHERE email IN ('trainer@example.com', 'apis@example.com')");
$hash = password_hash('trainer123', PASSWORD_BCRYPT);
$pdo->prepare('INSERT INTO users (name, email, password, position, role_id, active) VALUES (?, ?, ?, ?, ?, 1)')->execute([
    'APIS User', 'apis@example.com', $hash, 'Trainer / Tester', 2
]);
echo "Created: apis@example.com / trainer123 (ADMIN)\n";

// Export to file
$cmd = '"C:\\xampp\\mysql\\bin\\mysqldump" -u root --no-create-info --tables tax_ets users --where="email=\'apis@example.com\'" > /tmp/apis_user.sql 2>NUL';
system($cmd, $ret);
if ($ret === 0) {
    echo "Exported to /tmp/apis_user.sql\n";
    system('ssh apis.com.la "mysql -u admin -p' . escapeshellarg('Sql_admin@#2024') . ' tax_ets" < /tmp/apis_user.sql 2>&1', $ret2);
    if ($ret2 === 0) echo "Synced to Ubuntu.\n";
    else echo "Sync failed.\n";
}
