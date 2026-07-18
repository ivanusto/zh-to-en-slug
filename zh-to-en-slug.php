<?php
/*
Plugin Name: Chinese to English Slug Converter
Description: Convert Chinese post titles to English slugs using translation API
Plugin URI: https://yblog.org/zh-to-en-slug
Version: 1.1.0
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

// Define the sanitize function at the global scope
if (!function_exists('sanitize_cts_options')) {
    function sanitize_cts_options($input) {
        $sanitized = array();
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        if (isset($input['max_length'])) {
            $sanitized['max_length'] = absint($input['max_length']);
        }
        
        return $sanitized;
    }
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
            '1.1.0',
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
            // 建立基本 slug
            $base_slug = $this->create_slug($translated);
            
            // 獲取文章 ID
            $post_id = isset($postarr['ID']) ? $postarr['ID'] : 0;
            
            // 只有當文章已存在（有 ID）時，才添加到 slug 中
            if ($post_id > 0) {
                $data['post_name'] = $base_slug . '-' . $post_id;
            } else {
                // 如果是新文章，暫時只使用翻譯後的 slug
                // 文章創建後，WordPress 會再次調用此函數，那時會有 post_id
                $data['post_name'] = $base_slug;
            }
        }
        
        return $data;
    }
    
    public function test_translation_api() {
        check_ajax_referer('cts_test_api', 'nonce');

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
            )),
            'headers' => array(
                'Content-Type'     => 'application/json; charset=utf-8',
                'Referer'          => home_url(),
                'X-Requested-With' => 'XMLHttpRequest',
            ),
            'timeout' => 15,
        );

        return wp_remote_post($url, $args);
    }

    private function translate_title($title) {
        if (empty($this->options['api_key'])) {
            return false;
        }

        $response = $this->call_translation_api($this->options['api_key'], $title);

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
        
        // 取得設定的最大長度，預留空間給 post_id
        $max_length = isset($this->options['max_length']) ? $this->options['max_length'] : 30;
        $reserved_length = 12; // 預留給 "-123456" 這樣的 ID 格式
        $actual_max_length = $max_length - $reserved_length;
        
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
            'cts_option_group',    // Option group
            'cts_options',         // Option name
            'sanitize_cts_options' // Using string callback instead of array
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
        echo '<p class="description">' . esc_html__('設定的長度會自動保留空間給文章 ID', 'zh-to-en-slug') . '</p>';
    }
}

// 初始化外掛
add_action('plugins_loaded', function() {
    global $chinese_to_english_slug;
    $chinese_to_english_slug = new ChineseToEnglishSlug();
});
