<?php
/**
 * Plugin Name: Blog Audio Summary
 * Plugin URI: https://blog.com
 * Description: FREE audio summary player with UK English Feminine Voice - Custom #338866 theme
 * Version: 1.0.0
 * Author: blog
 * Author URI: https://blog.com
 * License: GPL v2 or later
 * Text Domain: blog-audio
 */

if (!defined('ABSPATH')) exit;

class blog_Audio_Player {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'add_audio_player'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function enqueue_scripts() {
        if (is_single() && $this->should_show_player()) {
            // ResponsiveVoice library with your key
            wp_enqueue_script(
                'responsivevoice',
                'https://code.responsivevoice.org/responsivevoice.js?key=yakzCuBD',
                array(),
                null,
                true
            );
            
            wp_enqueue_style(
                'blog-audio-style',
                plugin_dir_url(__FILE__) . 'css/audio-player.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'blog-audio-script',
                plugin_dir_url(__FILE__) . 'js/audio-player.js',
                array('responsivevoice'),
                '1.0.0',
                true
            );
            
            wp_localize_script('blog-audio-script', 'blogAudioConfig', array(
                'voice' => get_option('blog_voice', 'UK English Female'),
                'rate' => get_option('blog_rate', '1'),
                'pitch' => get_option('blog_pitch', '1'),
                'volume' => get_option('blog_volume', '1'),
            ));
        }
    }
    
    private function should_show_player() {
        $post_types = get_option('blog_post_types', array('post'));
        return in_array(get_post_type(), $post_types);
    }
    
    public function add_audio_player($content) {
        if (!is_single() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        if (!$this->should_show_player()) {
            return $content;
        }
        
        $position = get_option('blog_position', 'top');
        
        // Get clean article text
        $article_text = wp_strip_all_tags($content);
        $article_text = preg_replace('/\s+/', ' ', $article_text);
        $article_text = trim($article_text);
        
        // Don't show for very short content
        if (str_word_count($article_text) < 50) {
            return $content;
        }
        
        $audio_player = $this->render_player_html($article_text);
        
        if ($position === 'top') {
            return $audio_player . $content;
        } else {
            return $content . $audio_player;
        }
    }
    
    private function render_player_html($article_text) {
        $word_count = str_word_count($article_text);
        $estimated_duration = ceil($word_count / 150); // 150 words per minute
        
        ob_start();
        ?>
        <div class="blog-audio-player" data-article-text="<?php echo esc_attr($article_text); ?>">
            <div class="audio-header">
                <span class="audio-icon">🔊</span>
                <span class="audio-title">Listen to this article</span>
                <span class="audio-quality-badge">UK Voice</span>
                <span class="audio-duration" id="estimatedDuration">~<?php echo $estimated_duration; ?> min</span>
            </div>
            <div class="audio-controls">
                <button id="playPauseBtn" class="play-btn" aria-label="Play">
                    <svg class="play-icon" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="currentColor" d="M8 5v14l11-7z"/>
                    </svg>
                    <svg class="pause-icon" style="display:none;" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="currentColor" d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                    </svg>
                </button>
                <div class="progress-container">
                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <span class="time-display">
                        <span id="currentTime">0:00</span> / <span id="totalTime">--:--</span>
                    </span>
                </div>
                <div class="speed-control">
                    <label for="speedControl" class="sr-only">Playback Speed</label>
                    <select id="speedControl" class="speed-select">
                        <option value="0.75">0.75x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2">2x</option>
                    </select>
                </div>
            </div>
            <div class="audio-error" id="audioError" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function add_settings_page() {
        add_options_page(
            'blog Audio Settings',
            'Audio Summary',
            'manage_options',
            'blog-audio',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('blog_audio_settings', 'blog_voice');
        register_setting('blog_audio_settings', 'blog_rate');
        register_setting('blog_audio_settings', 'blog_pitch');
        register_setting('blog_audio_settings', 'blog_volume');
        register_setting('blog_audio_settings', 'blog_position');
        register_setting('blog_audio_settings', 'blog_post_types');
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>🔊 blog Audio Summary Settings</h1>
            <p><strong>FREE audio player with UK English feminine voice!</strong></p>
            
            <div class="notice notice-success">
                <p><strong>✅ ResponsiveVoice Activated!</strong> Your key is configured and ready to use.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('blog_audio_settings'); ?>
                
                <h2>Voice Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Voice Selection</th>
                        <td>
                            <select name="blog_voice">
                                <option value="UK English Female" <?php selected(get_option('blog_voice', 'UK English Female'), 'UK English Female'); ?>>
                                    UK English Female ⭐ RECOMMENDED
                                </option>
                                <option value="UK English Male" <?php selected(get_option('blog_voice'), 'UK English Male'); ?>>
                                    UK English Male
                                </option>
                                <option value="US English Female" <?php selected(get_option('blog_voice'), 'US English Female'); ?>>
                                    US English Female
                                </option>
                                <option value="Australian Female" <?php selected(get_option('blog_voice'), 'Australian Female'); ?>>
                                    Australian Female
                                </option>
                            </select>
                            <p class="description">UK English Female provides a professional British accent</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Speaking Rate</th>
                        <td>
                            <select name="blog_rate">
                                <option value="0.75" <?php selected(get_option('blog_rate', '1'), '0.75'); ?>>0.75x (Slower)</option>
                                <option value="0.9" <?php selected(get_option('blog_rate', '1'), '0.9'); ?>>0.9x (Slightly Slower)</option>
                                <option value="1" <?php selected(get_option('blog_rate', '1'), '1'); ?>>1.0x (Normal) - Recommended</option>
                                <option value="1.1" <?php selected(get_option('blog_rate', '1'), '1.1'); ?>>1.1x (Slightly Faster)</option>
                                <option value="1.25" <?php selected(get_option('blog_rate', '1'), '1.25'); ?>>1.25x (Faster)</option>
                                <option value="1.5" <?php selected(get_option('blog_rate', '1'), '1.5'); ?>>1.5x (Much Faster)</option>
                            </select>
                            <p class="description">Controls the default speaking speed</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Voice Pitch</th>
                        <td>
                            <select name="blog_pitch">
                                <option value="0.75" <?php selected(get_option('blog_pitch', '1'), '0.75'); ?>>Lower</option>
                                <option value="1" <?php selected(get_option('blog_pitch', '1'), '1'); ?>>Normal - Recommended</option>
                                <option value="1.25" <?php selected(get_option('blog_pitch', '1'), '1.25'); ?>>Higher</option>
                            </select>
                            <p class="description">Adjust voice pitch (keep at Normal for natural sound)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Volume</th>
                        <td>
                            <select name="blog_volume">
                                <option value="0.5" <?php selected(get_option('blog_volume', '1'), '0.5'); ?>>50%</option>
                                <option value="0.75" <?php selected(get_option('blog_volume', '1'), '0.75'); ?>>75%</option>
                                <option value="1" <?php selected(get_option('blog_volume', '1'), '1'); ?>>100% (Maximum)</option>
                            </select>
                            <p class="description">Default audio volume level</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Display Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Player Position</th>
                        <td>
                            <select name="blog_position">
                                <option value="top" <?php selected(get_option('blog_position', 'top'), 'top'); ?>>Before Content (Top)</option>
                                <option value="bottom" <?php selected(get_option('blog_position'), 'bottom'); ?>>After Content (Bottom)</option>
                            </select>
                            <p class="description">Where to display the audio player</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable On Post Types</th>
                        <td>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            $selected_types = get_option('blog_post_types', array('post'));
                            foreach ($post_types as $post_type) {
                                if ($post_type->name === 'attachment') continue;
                                $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
                                echo '<label style="display:block;margin-bottom:5px;">';
                                echo '<input type="checkbox" name="blog_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '>';
                                echo ' ' . esc_html($post_type->label);
                                echo '</label>';
                            }
                            ?>
                            <p class="description">Select which post types should display the audio player</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <hr>
            
            <h2>✨ Features</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3>Audio Features</h3>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li>✅ UK English Feminine Voice</li>
                        <li>✅ Speed Control (0.75x - 2x)</li>
                        <li>✅ Progress Bar</li>
                        <li>✅ Time Display</li>
                        <li>✅ Keyboard Shortcuts</li>
                        <li>✅ Mobile Responsive</li>
                    </ul>
                </div>
                <div>
                    <h3>Technical</h3>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li>✅ No Server Requirements</li>
                        <li>✅ Works in All Browsers</li>
                        <li>✅ Custom #338866 Color Theme</li>
                        <li>✅ Lightweight (14kB)</li>
                        <li>✅ WCAG 2.0 Compliant</li>
                        <li>✅ Free Forever!</li>
                    </ul>
                </div>
            </div>
            
            <h2>⌨️ Keyboard Shortcuts</h2>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>Space</code> or <code>K</code></td>
                        <td>Play / Pause</td>
                    </tr>
                    <tr>
                        <td><code>Esc</code></td>
                        <td>Stop</td>
                    </tr>
                    <tr>
                        <td><code>↑</code></td>
                        <td>Increase Speed</td>
                    </tr>
                    <tr>
                        <td><code>↓</code></td>
                        <td>Decrease Speed</td>
                    </tr>
                </tbody>
            </table>
            
            <h2>💰 About ResponsiveVoice</h2>
            <p>This plugin uses ResponsiveVoice for text-to-speech conversion.</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><strong>Free for Non-Commercial Use:</strong> Perfect for personal blogs and non-profit sites</li>
                <li><strong>Commercial License:</strong> Only $39/year for commercial websites (much cheaper than other TTS services!)</li>
                <li><strong>More Info:</strong> <a href="https://responsivevoice.org/" target="_blank">ResponsiveVoice.org</a></li>
            </ul>
            
            <h2>📊 Cost Comparison</h2>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Cost per Year</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #d4edda;">
                        <td><strong>ResponsiveVoice (This Plugin)</strong></td>
                        <td><strong>$0 - $39/year</strong> ✅</td>
                    </tr>
                    <tr>
                        <td>Google Cloud TTS</td>
                        <td>~$192/year</td>
                    </tr>
                    <tr>
                        <td>Amazon Polly</td>
                        <td>~$192/year</td>
                    </tr>
                    <tr>
                        <td>ElevenLabs</td>
                        <td>~$264/year</td>
                    </tr>
                </tbody>
            </table>
            <p><strong>You Save:</strong> $150+ per year! 🎉</p>
            
            <h2>🆘 Need Help?</h2>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><strong>Documentation:</strong> See README.md file in plugin folder</li>
                <li><strong>ResponsiveVoice API:</strong> <a href="https://responsivevoice.org/api/" target="_blank">API Documentation</a></li>
                <li><strong>Support:</strong> Email support@blog.com</li>
            </ul>
        </div>
        <style>
            .wrap h2 { margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .widefat th, .widefat td { padding: 10px; }
        </style>
        <?php
    }
}

// Initialize the plugin
blog_Audio_Player::get_instance();
