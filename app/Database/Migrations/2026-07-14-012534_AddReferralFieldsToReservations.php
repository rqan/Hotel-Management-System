<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferralFieldsToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'referral_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'notes',
            ],
            'discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'referral_code',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', ['referral_code', 'discount_amount']);
    }
}