<?php
header('Content-Type: application/json');
echo json_encode([
  'DATABASE_URL' => getenv('DATABASE_URL')
], JSON_PRETTY_PRINT);
?>
