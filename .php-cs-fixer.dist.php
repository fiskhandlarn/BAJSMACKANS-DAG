<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true);

return (new PhpCsFixer\Config())
    ->setRules(['@PER-CS' => true])
    ->setFinder($finder);
