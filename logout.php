<?php
require_once __DIR__ . '/auth.php';

logout();
flash_set('success', 'You have been signed out.');
header('Location: login.php');
exit;