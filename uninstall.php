<?php
/**
 * 移除外掛時清除所有資料：設定選項與翻譯快取
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('cts_options');

// 清除翻譯快取 transients（cts_tr_ 前綴）
// 快取 key 含標題 md5，無法逐一列舉，解除安裝時直接查詢資料庫是唯一可行做法
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_cts_tr_') . '%',
        $wpdb->esc_like('_transient_timeout_cts_tr_') . '%'
    )
);
