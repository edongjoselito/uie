<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Password_reset_model extends CI_Model
{
    /** Atomic, database-backed limits also apply to unknown email addresses. */
    public function allow_request($scope, $identity, $maximum)
    {
        $bucket = hash('sha256', $scope.':'.$identity.':'.(int) floor(time() / 900));
        $table = $this->db->dbprefix('password_reset_limits');
        $this->db->query("INSERT INTO `$table` (bucket_hash, attempts, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1",
            array($bucket, date('Y-m-d H:i:s', time() + 1800)));
        $row = $this->db->where('bucket_hash', $bucket)->get('password_reset_limits')->row_array();
        $this->db->where('expires_at <', date('Y-m-d H:i:s'))->delete('password_reset_limits');
        return $row && (int) $row['attempts'] <= $maximum;
    }

    public function issue($email)
    {
        $users = $this->db->dbprefix('users');
        $this->db->trans_begin();
        $user = $this->db->query("SELECT id, email FROM `$users` WHERE email = ? AND status IN ('active', 'locked') FOR UPDATE", array($email))->row_array();
        if ( ! $user)
        {
            $this->db->trans_rollback();
            return NULL;
        }
        $token = bin2hex(random_bytes(32));
        $this->db->replace('password_resets', array(
            'user_id' => $user['id'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 1800),
        ));
        if ( ! $this->db->trans_status())
        {
            $this->db->trans_rollback();
            return NULL;
        }
        $this->db->trans_commit();
        return array('email' => $user['email'], 'token' => $token);
    }

    public function valid($token)
    {
        if ( ! is_string($token) || ! preg_match('/\A[a-f0-9]{64}\z/D', $token)) return FALSE;
        return (bool) $this->db->where('token_hash', hash('sha256', $token))
            ->where('expires_at >', date('Y-m-d H:i:s'))->count_all_results('password_resets');
    }

    public function revoke($token)
    {
        $this->db->where('token_hash', hash('sha256', $token))->delete('password_resets');
    }

    /** Lock the account before consuming the token to prevent concurrent reuse. */
    public function consume($token, $password)
    {
        if ( ! $this->valid($token)) return FALSE;
        $hash = hash('sha256', $token);
        $reset = $this->db->where('token_hash', $hash)->get('password_resets')->row_array();
        if ( ! $reset) return FALSE;
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $users = $this->db->dbprefix('users');
        $resets = $this->db->dbprefix('password_resets');
        $this->db->trans_begin();
        $user = $this->db->query("SELECT id, status FROM `$users` WHERE id = ? FOR UPDATE", array($reset['user_id']))->row_array();
        $current = $this->db->query("SELECT user_id FROM `$resets` WHERE user_id = ? AND token_hash = ? AND expires_at > ? FOR UPDATE",
            array($reset['user_id'], $hash, date('Y-m-d H:i:s')))->row_array();
        if ( ! $user || $user['status'] === 'inactive' || ! $current)
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->where('id', $user['id'])->update('users', array(
            'password_hash' => $password_hash,
            'password_changed_at' => date('Y-m-d H:i:s'),
            'must_change_password' => 0,
            'status' => 'active', 'failed_attempts' => 0, 'locked_until' => NULL,
        ));
        $this->db->where('user_id', $user['id'])->delete('password_resets');
        if ( ! $this->db->trans_status())
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return (int) $user['id'];
    }
}
