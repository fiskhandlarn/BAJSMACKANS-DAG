<?php

// Router for php -S in tests: redirects / to /moved, which answers 200, so following the redirect would look like success.
if ('/' === $_SERVER['REQUEST_URI']) {
    header('Location: /moved', true, 302);
}
