<?php
/*
Plugin Name: Chinese to English Slug Converter
Description: Convert Chinese post titles to English slugs using translation API
Plugin URI: https://yblog.org/zh-to-en-slug
Version: 1.0.0
Author: Ivan Lin
Author URI: https://yblog.org/
Requires at least: 6.0
Tested up to: 6.7.1
Text Domain: zh-to-en-slug
Domain Path: /languages
License: Apache-2.0
License URI: https://opensource.org/license/apache-2-0
*/

// 防止直接訪問此文件
if (!defined('ABSPATH')) {
    exit;
}

class ChineseToEnglishSlug {
    private $options;
    
    public function __construct() {
        // 初始化設定
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        
        // 修改 slug 生成的時機
        add_filter('wp_insert_post_data', array($this, 'process_post_data'), 10, 2);
        
        // 添加 AJAX 處理
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_test_translation_api', array($this, 'test_translation_api'));
        
        $this->options = get_option('cts_options', array(
            'max_length' => 30,
            'api_key' => ''
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_chinese-to-english-slug' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'cts-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery'),
            '1.0.2',
            true
        );
        
        wp_localize_script('cts-admin', 'ctsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cts_test_api')
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
        
        // 新的檢查邏輯：允許處理草稿和發布狀態
        $allowed_statuses = array('draft', 'publish');
        $current_status = isset($postarr['post_status']) ? $postarr['post_status'] : '';
        
        // 檢查是否是允許的狀態
        if (!in_array($current_status, $allowed_statuses)) {
            return $data;
        }
        
        // 檢查是否包含中文
        if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $data['post_title'])) {
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
            $data['post_name'] = $this->create_slug($translated);
        }
        
        return $data;
    }
    
    public function test_translation_api() {
        check_ajax_referer('cts_test_api', 'nonce');
        
        if (!isset($_POST['api_key'])) {
            wp_send_json_error(esc_html__('API Key 不能為空', 'zh-to-en-slug'));
            return;
        }
        
        $api_key = sanitize_text_field(wp_unslash($_POST['api_key']));
        $test_text = "測試文字";
        
        $url = add_query_arg(
            array('key' => $api_key),
            'https://translation.googleapis.com/language/translate/v2'
        );
        
        $body = array(
            'q' => $test_text,
            'source' => 'zh-TW',
            'target' => 'en',
        );
        
        $args = array(
            'body' => wp_json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Referer' => home_url(),
                'X-Requested-With' => 'XMLHttpRequest'
            ),
            'timeout' => 15,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error(esc_html__('API 連線失敗：', 'zh-to-en-slug') . esc_html($response->get_error_message()));
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['data']['translations'][0]['translatedText'])) {
            wp_send_json_success(esc_html__('API 測試成功！翻譯結果：', 'zh-to-en-slug') . esc_html($body['data']['translations'][0]['translatedText']));
        } else if (isset($body['error'])) {
            wp_send_json_error(esc_html__('API 錯誤：', 'zh-to-en-slug') . esc_html($body['error']['message']));
        } else {
            wp_send_json_error(esc_html__('無法取得翻譯結果，請檢查 API Key 是否正確', 'zh-to-en-slug'));
        }
    }
    
    private function translate_title($title) {
        if (empty($this->options['api_key'])) {
            return false;
        }
        
        $url = add_query_arg(
            array('key' => $this->options['api_key']),
            'https://translation.googleapis.com/language/translate/v2'
        );
        
        $body = array(
            'q' => $title,
            'source' => 'zh-TW',
            'target' => 'en',
        );
        
        $args = array(
            'body' => wp_json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Referer' => home_url(),
                'X-Requested-With' => 'XMLHttpRequest'
            ),
            'timeout' => 15,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['data']['translations'][0]['translatedText'])) {
            return $body['data']['translations'][0]['translatedText'];
        }
        
        return false;
    }
    
    private function create_slug($text) {
        $text = strtolower($text);
        $text = remove_accents($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = trim($text, '-');
        
        $max_length = isset($this->options['max_length']) ? $this->options['max_length'] : 30;
        if (strlen($text) > $max_length) {
            $text = substr($text, 0, $max_length);
            $text = preg_replace('/-[^-]*$/', '', $text);
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
                            <input type="hidden" id="cts_nonce" value="<?php echo esc_attr(wp_create_nonce('cts_test_api')); ?>">
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
            array($this, 'sanitize')
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
    
    public function sanitize($input) {
        $new_input = array();
        $new_input['api_key'] = sanitize_text_field($input['api_key']);
        $new_input['max_length'] = absint($input['max_length']);
        return $new_input;
    }
    
    public function section_info() {
        echo esc_html__('請設定 Google Translate API Key 和最大字元長度', 'zh-to-en-slug');
    }
    
    public function api_key_callback() {
        printf(
            '<input type="text" id="api_key" name="cts_options[api_key]" value="%s" class="regular-text" />',
            esc_attr($this->options['api_key'])
        );
    }
    
    public function max_length_callback() {
        printf(
            '<input type="number" id="max_length" name="cts_options[max_length]" value="%s" class="small-text" min="1" max="200" />',
            esc_attr($this->options['max_length'])
        );
    }
}

// 初始化外掛
add_action('plugins_loaded', function() {
    global $chinese_to_english_slug;
    $chinese_to_english_slug = new ChineseToEnglishSlug();
});
