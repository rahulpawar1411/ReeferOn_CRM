<?php
/**
 * Documents folder guard
 *
 * Blocks direct browsing of uploaded document files (returns 403).
 */
http_response_code(403);
exit('Forbidden');
