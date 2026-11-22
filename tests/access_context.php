<?php
// Read-only regression test: session context must use current database grants.
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
require APPPATH.'core/MY_Model.php';
require APPPATH.'models/User_model.php';
require APPPATH.'libraries/Access_control.php';
$fake = new stdClass();
$fake->db = DB($db['default'], TRUE);
$row = $fake->db->select('u.*')->from('users u')->join('user_roles ur', 'ur.user_id = u.id')
    ->join('roles r', 'r.id = ur.role_id')->where('r.slug', 'school_encoder')->where('u.status', 'active')->limit(1)->get()->row_array();
if ( ! $row) throw new RuntimeException('An active school encoder is required for this read-only check.');
$fake->session = new class {
    public $data = array();
    public function userdata($key) { return isset($this->data[$key]) ? $this->data[$key] : NULL; }
    public function set_userdata($key, $value) { $this->data[$key] = $value; }
    public function unset_userdata($keys) { foreach ($keys as $key) unset($this->data[$key]); }
};
$fake->load = new class {
    public function model($name) {
        $ci =& get_instance();
        $ci->$name = new $name();
        $ci->$name->db = $ci->db;
    }
};
function &get_instance() { global $fake; return $fake; }
$fake->session->data = array('uie_uid' => $row['id'], 'uie_password_version' => hash('sha256', $row['password_hash']),
    'uie_user' => array('permissions' => array('test.stale'), 'school_id' => 0, 'roles' => array()));
$access = new Access_control();
$context = $access->user();
if ( ! in_array('learners.create', $context['permissions'], TRUE)) throw new RuntimeException('Current learner permissions were not loaded.');
if (in_array('test.stale', $context['permissions'], TRUE)) throw new RuntimeException('Stale permissions retained.');
if ( ! in_array('school_encoder', $context['roles'], TRUE)) throw new RuntimeException('Current role was not loaded.');
$assignment = $fake->User_model->get_primary_assignment($row['id']);
if ((int) $context['school_id'] !== (int) $assignment['school_id']) throw new RuntimeException('Stale school assignment retained.');
echo "PASS: Existing session receives current learner entry permissions without signing in again\n";
echo "PASS: Stale permissions removed and current school assignment restored\n";
