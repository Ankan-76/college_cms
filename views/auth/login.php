<?php
// views/auth/login.php — DEPRECATED: Redirect to landing page
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';

// This unified login page is deprecated. Redirect users to the main landing page
// where they can choose their specific portal.
redirect('/');
