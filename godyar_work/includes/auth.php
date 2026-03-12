<?php

require_once __DIR__ . '/bootstrap.php';

if (!class_exists('Auth', false) && class_exists('Godyar\\Auth')) {
    class Auth extends \Godyar\Auth {}
}
