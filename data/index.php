<?php
// Fallback protection: prevent any PHP execution inside the data directory.
// The .htaccess file is the primary barrier; this file acts as a safety net
// for servers that do not honour .htaccess directives.
die('Direct access not permitted.');
