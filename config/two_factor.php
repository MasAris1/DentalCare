<?php

return [
    'login_otp' => [
        'digits' => (int) env('LOGIN_OTP_DIGITS', 6),
        'expires_minutes' => (int) env('LOGIN_OTP_EXPIRES_MINUTES', 10),
        'max_attempts' => (int) env('LOGIN_OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('LOGIN_OTP_RESEND_COOLDOWN_SECONDS', 60),
    ],
];
