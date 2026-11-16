<?php


namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTicketsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reservation_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'payment_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'ticket_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'valid',
            ],
            'issued_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey('reservation_id');
        $this->forge->addUniqueKey('payment_id');
        $this->forge->addUniqueKey('ticket_number');

        $this->forge->addForeignKey(
            'reservation_id',
            'reservations',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'payment_id',
            'payments',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('tickets', true);
    }

    public function down()
    {
        $this->forge->dropTable('tickets', true);
    }
}