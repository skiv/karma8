<?php

include_once 'common.php';

$sql = "FROM emails e INNER JOIN users u ON e.email = u.email WHERE e.checked = FALSE AND u.confirmed = TRUE AND u.validts > NOW() AND e.inprogress = FALSE";

/** @var mysqli $db_connect */
$count_unchecked = mysqli_query(
    $db_connect,
    "SELECT COUNT(e.id) $sql;"
)->fetch_row()[0];


if ($count_unchecked) {
    $count_processes = min(ceil($count_unchecked * CHECK_EMAIL_TIME / 86400), getenv('EMAIL_MAX_PROCESS'));

    for ($i = 1; $i <= $count_processes; $i++) {
        $pid = pcntl_fork();

        if (!$pid) {
            $db_connect = mysqli_connect(getenv('DB_HOSTNAME'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'),
                getenv('DB_DATABASE'));
            do {
                mysqli_query($db_connect, "SET AUTOCOMMIT = 0;");
                mysqli_query($db_connect, "BEGIN;");
                $data = mysqli_query(
                    $db_connect,
                    "SELECT e.id, e.email $sql LIMIT 1 FOR UPDATE;"
                )->fetch_assoc();

                if ($data) {
                    mysqli_query($db_connect, "UPDATE emails SET inprogress = 1 WHERE id = {$data['id']};");
                    mysqli_query($db_connect, "COMMIT;");

                    $valid = check_email($data['email']);

                    mysqli_query($db_connect, "BEGIN;");
                    mysqli_query($db_connect,
                        "UPDATE emails SET checked=1, valid=$valid, inprogress = 0 WHERE id = {$data['id']};");
                    mysqli_query($db_connect, "COMMIT;");

                    echo "$i - Email #{$data['id']} {$data['email']} was checked. Result: $valid\n";
                }
            } while ($data);

            exit($i);
        }
    }

    while (pcntl_waitpid(0, $status) != -1) {
        $status = pcntl_wexitstatus($status);

        echo "Child $status completed\n";
    }
}
