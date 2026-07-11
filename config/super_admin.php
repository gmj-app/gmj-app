<?php

$emails = array_values(array_filter(array_map(
    fn (string $email): string => strtolower(trim($email)),
    explode(',', (string) env('SUPER_ADMIN_EMAILS', '')),
)));

return ['emails' => array_values(array_unique($emails))];
