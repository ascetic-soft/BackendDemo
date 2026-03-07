<?php

declare(strict_types=1);

use AsceticSoft\RowcastSchema\Migration\AbstractMigration;
use AsceticSoft\RowcastSchema\Schema\ColumnType;
use AsceticSoft\RowcastSchema\SchemaBuilder\SchemaBuilder;
use AsceticSoft\RowcastSchema\SchemaBuilder\TableBuilder;

final class Migration_20260306_165816 extends AbstractMigration
{
    public function up(SchemaBuilder $schema): void
    {
        $schema->createTable('products', function (TableBuilder $table): void {
            $table->column('id', ColumnType::String)->length(36)->primaryKey();
            $table->column('name', ColumnType::String)->length(255);
            $table->column('price_amount', ColumnType::Integer)->default(0);
            $table->column('price_currency', ColumnType::String)->length(3)->default('USD');
            $table->column('description', ColumnType::Text);
            $table->column('created_at', ColumnType::Datetime)->default('CURRENT_TIMESTAMP');
            $table->column('updated_at', ColumnType::Datetime)->default('CURRENT_TIMESTAMP');
            $table->primaryKey(['id']);
        });
        $schema->createTable('orders', function (TableBuilder $table): void {
            $table->column('id', ColumnType::String)->length(36)->primaryKey();
            $table->column('status', ColumnType::String)->length(20)->default('pending');
            $table->column('customer_name', ColumnType::String)->length(255);
            $table->column('total_amount', ColumnType::Integer)->default(0);
            $table->column('total_currency', ColumnType::String)->length(3)->default('USD');
            $table->column('created_at', ColumnType::Datetime)->default('CURRENT_TIMESTAMP');
            $table->column('updated_at', ColumnType::Datetime)->default('CURRENT_TIMESTAMP');
            $table->primaryKey(['id']);
        });
        $schema->createTable('order_lines', function (TableBuilder $table): void {
            $table->column('order_id', ColumnType::String)->length(36)->primaryKey();
            $table->column('position', ColumnType::Integer)->default(0)->primaryKey();
            $table->column('product_id', ColumnType::String)->length(36);
            $table->column('product_name', ColumnType::String)->length(255);
            $table->column('unit_price_amount', ColumnType::Integer)->default(0);
            $table->column('unit_price_currency', ColumnType::String)->length(3)->default('USD');
            $table->column('quantity', ColumnType::Integer)->default(1);
            $table->primaryKey(['order_id', 'position']);
        });
    }

    public function down(SchemaBuilder $schema): void
    {
        $schema->dropTable('order_lines');
        $schema->dropTable('orders');
        $schema->dropTable('products');
    }
}
