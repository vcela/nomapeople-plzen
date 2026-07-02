<?php
// Session-bound CSRF tokens for authenticated admin/ POST forms.
// Assumes a session is already started (auth_bootstrap()) by the caller.

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): void {
    echo '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token ?? '');
}
