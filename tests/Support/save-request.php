<?php

// Router for php -S in tests: saves each POSTed JSON body so a test can read
// what was posted, and answers 200.
// A post without the JSON content type is not saved, so the payload tests fail
// without it.
if (
    'POST' === $_SERVER['REQUEST_METHOD']
    && 'application/json' === ($_SERVER['CONTENT_TYPE'] ?? '')
) {
    file_put_contents(
        __DIR__ . '/../_output/request-body.json',
        file_get_contents('php://input'),
    );
}
