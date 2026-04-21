#!/bin/sh
set -eu

ENV_FILE="${1:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "Missing $ENV_FILE. Copy .env.presentation.example or run: cp .env.presentation.example .env" >&2
    exit 1
fi

get_env() {
    key="$1"
    value=$(grep -E "^${key}=" "$ENV_FILE" | tail -n 1 | sed "s/^${key}=//" | sed 's/^"//' | sed 's/"$//')
    printf '%s' "$value"
}

failures=0

require_value() {
    key="$1"
    label="$2"
    value=$(get_env "$key")

    if [ -z "$value" ] || [ "$value" = "null" ]; then
        echo "FAIL: $label ($key) is empty"
        failures=$((failures + 1))
        return
    fi

    case "$value" in
        *example*|your-*|*yourgmail*)
            echo "FAIL: $label ($key) still uses placeholder: $value"
            failures=$((failures + 1))
            ;;
        *)
            echo "OK: $label"
            ;;
    esac
}

expect_value() {
    key="$1"
    expected="$2"
    label="$3"
    value=$(get_env "$key")

    if [ "$value" = "$expected" ]; then
        echo "OK: $label"
    else
        echo "FAIL: $label ($key should be $expected, got ${value:-empty})"
        failures=$((failures + 1))
    fi
}

app_url=$(get_env APP_URL)

case "$app_url" in
    https://localhost*|http://localhost*|http://127.0.0.1*|https://127.0.0.1*|"")
        echo "FAIL: APP_URL must be your public https tunnel URL, got ${app_url:-empty}"
        failures=$((failures + 1))
        ;;
    https://*)
        echo "OK: APP_URL uses https public URL"
        ;;
    *)
        echo "FAIL: APP_URL must start with https://, got $app_url"
        failures=$((failures + 1))
        ;;
esac

expect_value APP_ENV production "APP_ENV is production for public demo"
expect_value APP_DEBUG false "APP_DEBUG is disabled"
expect_value MAIL_MAILER smtp "Mailer uses SMTP"
expect_value MAIL_HOST smtp.gmail.com "Gmail SMTP host"
expect_value MAIL_PORT 587 "Gmail SMTP port"
expect_value MIDTRANS_IS_PRODUCTION false "Midtrans remains sandbox"

require_value APP_KEY "Laravel app key"
require_value MAIL_USERNAME "Gmail SMTP username"
require_value MAIL_PASSWORD "Gmail App Password"
require_value MAIL_FROM_ADDRESS "Mail from address"

mail_username=$(get_env MAIL_USERNAME)
mail_from=$(get_env MAIL_FROM_ADDRESS)

if [ -n "$mail_username" ] && [ -n "$mail_from" ] && [ "$mail_username" != "$mail_from" ]; then
    echo "WARN: MAIL_FROM_ADDRESS differs from MAIL_USERNAME. Gmail may reject this unless alias is configured."
fi

if [ -n "$(get_env GOOGLE_CLIENT_ID)" ] || [ -n "$(get_env GOOGLE_CLIENT_SECRET)" ]; then
    google_redirect=$(get_env GOOGLE_REDIRECT_URI)
    expected_redirect="${app_url%/}/auth/google/callback"
    if [ "$google_redirect" = "$expected_redirect" ]; then
        echo "OK: Google redirect matches APP_URL"
    else
        echo "FAIL: GOOGLE_REDIRECT_URI should be $expected_redirect"
        failures=$((failures + 1))
    fi
else
    echo "OK: Google login left disabled for presentation"
fi

midtrans_callback=$(get_env MIDTRANS_CALLBACK_URL)
expected_midtrans="${app_url%/}/payments/midtrans/callback"

if [ "$midtrans_callback" = "$expected_midtrans" ] || [ "$midtrans_callback" = '"${APP_URL}/payments/midtrans/callback"' ] || [ "$midtrans_callback" = '${APP_URL}/payments/midtrans/callback' ]; then
    echo "OK: Midtrans callback points to public URL pattern"
else
    echo "FAIL: MIDTRANS_CALLBACK_URL should be $expected_midtrans"
    failures=$((failures + 1))
fi

if [ "$failures" -gt 0 ]; then
    echo "Presentation env check failed with $failures issue(s)."
    exit 1
fi

echo "Presentation env check passed."
