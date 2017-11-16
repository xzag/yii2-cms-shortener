<?php

use yii\db\Migration;

class m170317_045029_create_shorturl_tables extends Migration
{
    public function up()
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%link}}', [
            'id'         => $this->primaryKey(),
            'domain'        => $this->string()->notNull(),
            'url'        => $this->string()->notNull(),
            'url_hash'  => $this->string(128)->notNull()->unique(),
            'shortened_url' => $this->string(),
            'hostname'    => $this->string(),
            'pid'         => $this->string(),
            'status'      => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'processed_at' => $this->integer(),
        ], $tableOptions . ' AUTO_INCREMENT=112768');

        $this->createTable('{{%article_link}}', [
            'article_id' => $this->integer()->notNull(),
            'link_id'     => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-article_link', '{{%article_link}}', ['article_id', 'link_id']);
        $this->addForeignKey('fk-article-id-article_link-article_id', '{{%article_link}}', 'article_id', '{{%article}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-link-id-article_link-link_id', '{{%article_link}}', 'link_id', '{{%link}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('{{%article_link}}');
        $this->dropTable('{{%link}}');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
