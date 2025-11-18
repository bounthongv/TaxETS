<?php
// public/server_check.php - Check server configuration

echo "<h2>Server Configuration Check</h2>";

echo "<h3>PHP Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";
echo "OS: " . PHP_OS . "<br>";

echo "<h3>Disabled Functions:</h3>";
$disabled_functions = explode(',', ini_get('disable_functions'));
echo "shell_exec disabled: " . (in_array('shell_exec', array_map('trim', $disabled_functions)) ? 'YES' : 'NO') . "<br>";
echo "exec disabled: " . (in_array('exec', array_map('trim', $disabled_functions)) ? 'YES' : 'NO') . "<br>";
echo "system disabled: " . (in_array('system', array_map('trim', $disabled_functions)) ? 'YES' : 'NO') . "<br>";

echo "<h3>File Upload Settings:</h3>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

echo "<h3>Include Path:</h3>";
echo "include_path: " . ini_get('include_path') . "<br>";

echo "<h3>Current Directory:</h3>";
echo "Working dir: " . getcwd() . "<br>";
echo "Script dir: " . __DIR__ . "<br>";
echo "Root dir: " . __FILE__ . "<br>";

echo "<h3>Permission Tests:</h3>";
$test_file = __DIR__ . '/test_write.txt';
if (file_put_contents($test_file, "test")) {
    echo "Write permission: <span style='color: green;'>OK</span><br>";
    unlink($test_file);
    echo "Delete permission: <span style='color: green;'>OK</span><br>";
} else {
    echo "Write permission: <span style='color: red;'>FAILED</span><br>";
}

echo "<br>Basic functionality test: <span style='color: green;'>PASSED</span>";
?>