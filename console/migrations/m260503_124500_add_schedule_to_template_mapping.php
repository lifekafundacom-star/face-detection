<?php

use yii\db\Migration;

class m260503_124500_add_schedule_to_template_mapping extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%template_mapping}}', 'publish_type', $this->string(20)->notNull()->defaultValue('direct')->after('subcategory_id'));
        $this->addColumn('{{%template_mapping}}', 'scheduled_at', $this->dateTime()->null()->after('publish_type'));
        $this->createIndex('idx-template_mapping-publish_type-scheduled_at', '{{%template_mapping}}', ['publish_type', 'scheduled_at']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-template_mapping-publish_type-scheduled_at', '{{%template_mapping}}');
        $this->dropColumn('{{%template_mapping}}', 'scheduled_at');
        $this->dropColumn('{{%template_mapping}}', 'publish_type');
    }
}
