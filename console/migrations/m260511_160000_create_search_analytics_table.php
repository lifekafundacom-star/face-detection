<?php

use yii\db\Migration;

class m260511_160000_create_search_analytics_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%search_analytics}}', true) !== null) {
            return;
        }

        $this->createTable('{{%search_analytics}}', [
            'id' => $this->primaryKey(),
            'keyword' => $this->string(255)->notNull(),
            'normalized_keyword' => $this->string(255)->notNull(),
            'result_count' => $this->integer()->notNull()->defaultValue(0),
            'category_names' => $this->text()->null(),
            'subcategory_names' => $this->text()->null(),
            'page_url' => $this->text()->null(),
            'referrer' => $this->text()->null(),
            'session_id' => $this->string(80)->null(),
            'ip_address' => $this->string(45)->null(),
            'user_agent' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx-search_analytics-normalized_keyword', '{{%search_analytics}}', 'normalized_keyword');
        $this->createIndex('idx-search_analytics-created_at', '{{%search_analytics}}', 'created_at');
        $this->createIndex('idx-search_analytics-result_count', '{{%search_analytics}}', 'result_count');
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('{{%search_analytics}}', true) !== null) {
            $this->dropTable('{{%search_analytics}}');
        }
    }
}
