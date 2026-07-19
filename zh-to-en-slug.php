<?php
/*
Plugin Name: Chinese to English Slug Converter
Description: Convert Chinese post titles to English slugs using translation API
Plugin URI: https://yblog.org/zh-to-en-slug
Version: 1.2.2
Author: Ivan Lin
Author URI: https://yblog.org/
Requires at least: 6.0
Tested up to: 7.0
Text Domain: zh-to-en-slug
License: Apache-2.0
License URI: https://opensource.org/license/apache-2-0
*/

// 防止直接訪問此文件
if (!defined('ABSPATH')) {
    exit;
}

// Define the sanitize function at the global scope
if (!function_exists('sanitize_cts_options')) {
    function sanitize_cts_options($input) {
        $sanitized = array();
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        if (isset($input['max_length'])) {
            // 夾在 20-200 之間，避免扣除 ID 保留空間後 slug 長度歸零
            $sanitized['max_length'] = min(200, max(20, absint($input['max_length'])));
        }
        
        return $sanitized;
    }
}

// 條件式宣告：若同名外掛已載入（例如新舊版本同時安裝），跳過宣告與初始化，
// 避免 class 重複宣告的 fatal error。
// 注意：不可改成「declare 前先 class_exists 就 return」——PHP 會在編譯期
// 先綁定本檔的無條件 class 宣告，導致該檢查永遠為真、外掛整個不執行。
if (!class_exists('ChineseToEnglishSlug')) {

class ChineseToEnglishSlug {
    private $options = null;

    public function __construct() {
        // 初始化設定
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));

        // 修改 slug 生成的時機
        add_filter('wp_insert_post_data', array($this, 'process_post_data'), 10, 2);

        // 添加 AJAX 處理
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_test_translation_api', array($this, 'test_translation_api'));
    }

    private function get_options() {
        if (null === $this->options) {
            // 合併預設值，避免舊資料缺少個別 key
            $this->options = wp_parse_args(get_option('cts_options', array()), array(
                'max_length' => 30,
                'api_key'    => '',
            ));
        }
        return $this->options;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_chinese-to-english-slug' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'cts-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery'),
            '1.2.2',
            true
        );
        
        wp_localize_script('cts-admin', 'ctsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cts_test_api'),
            'testing' => __('測試中...', 'zh-to-en-slug'),
            'error'   => __('測試失敗，請檢查網路連線', 'zh-to-en-slug'),
        ));
    }
    
    public function process_post_data($data, $postarr) {
        // 如果是自動儲存或修訂版本，不處理
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $data;
        }
        
        // 如果沒有標題，不處理
        if (empty($data['post_title'])) {
            return $data;
        }
        
        // 允許處理的文章狀態，可透過 filter 自訂
        $allowed_statuses = apply_filters('cts_allowed_statuses', array('draft', 'publish', 'future', 'pending', 'private'));
        $current_status = isset($postarr['post_status']) ? $postarr['post_status'] : '';

        // 檢查是否是允許的狀態
        if (!in_array($current_status, $allowed_statuses, true)) {
            return $data;
        }

        // 檢查是否包含中文（含 CJK Extension A 與基本區全範圍）
        if (!preg_match('/[\x{3400}-\x{4DBF}\x{4e00}-\x{9FFF}]/u', $data['post_title'])) {
            return $data;
        }
        
        // 如果已經有自訂的有意義的 slug（不是 auto-draft 且不是根據當前標題生成的），不處理
        if (!empty($postarr['post_name']) && 
            !preg_match('/^auto-draft/', $postarr['post_name']) &&
            $postarr['post_name'] !== sanitize_title($data['post_title']) &&
            !empty($postarr['ID'])) {
            return $data;
        }
        
        // 翻譯標題
        $translated = $this->translate_title($data['post_title']);
        if ($translated) {
            // 建立基本 slug
            $base_slug = $this->create_slug($translated);
            
            // 獲取文章 ID
            $post_id = isset($postarr['ID']) ? $postarr['ID'] : 0;
            
            // 只有當文章已存在（有 ID）時，才添加到 slug 中
            if ($post_id > 0) {
                $data['post_name'] = $base_slug . '-' . $post_id;
            } else {
                // 新文章尚無 ID，直接使用翻譯後的 slug；
                // 唯一性由 WordPress 核心的 wp_unique_post_slug 保證
                $data['post_name'] = $base_slug;
            }
        }
        
        return $data;
    }
    
    public function test_translation_api() {
        check_ajax_referer('cts_test_api', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('權限不足', 'zh-to-en-slug'));
            return;
        }

        if (!isset($_POST['api_key'])) {
            wp_send_json_error(esc_html__('API Key 不能為空', 'zh-to-en-slug'));
            return;
        }

        $api_key  = sanitize_text_field(wp_unslash($_POST['api_key']));
        $response = $this->call_translation_api($api_key, '測試文字');

        if (is_wp_error($response)) {
            wp_send_json_error(esc_html__('API 連線失敗：', 'zh-to-en-slug') . esc_html($response->get_error_message()));
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['data']['translations'][0]['translatedText'])) {
            wp_send_json_success(esc_html__('API 測試成功！翻譯結果：', 'zh-to-en-slug') . esc_html($body['data']['translations'][0]['translatedText']));
        } elseif (isset($body['error'])) {
            wp_send_json_error(esc_html__('API 錯誤：', 'zh-to-en-slug') . esc_html($body['error']['message']));
        } else {
            wp_send_json_error(esc_html__('無法取得翻譯結果，請檢查 API Key 是否正確', 'zh-to-en-slug'));
        }
    }

    private function call_translation_api($api_key, $text) {
        $url = add_query_arg(
            array('key' => $api_key),
            'https://translation.googleapis.com/language/translate/v2'
        );

        $args = array(
            'body'    => wp_json_encode(array(
                'q'      => $text,
                'source' => 'zh-TW',
                'target' => 'en',
                // 要求純文字回應，避免回傳 HTML entities（如 &#39;）污染 slug
                'format' => 'text',
            )),
            'headers' => array(
                'Content-Type'     => 'application/json; charset=utf-8',
                'Referer'          => home_url(),
                'X-Requested-With' => 'XMLHttpRequest',
            ),
            // 翻譯失敗會回退到 WP 預設 slug，寧可快速失敗也不要卡住存檔流程
            'timeout' => 8,
        );

        return wp_remote_post($url, $args);
    }

    private function translate_title($title) {
        $options = $this->get_options();

        if (empty($options['api_key'])) {
            return false;
        }

        // 相同標題直接使用快取，減少 API 呼叫次數與存檔延遲
        $cache_key = 'cts_tr_' . md5($title);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $response = $this->call_translation_api($options['api_key'], $title);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['data']['translations'][0]['translatedText'])) {
            $translated = $body['data']['translations'][0]['translatedText'];
            set_transient($cache_key, $translated, WEEK_IN_SECONDS);
            return $translated;
        }

        return false;
    }
    
    private function create_slug($text) {
        $text = strtolower($text);
        $text = remove_accents($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = trim($text, '-');
        
        // 取得設定的最大長度，預留空間給 post_id
        $options = $this->get_options();
        $max_length = absint($options['max_length']);
        $reserved_length = 12; // 預留給 "-123456" 這樣的 ID 格式
        // 保底 8 個字元，避免設定值過小時 slug 被截成空字串
        $actual_max_length = max(8, $max_length - $reserved_length);

        if (strlen($text) > $actual_max_length) {
            $text = substr($text, 0, $actual_max_length);
            $text = preg_replace('/-[^-]*$/', '', $text); // 移除最後一個不完整的單字
        }
        
        return $text;
    }
    
    public function add_plugin_page() {
        add_options_page(
            esc_html__('中英網址轉換設定', 'zh-to-en-slug'),
            esc_html__('中英網址轉換', 'zh-to-en-slug'),
            'manage_options',
            'chinese-to-english-slug',
            array($this, 'create_admin_page')
        );
    }
    
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('中英網址轉換設定', 'zh-to-en-slug'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cts_option_group');
                do_settings_sections('chinese-to-english-slug-admin');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('API 測試', 'zh-to-en-slug'); ?></th>
                        <td>
                            <button type="button" id="test-api-button" class="button">
                                <?php echo esc_html__('測試 API 連線', 'zh-to-en-slug'); ?>
                            </button>
                            <div id="api-test-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function page_init() {
        register_setting(
            'cts_option_group',
            'cts_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => 'sanitize_cts_options',
                'default'           => array(
                    'max_length' => 30,
                    'api_key'    => '',
                ),
            )
        );
        
        add_settings_section(
            'cts_setting_section',
            esc_html__('基本設定', 'zh-to-en-slug'),
            array($this, 'section_info'),
            'chinese-to-english-slug-admin'
        );
        
        add_settings_field(
            'api_key',
            esc_html__('Google Translate API Key', 'zh-to-en-slug'),
            array($this, 'api_key_callback'),
            'chinese-to-english-slug-admin',
            'cts_setting_section'
        );
        
        add_settings_field(
            'max_length',
            esc_html__('最大字元長度', 'zh-to-en-slug'),
            array($this, 'max_length_callback'),
            'chinese-to-english-slug-admin',
            'cts_setting_section'
        );
    }
    
    public function section_info() {
        echo esc_html__('請設定 Google Translate API Key 和最大字元長度', 'zh-to-en-slug');
    }
    
    public function api_key_callback() {
        $options = $this->get_options();
        printf(
            '<input type="password" id="api_key" name="cts_options[api_key]" value="%s" class="regular-text" autocomplete="off" />',
            esc_attr($options['api_key'])
        );
    }

    public function max_length_callback() {
        $options = $this->get_options();
        printf(
            '<input type="number" id="max_length" name="cts_options[max_length]" value="%s" class="small-text" min="20" max="200" />',
            esc_attr($options['max_length'])
        );
        echo '<p class="description">' . esc_html__('設定的長度會自動保留空間給文章 ID', 'zh-to-en-slug') . '</p>';
    }
}

// 初始化外掛
add_action('plugins_loaded', function() {
    global $chinese_to_english_slug;
    $chinese_to_english_slug = new ChineseToEnglishSlug();
});

} // end if (!class_exists('ChineseToEnglishSlug'))
