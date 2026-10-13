<?php

use yii\db\Migration;

class m260502_091500_create_analytics_event_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%analytics_event}}', [
            'id' => $this->primaryKey(),
            'event_type' => $this->string(30)->notNull(),
            'page_url' => $this->text()->notNull(),
            'page_path' => $this->string(500)->notNull(),
            'page_title' => $this->string(500)->null(),
            'element_text' => $this->string(500)->null(),
            'element_tag' => $this->string(50)->null(),
            'element_href' => $this->text()->null(),
            'referrer' => $this->text()->null(),
            'session_id' => $this->string(80)->null(),
            'ip_address' => $this->string(45)->null(),
            'user_agent' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex('idx-analytics_event-event_type', '{{%analytics_event}}', 'event_type');
        $this->createIndex('idx-analytics_event-page_path', '{{%analytics_event}}', 'page_path');
        $this->createIndex('idx-analytics_event-created_at', '{{%analytics_event}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%analytics_event}}');
    }
}
