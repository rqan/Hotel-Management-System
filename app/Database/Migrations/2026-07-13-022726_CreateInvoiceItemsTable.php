<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoiceItemsTable extends Migration
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
            'invoice_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 1,
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'quantity * unit_price, disimpan agar tidak perlu hitung ulang tiap query',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('invoice_id', 'invoices', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('invoice_items');
    }

    public function down()
    {
        $this->forge->dropTable('invoice_items');
    }
}