<?php

include_once 'common.php';

$sql = "FROM users u 
        LEFT JOIN emails e ON e.email = u.email 
        WHERE u.email IS NOT NULL 
            AND u.validts > NOW() 
            AND u.validts < (NOW() + INTERVAL 3 DAY) 
            AND (u.lastts IS NULL OR u.lastts < (NOW() + INTERVAL 3 DAY)) 
            AND u.confirmed = TRUE 
            AND u.inprogress = FALSE";

/** @var mysqli $db_connect */
[$count_need_to_send, $count_checked] = mysqli_query(
    $db_connect,
    "SELECT COUNT(u.id), SUM(e.checked) $sql AND (e.id IS NULL OR e.checked = FALSE OR e.valid = TRUE)"
)->fetch_row();

if ($count_need_to_send) {
    $count_processes = min(
        ceil((($count_need_to_send - $count_checked) * CHECK_EMAIL_TIME + $count_need_to_send * SEND_EMAIL_TIME) / 86400),
        getenv('EMAIL_MAX_PROCESS')
    );

    for ($i = 1; $i <= $count_processes; $i++) {
        $pid = pcntl_fork();

        if (!$pid) {
            echo "Child $i started\n";
            $db_connect = mysqli_connect(getenv('DB_HOSTNAME'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'),
                getenv('DB_DATABASE'));
            do {
                mysqli_query($db_connect, "SET AUTOCOMMIT = 0;");
                mysqli_query($db_connect, "BEGIN;");
                $data = mysqli_query(
                    $db_connect,
                    "SELECT u.id, u.username, u.email, e.id AS eid, e.checked, e.valid, e.inprogress $sql AND (e.id IS NULL OR (e.inprogress = FALSE AND (e.checked = FALSE OR e.valid = TRUE))) LIMIT 1 FOR UPDATE;"
                )->fetch_assoc();

                if ($data) {
                    mysqli_query($db_connect, "UPDATE users SET inprogress = 1 WHERE id = {$data['id']};");
                    if ($data['eid'] && !$data['checked']) {
                        mysqli_query($db_connect, "UPDATE emails SET inprogress = 1 WHERE id = {$data['eid']};");
                    }
                    mysqli_query($db_connect, "COMMIT;");

                    if (!$data['eid'] || !$data['checked']) {
                        $valid = check_email($data['email']);
                        echo "$i - Email #{$data['id']} {$data['email']} was checked. Result: $valid\n";
                    } else {
                        $valid = $data['valid'];
                    }

                    if ($valid) {
                        send_email(
                            $data['email'],
                            getenv('EMAIL_EMAIL'),
                            $data['email'],
                            'Your subscription is expiring',
                            $data['username'] . ', your subscription is expiring soon'
                        );
                        echo "$i - Email #{$data['id']} {$data['email']} was send.\n";
                    }

                    mysqli_query($db_connect, "BEGIN;");
                    mysqli_query(
                        $db_connect,
                        "UPDATE users SET lastts=NOW(), inprogress = 0 WHERE id = {$data['id']};"
                    );
                    if ($data['eid'] && !$data['checked']) {
                        mysqli_query(
                            $db_connect,
                            "UPDATE emails SET checked=1, valid=$valid, inprogress = 0 WHERE id = {$data['eid']};"
                        );
                    } elseif (!$data['eid']) {
                        $checked = rand(0, 1);
                        mysqli_query(
                            $db_connect,
                            "INSERT INTO emails (email, checked, valid) VALUES ({$data['email']}, 1, $valid);"
                        );
                    }
                    mysqli_query($db_connect, "COMMIT;");
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
