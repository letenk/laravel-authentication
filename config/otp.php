<?php

return [
    'length'                   => (int) env('OTP_LENGTH', 5),
    'ttl_minutes'              => (int) env('OTP_TTL_MINUTES', 10),
    'max_submit_attempt'       => (int) env('OTP_MAX_ATTEMPT', 5),
    'next_attempt_wait_minutes' => (int) env('OTP_NEXT_ATTEMPT_WAIT_MINUTES', 1),
];
