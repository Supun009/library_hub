<?php
/**
 * Front Controller
 * 
 * This file acts as the entry point when the server is not configured
 * to point directly to the 'public' directory. It forwards control
 * to the public index file.
 */

// If the requests are for assets within public, we might want to let the webserver handle them,
// but since we are here, likely we are requesting the root or a route.

require_once __DIR__ . '/public/index.php';
