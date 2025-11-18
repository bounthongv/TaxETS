<?php

$page = $_GET['page'] ?? 'dashboard'; // Default to dashboard if no page is specified

$pageContent = '../src/Views/pages/' . $page . '.php';

require_once 'layout.php';

?>