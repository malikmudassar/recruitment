<?php
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access Denied');
}
