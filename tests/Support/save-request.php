<?php

// Router for php -S in tests: saves each POSTed body so a test can read what was posted, and answers 200.
if ('POST' === $_SERVER['REQUEST_METHOD']) {
    file_put_contents(__DIR__ . '/../_output/request-body.json', file_get_contents('php://input'));
}
