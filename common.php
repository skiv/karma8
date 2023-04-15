<?php

const CHECK_EMAIL_TIME = 60;
const SEND_EMAIL_TIME = 10;

function check_email(string $email)
{
    sleep(rand(1, CHECK_EMAIL_TIME));

    return rand(0,1);
}

function send_email(string $email, string $from, string $to, string $subj, string $body)
{
    sleep(rand(1, SEND_EMAIL_TIME));
}

$db_connect = mysqli_connect(getenv('DB_HOSTNAME'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'));

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}