<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class IntegrateShieldWithoutReplacingUsers extends Migration
{
    public function up(): void
    {
        // 1. Préserver l'ancien statut métier.
        $this->db->query("
            ALTER TABLE users
            CHANGE status account_status
            VARCHAR(20) NOT NULL DEFAULT 'active'
        ");

        // 2. Ajouter les colonnes attendues par Shield.
        $this->forge->addColumn('users', [
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status_message' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'last_active' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // 3. Activer les comptes déjà actifs.
        $this->db->query("
            UPDATE users
            SET active = CASE
                WHEN account_status = 'active' THEN 1
                ELSE 0
            END
        ");

        // 4. Créer les identités Shield.
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'secret' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'secret2' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'expires' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'extra' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'force_reset' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['type', 'secret']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            '',
            'CASCADE'
        );
        $this->forge->createTable('auth_identities', false, [
            'ENGINE' => 'InnoDB',
        ]);

        // 5. Créer les historiques de connexion.
        foreach (['auth_logins', 'auth_token_logins'] as $table) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'user_agent' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'id_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'identifier' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'date' => [
                    'type' => 'DATETIME',
                ],
                'success' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                ],
            ]);

            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['id_type', 'identifier']);
            $this->forge->addKey('user_id');
            $this->forge->createTable($table, false, [
                'ENGINE' => 'InnoDB',
            ]);
        }

        // 6. Créer les jetons de connexion persistante.
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'selector' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'hashedValidator' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'expires' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('selector');
        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            '',
            'CASCADE'
        );
        $this->forge->createTable('auth_remember_tokens', false, [
            'ENGINE' => 'InnoDB',
        ]);

        // 7. Créer les associations utilisateurs/groupes.
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'group' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            '',
            'CASCADE'
        );
        $this->forge->createTable('auth_groups_users', false, [
            'ENGINE' => 'InnoDB',
        ]);

        // 8. Créer les permissions individuelles.
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'permission' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            '',
            'CASCADE'
        );
        $this->forge->createTable('auth_permissions_users', false, [
            'ENGINE' => 'InnoDB',
        ]);

        // 9. Importer les e-mails et hashes existants.
        $users = $this->db->table('users')
            ->select('id, email, password_hash, created_at, updated_at')
            ->get()
            ->getResultArray();

        $identities = [];

        foreach ($users as $user) {
            $identities[] = [
                'user_id'    => $user['id'],
                'type'       => Session::ID_TYPE_EMAIL_PASSWORD,
                'secret'     => strtolower(trim($user['email'])),
                'secret2'    => $user['password_hash'],
                'force_reset' => 0,
                'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $user['updated_at'] ?? date('Y-m-d H:i:s'),
            ];
        }

        if ($identities !== []) {
            $this->db->table('auth_identities')->insertBatch($identities);
        }

        // 10. Importer les rôles comme groupes Shield.
        $this->db->query("
            INSERT INTO auth_groups_users (user_id, `group`, created_at)
            SELECT id, role, COALESCE(created_at, NOW())
            FROM users
            WHERE role IN ('admin', 'company_admin', 'traveler')
        ");
    }

    public function down(): void
    {
        // Attention : cette opération supprime les données Shield importées.
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('auth_permissions_users', true);
        $this->forge->dropTable('auth_groups_users', true);
        $this->forge->dropTable('auth_remember_tokens', true);
        $this->forge->dropTable('auth_token_logins', true);
        $this->forge->dropTable('auth_logins', true);
        $this->forge->dropTable('auth_identities', true);

        $this->forge->dropColumn('users', [
            'username',
            'status',
            'status_message',
            'active',
            'last_active',
            'deleted_at',
        ]);

        $this->db->query("
            ALTER TABLE users
            CHANGE account_status status
            VARCHAR(20) NOT NULL DEFAULT 'active'
        ");

        $this->db->enableForeignKeyChecks();
    }
}
