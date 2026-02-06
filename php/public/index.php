<?php

require_once __DIR__ . '/../core/Database.php';

$db = Database::connect();


$stmt = $db->query("SELECT * FROM Buyer LIMIT 1");
$buyer = $stmt->fetch();

if ($buyer) {
    echo "Connected ✔<br>";
    echo "Buyer name: " . $buyer['name'] . "<br>";
} else {
    echo "Connected ✔ but no invoices found.";
}

?>