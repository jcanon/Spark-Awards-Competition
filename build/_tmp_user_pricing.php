<?php
$m = new mysqli('localhost','user_sparkawards','pass_sparkawards','sparkawards',3306);
if ($m->connect_error) { fwrite(STDERR, $m->connect_error . PHP_EOL); exit(1); }
$r = $m->query("SELECT DISTINCT user_type_pricing FROM comp_user_type ORDER BY user_type_pricing");
while ($row = $r->fetch_assoc()) { echo json_encode($row), PHP_EOL; }
?>