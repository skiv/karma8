<?php

include_once "common.php";

$users_count = 1000000;

/** @var mysqli $db_connect */
mysqli_query(
    $db_connect,
    "DROP TABLE IF EXISTS users;"
);
mysqli_query(
    $db_connect,
    "CREATE TABLE users (
        id INT auto_increment PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        validts TIMESTAMP NULL,
        confirmed BOOLEAN DEFAULT FALSE,
        inprogress BOOLEAN DEFAULT FALSE,
        lastts TIMESTAMP NULL    
    );"
);

mysqli_query(
    $db_connect,
    "DROP TABLE IF EXISTS emails;"
);
mysqli_query(
    $db_connect,
    "CREATE TABLE emails (
        id INT auto_increment PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        checked BOOLEAN DEFAULT FALSE,
        valid BOOLEAN DEFAULT FALSE,
        inprogress BOOLEAN DEFAULT FALSE
    );"
);

for ($i = 1; $i <= $users_count; $i++) {
    $validts = rand(0, 100) > 5 ? time() + rand(-15 * 86400, 30 * 86400) : null;
    $lastts = $validts && rand(0, 100) > 95 ? $validts - rand(0, 13 * 86400) : null;
    mysqli_query(
        $db_connect,
        "INSERT INTO users (username, email, validts, lastts, confirmed) VALUES ("
        . "'user" . $i . "',"
        . (rand(0, 100) > 3 ? "'email" . $i . "@example.com'," : "NULL,")
        . ($validts ? "'" . date('Y-m-d H:i:s', $validts) . "'" : "NULL") . ","
        . ($lastts ? "'" . date('Y-m-d H:i:s', $validts) . "'" : "NULL") . ","
        . rand(0, 1)
        . ");"
    );
    if (rand(0, 100) > 10) {
        $checked = rand(0, 1);
        mysqli_query(
            $db_connect,
            "INSERT INTO emails (email, checked, valid) VALUES ("
            . "'email" . $i . "@example.com',"
            . $checked . ","
            . (int) ($checked && rand(0, 1))
            . ");"
        );
    }
    echo '.';
    if ($i % 100 === 0) {
        echo " $i\n";
    }
}