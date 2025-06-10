<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserJawabanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_jawaban' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_result' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'id_test' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
            ],
            'id_question' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'jawaban_user' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'waktu_submit' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_jawaban', true);

        $this->forge->addKey('id_question');
        $this->forge->addForeignKey('id_question', 'question', 'id_question', 'CASCADE', 'CASCADE');

        $this->forge->addKey('id_result');
        $this->forge->addForeignKey('id_result', 'test_result', 'id_result', 'CASCADE', 'CASCADE');

        $this->forge->createTable('user_jawaban');
    }

    public function down()
    {
        $this->forge->dropTable('user_jawaban');
    }
}
