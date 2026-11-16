<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReservationTables extends Migration
{
    public function up()
    {
        /*
         * 1. RÉSERVATIONS
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'trip_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'number_of_seats' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'reserved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_at' => [
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey('user_id');
        $this->forge->addKey('trip_id');
        $this->forge->addKey('status');

        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'trip_id',
            'trips',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('reservations', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 2. SIÈGES ASSOCIÉS AUX RÉSERVATIONS
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reservation_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'trip_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'seat_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        // Un siège ne peut apparaître qu'une fois pour un départ donné.
        $this->forge->addUniqueKey([
            'trip_id',
            'seat_id',
        ]);

        $this->forge->addKey('reservation_id');

        $this->forge->addForeignKey(
            'reservation_id',
            'reservations',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'trip_id',
            'trips',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'seat_id',
            'seats',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('reservation_seats', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 3. PAIEMENTS
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reservation_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'transaction_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'paid_at' => [
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('reservation_id');
        $this->forge->addKey('status');
        $this->forge->addKey('transaction_reference');

        $this->forge->addForeignKey(
            'reservation_id',
            'reservations',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('payments', true, [
            'ENGINE' => 'InnoDB',
        ]);

        /*
         * 4. REMBOURSEMENTS
         */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payment_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'refunded_at' => [
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_id');
        $this->forge->addKey('status');

        $this->forge->addForeignKey(
            'payment_id',
            'payments',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('refunds', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        // Ordre inverse des dépendances.
        $this->forge->dropTable('refunds', true);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('reservation_seats', true);
        $this->forge->dropTable('reservations', true);
    }
}

