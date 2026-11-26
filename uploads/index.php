<?php
/**
 * Uploads folder guard
 *
 * Blocks direct browsing of the uploads directory (returns 403).
 */
http_response_code(403);
exit('Forbidden');
