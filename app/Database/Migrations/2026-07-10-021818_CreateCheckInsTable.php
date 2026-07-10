<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCheckInsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reservation_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true,
            ],
            'checked_in_at' => [
                'type' => 'DATETIME',
            ],
            'checked_in_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('reservation_id', 'reservations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('checked_in_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('check_ins');
    }

    public function down()
    {
        $this->forge->dropTable('check_ins');
    }
}