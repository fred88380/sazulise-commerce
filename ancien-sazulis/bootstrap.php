<?php
// /bootstrap.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ...existing code...
