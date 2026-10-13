<?php

use yii\db\Migration;

/**
 * Keeps createcard URLs stable when product/template titles are edited.
 */
class m260511_110000_add_url_slug_to_product extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%product}}', 'url_slug', $this->string(255)->null()->after('title'));
        $this->execute("UPDATE {{%product}} SET url_slug = REPLACE(TRIM(COALESCE(NULLIF(title, ''), 'card')), ' ', '_') WHERE url_slug IS NULL OR url_slug = ''");
    }

    public function safeDown()
    {
        $this->dropColumn('{{%product}}', 'url_slug');
    }
}
