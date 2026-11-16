<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeLegacyUserCredentialsNullable extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('users', [
            'email' => [
                'name'       => 'email',
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'password_hash' => [
                'name'       => 'password_hash',
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);
    }

    public function down(): void
    {
        // Vérifier qu'aucune valeur NULL n'existe avant le retour.
        $count = $this->db->table('users')
            ->groupStart()
                ->where('email', null)
                ->orWhere('password_hash', null)
            ->groupEnd()
            ->countAllResults();

        if ($count > 0) {
            throw new \RuntimeException(
                'Impossible de restaurer NOT NULL : certains utilisateurs ont des valeurs NULL.'
            );
        }

        $this->forge->modifyColumn('users', [
            'email' => [
                'name'       => 'email',
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'password_hash' => [
                'name'       => 'password_hash',
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
        ]);
    }
}

