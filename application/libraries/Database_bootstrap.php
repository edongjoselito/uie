<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Database_bootstrap
{
    public function initialize($db, $seed_defaults)
    {
        $schema = $this->statements(FCPATH.'db/schema.sql');
        $region = $this->statements(FCPATH.'db/seed_region11.sql');
        $migrations = array();
        foreach (glob(FCPATH.'db/migrations/*.sql') as $file)
        {
            $migrations = array_merge($migrations, $this->statements($file));
        }

        $definitions = array();
        foreach (array_merge($schema, $migrations) as $sql)
        {
            if (preg_match('/^CREATE TABLE (?:IF NOT EXISTS )?(uie_\w+)/i', $sql, $matches))
            {
                // Prefer the base definition if a migration defines the same table.
                $definitions[$matches[1]] = $definitions[$matches[1]] ?? $sql;
            }
        }

        $lock = 'uie-bootstrap-'.sha1($db->database);
        if ((int) $db->query('SELECT GET_LOCK(?, 15) AS acquired', array($lock))->row()->acquired !== 1)
        {
            throw new RuntimeException('Could not acquire the database setup lock.');
        }

        try
        {
            $tables = $this->tables($db);
            $missing = array_diff_key($definitions, $tables);
            foreach ($missing as $table => $sql)
            {
                $this->create_table($db, $table, $sql, $tables);
            }

            $create_accounts = $seed_defaults && $this->users_empty($db);
            if ($missing || $create_accounts)
            {
                $this->seed($db, $schema, $region, $migrations, $missing, $create_accounts);
            }
        }
        finally
        {
            $db->query('SELECT RELEASE_LOCK(?)', array($lock));
        }
    }

    private function statements($path)
    {
        $sql = file_get_contents($path);
        if ($sql === FALSE)
        {
            throw new RuntimeException('Could not read the database setup SQL.');
        }

        // The bundled SQL uses line comments and ends each statement on its own line.
        $sql = preg_replace('/^\h*--[^\r\n]*|(?<=;)\h*--[^\r\n]*/m', '', $sql);
        return array_filter(array_map('trim', preg_split('/;\h*(?:\R|$)/', $sql)));
    }

    private function tables($db)
    {
        $tables = array();
        foreach ($db->query('SHOW TABLES')->result_array() as $row)
        {
            $tables[reset($row)] = TRUE;
        }
        return $tables;
    }

    private function users_empty($db)
    {
        return $db->query('SELECT 1 FROM uie_users LIMIT 1')->num_rows() === 0;
    }

    private function create_table($db, $table, $sql, $existing)
    {
        // A table dropped with foreign key checks disabled can still have references.
        // Reserve their IDs so newly created rows cannot acquire those relationships.
        if (preg_match('/^\h+id\s+.*\bAUTO_INCREMENT\b/m', $sql))
        {
            $references = $db->query(
                'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_SCHEMA = DATABASE()
                   AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ?',
                array($table, 'id')
            )->result_array();

            $next_id = 1;
            foreach ($references as $reference)
            {
                if (isset($existing[$reference['TABLE_NAME']]))
                {
                    $column = $db->escape_identifiers($reference['COLUMN_NAME']);
                    $child = $db->escape_identifiers($reference['TABLE_NAME']);
                    $largest = $db->query("SELECT MAX($column) AS largest FROM $child")->row()->largest;
                    $next_id = max($next_id, (int) $largest + 1);
                }
            }

            if ($next_id > 1)
            {
                $sql .= ' AUTO_INCREMENT='.$next_id;
            }
        }

        $this->execute($db, $sql);
    }

    private function seed($db, $schema, $region, $migrations, $missing, $create_accounts)
    {
        $regional_tables = array_flip(array('uie_regions', 'uie_divisions', 'uie_schools', 'uie_system_settings'));
        $permission_tables = array_flip(array('uie_roles', 'uie_permissions', 'uie_role_permissions'));
        $seed_region = $create_accounts || array_intersect_key($missing, $regional_tables);

        if ( ! $db->trans_begin())
        {
            throw new RuntimeException('Could not start the database seed transaction.');
        }

        try
        {
            foreach ($schema as $sql)
            {
                if ( ! preg_match('/^INSERT INTO (uie_\w+)/i', $sql, $matches))
                {
                    continue;
                }

                $table = $matches[1];
                if (in_array($table, array('uie_users', 'uie_user_roles'), TRUE))
                {
                    if ($create_accounts)
                    {
                        $this->execute($db, $sql);
                    }
                }
                elseif (isset($missing[$table])
                    || ($seed_region && $table === 'uie_regions')
                    || ($create_accounts && isset($permission_tables[$table]))
                    || ($table === 'uie_role_permissions' && array_intersect_key($missing, $permission_tables)))
                {
                    $this->insert_defaults($db, $sql);
                }
            }

            if ($seed_region)
            {
                // SQL variables and inserts after @hash belong to the documented test accounts.
                foreach ($region as $sql)
                {
                    if (strpos($sql, 'SET @hash ') === 0)
                    {
                        if ( ! $create_accounts)
                        {
                            break;
                        }
                        $this->execute($db, $sql);
                    }
                    elseif (preg_match('/^INSERT INTO (uie_divisions|uie_schools|uie_system_settings)\b/i', $sql, $matches))
                    {
                        if ($create_accounts || isset($missing[$matches[1]]))
                        {
                            $this->insert_defaults($db, $sql);
                        }
                    }
                    elseif (preg_match('/^(?:SET @|INSERT INTO )/i', $sql))
                    {
                        $this->execute($db, $sql);
                    }
                }
            }

            if ($create_accounts || isset($missing['uie_learner_program_tags'])
                || array_intersect_key($missing, $permission_tables))
            {
                foreach ($migrations as $sql)
                {
                    if (preg_match('/^INSERT IGNORE INTO uie_(?:permissions|role_permissions)\b/i', $sql))
                    {
                        $this->execute($db, $sql);
                    }
                }
            }

            if ( ! $db->trans_commit())
            {
                throw new RuntimeException('Could not commit the database seed transaction.');
            }
        }
        catch (Throwable $error)
        {
            $db->trans_rollback();
            throw $error;
        }
    }

    private function insert_defaults($db, $sql)
    {
        $sql = preg_replace('/\s+ON DUPLICATE KEY UPDATE.*$/is', '', $sql);
        $this->execute($db, preg_replace('/^INSERT INTO /i', 'INSERT IGNORE INTO ', $sql));
    }

    private function execute($db, $sql)
    {
        if ($db->query($sql) === FALSE)
        {
            throw new RuntimeException('Database setup statement failed.');
        }
    }
}
