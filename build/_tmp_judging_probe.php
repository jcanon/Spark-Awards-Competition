<?php
$m = new mysqli('localhost','user_sparkawards','pass_sparkawards','sparkawards',3306);
if ($m->connect_error) { fwrite(STDERR, "connect_error=" . $m->connect_error . PHP_EOL); exit(1); }
$q1 = $m->query("SELECT comp_id, comp_year, comp_type_id, shortlist_enabled, jury_phase_2_open, jury_phase_2_close FROM comp_competitions WHERE comp_id=1099");
while ($row = $q1->fetch_assoc()) { echo json_encode(['comp'=>$row]), PHP_EOL; }
$q2 = $m->query("SELECT entry_status, shortlist, COUNT(*) AS cnt FROM comp_entries WHERE comp_id=1099 GROUP BY entry_status, shortlist ORDER BY entry_status, shortlist");
while ($row = $q2->fetch_assoc()) { echo json_encode(['entry'=>$row]), PHP_EOL; }
?>