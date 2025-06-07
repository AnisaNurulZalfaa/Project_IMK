<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestResultTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_result' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'id_test' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'pendidikan' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true, 
            ],
            'tujuan_pemeriksaan' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true, 
            ],
            'interpretasi' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'skor_total' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'tanggal_pengerjaan' => [
                'type' => 'DATE',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id_result', true);

        $this->forge->addKey('id_user');
        $this->forge->addForeignKey('id_user', 'users', 'id_user', 'CASCADE', 'CASCADE');

        $this->forge->addKey('id_test');
        $this->forge->addForeignKey('id_test', 'test', 'id_test', 'CASCADE', 'CASCADE');

        $this->forge->createTable('test_result');
    }

    public function down()
    {
        $this->forge->dropTable('test_result');
    }
}
