<?php
/**
 * BuzzarFeed - Logout Handler
 * 
 * Handles user logout and session cleanup
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\Helpers;

// Start session
Session::start();

// Destroy session
Session::destroy();

// Set flash message
Session::start();
Session::setFlash('You have been logged out successfully.', 'success');

// Redirect to homepage
Helpers::redirect('index.php');
