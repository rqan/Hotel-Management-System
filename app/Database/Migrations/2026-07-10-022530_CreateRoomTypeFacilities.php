<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomTypeFacilitiesTable extends Migration
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
            'room_type_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'facility_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['room_type_id', 'facility_id']);
        $this->forge->addForeignKey('room_type_id', 'room_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('room_type_facilities');
    }

    public function down()
    {
        $this->forge->dropTable('room_type_facilities');
    }
}