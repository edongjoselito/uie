<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Use an authenticated SMTP service. Never log reset links or SMTP credentials.
$config['smtp_host'] = getenv('UIE_SMTP_HOST') ?: '';
$config['smtp_port'] = (int) (getenv('UIE_SMTP_PORT') ?: 587);
$config['smtp_user'] = getenv('UIE_SMTP_USER') ?: '';
$config['smtp_pass'] = getenv('UIE_SMTP_PASS') ?: '';
$config['smtp_crypto'] = getenv('UIE_SMTP_CRYPTO') ?: 'tls';
$config['from_email'] = getenv('UIE_MAIL_FROM') ?: '';
