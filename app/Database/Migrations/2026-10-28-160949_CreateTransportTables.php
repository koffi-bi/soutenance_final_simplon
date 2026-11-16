<?php


namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransportTables extends Migration
{
    public function up()
    {
        /*
         * 1. TABLE DES VILLES
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'region' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('cities', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 2. TABLE DES VÉHICULES
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'registration_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'vehicle_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'bus',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('registration_number');
        $this->forge->addKey('company_id');

        $this->forge->addForeignKey(
            'company_id',
            'companies',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('vehicles', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 3. TABLE DES SIÈGES PHYSIQUES
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'seat_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'seat_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'standard',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey([
            'vehicle_id',
            'seat_number',
        ]);

        $this->forge->addForeignKey(
            'vehicle_id',
            'vehicles',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('seats', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 4. TABLE DES TRAJETS
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'origin_city_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'destination_city_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'estimated_duration_minutes' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey([
            'company_id',
            'origin_city_id',
            'destination_city_id',
        ]);
        $this->forge->addKey('origin_city_id');
        $this->forge->addKey('destination_city_id');

        $this->forge->addForeignKey(
            'company_id',
            'companies',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'origin_city_id',
            'cities',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'destination_city_id',
            'cities',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('routes', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 5. TABLE DES DÉPARTS PROGRAMMÉS
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'route_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'departure_at' => [
                'type' => 'DATETIME',
            ],
            'arrival_at' => [
                'type' => 'DATETIME',
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'scheduled',
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addKey('route_id');
        $this->forge->addKey('vehicle_id');
        $this->forge->addKey('departure_at');

        $this->forge->addForeignKey(
            'company_id',
            'companies',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'route_id',
            'routes',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'vehicle_id',
            'vehicles',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('trips', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        // Supprimer les tables dans l'ordre inverse des dépendances.
        $this->forge->dropTable('trips', true);
        $this->forge->dropTable('routes', true);
        $this->forge->dropTable('seats', true);
        $this->forge->dropTable('vehicles', true);
        $this->forge->dropTable('cities', true);
    }
}

