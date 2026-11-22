<?php
// CLI integration test: creates one isolated user, cleans up in finally.
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__).'/';
define('BASEPATH', $root.'system/');
define('APPPATH', $root.'application/');
define('ENVIRONMENT', 'testing');
require BASEPATH.'core/Common.php';
require APPPATH.'config/constants.php';
require APPPATH.'config/database.php';
require BASEPATH.'database/DB.php';
class CI_Model { public $db; }
require APPPATH.'models/Password_reset_model.php';
$model = new Password_reset_model();
$model->db = DB($db['default'], TRUE);
$dbh = $model->db;
function check($condition, $message) {
    if ( ! $condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
$identity = 'recovery-test-'.bin2hex(random_bytes(6));
$email = $identity.'@example.invalid';
$uid = NULL;
try {
    $dbh->insert('users', array('username' => $identity, 'email' => $email,
        'password_hash' => password_hash('Original-password-123', PASSWORD_BCRYPT),
        'first_name' => 'Recovery', 'last_name' => 'Test', 'status' => 'active'));
    $uid = $dbh->insert_id();
    check($model->issue('missing-'.$email) === NULL, 'Unknown account receives no token');
    $first = $model->issue($email);
    check(strlen($first['token']) === 64 && $model->valid($first['token']), 'Random token is valid');
    $stored = $dbh->where('user_id', $uid)->get('password_resets')->row_array();
    check($stored['token_hash'] === hash('sha256', $first['token']), 'Database stores token hash only');
    $second = $model->issue($email);
    check( ! $model->valid($first['token']), 'Replacement invalidates previous token');
    check($model->consume(str_repeat('0', 64), 'Changed-password-123') === FALSE, 'Forged token rejected');
    check($model->consume($second['token'], 'Changed-password-123') === (int) $uid, 'Valid token resets password');
    $user = $dbh->where('id', $uid)->get('users')->row_array();
    check(password_verify('Changed-password-123', $user['password_hash']), 'New password is securely hashed');
    check($model->consume($second['token'], 'Another-password-123') === FALSE, 'Used token cannot be replayed');
    $expired = $model->issue($email);
    $dbh->where('user_id', $uid)->update('password_resets', array('expires_at' => '2000-01-01 00:00:00'));
    check( ! $model->valid($expired['token']), 'Expired token rejected');
    $inactive = $model->issue($email);
    $dbh->where('id', $uid)->update('users', array('status' => 'inactive'));
    check($model->issue($email) === NULL, 'Inactive account cannot request token');
    check($model->consume($inactive['token'], 'Inactive-password-123') === FALSE, 'Deactivated account cannot redeem token');
    for ($i = 0; $i < 3; $i++) check($model->allow_request('test', $identity, 3), 'Request within limit allowed');
    check( ! $model->allow_request('test', $identity, 3), 'Rate limit enforced');
    check( ! $model->valid(array('bad')), 'Non-string token rejected safely');
    require APPPATH.'libraries/Access_control.php';
    $fake = new stdClass();
    $fake->db = $dbh;
    $fake->session = new class {
        public $data = array();
        public function userdata($key) { return isset($this->data[$key]) ? $this->data[$key] : NULL; }
        public function unset_userdata($keys) { foreach ($keys as $key) unset($this->data[$key]); }
    };
    function &get_instance() { global $fake; return $fake; }
    $dbh->where('id', $uid)->update('users', array('status' => 'active'));
    $fake->session->data = array('uie_uid' => $uid, 'uie_password_version' => hash('sha256', $user['password_hash']));
    $acl = new Access_control();
    check($acl->is_logged_in(), 'Current password session is accepted');
    $token = $model->issue($email);
    $model->consume($token['token'], 'Another-password-123');
    check( ! $acl->is_logged_in(), 'Password reset invalidates existing session');
} finally {
    if ($uid) $dbh->where('id', $uid)->delete('users');
    $bucket = hash('sha256', 'test:'.$identity.':'.(int) floor(time() / 900));
    $dbh->where('bucket_hash', $bucket)->delete('password_reset_limits');
}
