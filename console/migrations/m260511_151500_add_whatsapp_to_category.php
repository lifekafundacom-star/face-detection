<?php

use yii\db\Migration;

class m260511_151500_add_whatsapp_to_category extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%category}}', true);
        if ($schema !== null && $schema->getColumn('whatsapp') === null) {
            $this->addColumn('{{%category}}', 'whatsapp', $this->text()->null()->after('tag'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%category}}', true);
        if ($schema !== null && $schema->getColumn('whatsapp') !== null) {
            $this->dropColumn('{{%category}}', 'whatsapp');
        }
    }
}
