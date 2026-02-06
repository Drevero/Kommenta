<?php
/*
 * Plugin Name:       Kommenta
 * Plugin URI:        https://remi-duple.fr/
 * Description:       Enhance your Wordpress comment section by adding vote to improve the interaction rate with your community
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Rémi Duplé
 * Author URI:        https://remi-duple.fr/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kommenta
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Kommenta_Wordpress {
    private $commentMetaKey = 'kommenta_meta_comment';
    private static $optionKey = 'kommenta_vote_types';
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Load translations
        add_action('init', array($this, 'load_textdomain'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_front_assets'));
    
        add_action( 'wp_ajax_komenta_vote', array($this, 'komenta_vote_ajax'));
        add_action( 'wp_ajax_nopriv_komenta_vote', array($this, 'komenta_vote_ajax'));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_kommenta_save_vote_types', array($this, 'save_vote_types_ajax'));
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('kommenta', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Register, enqueue and localize front-end assets (only on single posts)
     */
    public function enqueue_front_assets() {
        if (!is_single()) { return; }

        wp_enqueue_style( 'komenta-front', plugins_url( 'assets/css/komenta-front.css', __FILE__ ) );
        wp_enqueue_script( 'komenta-front-dynamic', plugins_url( 'assets/js/komenta-front.js', __FILE__ ), array(), false, true );
        wp_localize_script('komenta-front-dynamic', 'komentaData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('komenta_vote'),
            'i18n'    => [
                'voteSent'     => __('+ 1 Vote sent', 'kommenta'),
                'alreadyVoted' => __('You already voted for this post', 'kommenta'),
                'systemError'  => __('A system error prevents us from counting your vote', 'kommenta'),
                'votes'        => __('votes', 'kommenta'),
            ]
        ]);
    }

    /**
     * Add admin menu for Kommenta settings
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Kommenta Settings', 'kommenta'),
            __('Kommenta', 'kommenta'),
            'manage_options',
            'kommenta-settings',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_kommenta-settings') {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('kommenta-admin', plugins_url('assets/css/kommenta-admin.css', __FILE__));
        wp_enqueue_script('kommenta-admin', plugins_url('assets/js/kommenta-admin.js', __FILE__), array('jquery', 'wp-color-picker'), '1.0.0', true);
        wp_localize_script('kommenta-admin', 'kommentaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kommenta_admin'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this vote type?', 'kommenta'),
                'saved' => __('Settings saved successfully!', 'kommenta'),
                'error' => __('An error occurred while saving.', 'kommenta'),
                'labelRequired' => __('Label is required.', 'kommenta'),
            )
        ));
    }

    /**
     * Generate slug from label (only on first creation)
     */
    public static function generateSlug($label) {
        $slug = sanitize_title($label);
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        return $slug;
    }

    /**
     * Sanitize hex color (fallback if WP function not available)
     */
    public static function sanitizeHexColor($color) {
        // Use WP function if available
        if (function_exists('sanitize_hex_color')) {
            $result = sanitize_hex_color($color);
            if ($result) {
                return $result;
            }
        }
        
        // Fallback: manually validate hex color
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }
        
        return '#cccccc';
    }

    /**
     * Get default vote types
     */
    public static function getDefaultVoteTypes() {
        return array(
            array(
                'label' => "Positive",
                'slug' => "positive",
                'color' => "#6bc9a0"
            ),
            array(
                'label' => "Negative",
                'slug' => "negative",
                'color' => "#e88a94"
            ),
            array(
                'label' => "Neutral",
                'slug' => "neutral",
                'color' => "#9ba3d0"
            )
        );
    }

    /**
     * Save vote types via AJAX
     */
    public function save_vote_types_ajax() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kommenta_admin')) {
                wp_send_json_error(array('message' => __('Security check failed', 'kommenta')));
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('Permission denied', 'kommenta')));
                return;
            }

            // Get vote types from POST
            $vote_types_raw = array();
            if (isset($_POST['vote_types']) && is_array($_POST['vote_types'])) {
                $vote_types_raw = wp_unslash($_POST['vote_types']);
            }
            
            $vote_types = array();

            foreach ($vote_types_raw as $type) {
                if (!is_array($type)) {
                    continue;
                }
                
                $label = isset($type['label']) ? sanitize_text_field($type['label']) : '';
                $slug = isset($type['slug']) ? sanitize_key($type['slug']) : '';
                
                // Handle color with our custom sanitizer
                $raw_color = isset($type['color']) ? sanitize_text_field($type['color']) : '#cccccc';
                $color = self::sanitizeHexColor($raw_color);

                if (empty($label)) {
                    continue;
                }

                // If no slug, generate one from label (first time only)
                if (empty($slug)) {
                    $slug = self::generateSlug($label);
                }

                // Ensure slug is unique
                $base_slug = $slug;
                $counter = 1;
                while ($this->slugExists($slug, $vote_types)) {
                    $slug = $base_slug . '-' . $counter;
                    $counter++;
                }

                $vote_types[] = array(
                    'label' => $label,
                    'slug' => $slug,
                    'color' => $color
                );
            }

            update_option(self::$optionKey, $vote_types);
            
            wp_send_json_success(array('vote_types' => $vote_types));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    /**
     * Check if slug already exists in array
     */
    private function slugExists($slug, $types) {
        foreach ($types as $type) {
            if ($type['slug'] === $slug) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        $vote_types = self::getVoteType();
        ?>
        <div class="wrap kommenta-settings-wrap">

            <div class="kommenta-header">
                <div class="kommenta-logo">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <h1>
                    Kommenta
                    <small><?php echo esc_html__('Configure the types of votes users can give to comments.', 'kommenta'); ?></small>
                </h1>
            </div>

            <div class="kommenta-card">
                <div class="kommenta-card-header">
                    <h2><?php echo esc_html__('Vote Types', 'kommenta'); ?></h2>
                </div>

                <div class="kommenta-card-body">
                    <div id="kommenta-vote-types-list">
                        <?php foreach ($vote_types as $index => $type) : ?>
                        <div class="kommenta-vote-type-row" data-index="<?php echo $index; ?>">
                            <input type="text" class="kommenta-color-picker vote-type-slug-hidden" value="<?php echo esc_attr($type['color']); ?>">
                            <input type="hidden" class="vote-type-slug" value="<?php echo esc_attr($type['slug']); ?>">
                            <div class="vote-type-fields">
                                <div class="vote-type-label-wrap">
                                    <input type="text" class="vote-type-label" value="<?php echo esc_attr($type['label']); ?>" placeholder="<?php echo esc_attr__('Vote label', 'kommenta'); ?>">
                                </div>
                                <span class="vote-type-slug-badge"><?php echo esc_html($type['slug']); ?></span>
                            </div>
                            <button type="button" class="button kommenta-remove-type" title="<?php echo esc_attr__('Remove', 'kommenta'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="kommenta-add-zone">
                        <button type="button" class="button" id="kommenta-add-type">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php echo esc_html__('Add Vote Type', 'kommenta'); ?>
                        </button>
                    </div>
                </div>

                <div class="kommenta-card-footer">
                    <button type="button" class="button" id="kommenta-reset-defaults">
                        <?php echo esc_html__('Reset to Defaults', 'kommenta'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="kommenta-save-settings">
                        <?php echo esc_html__('Save Settings', 'kommenta'); ?>
                    </button>
                </div>
            </div>

            <div class="kommenta-toast" id="kommenta-toast"></div>
        </div>

        <script type="text/html" id="tmpl-kommenta-vote-type-row">
            <div class="kommenta-vote-type-row new-row" data-index="{{data.index}}">
                <input type="text" class="kommenta-color-picker vote-type-slug-hidden" value="#cccccc">
                <input type="hidden" class="vote-type-slug" value="">
                <div class="vote-type-fields">
                    <div class="vote-type-label-wrap">
                        <input type="text" class="vote-type-label" value="" placeholder="<?php echo esc_attr__('Vote label', 'kommenta'); ?>">
                    </div>
                    <span class="vote-type-slug-badge"><?php echo esc_html__('Auto-generated', 'kommenta'); ?></span>
                </div>
                <button type="button" class="button kommenta-remove-type" title="<?php echo esc_attr__('Remove', 'kommenta'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </script>
        <?php
    }

    public static function getVoteType() {
        $saved_types = get_option(self::$optionKey);
        
        if (!empty($saved_types) && is_array($saved_types)) {
            return $saved_types;
        }
        
        // Return defaults if no saved types
        return self::getDefaultVoteTypes();
    }

    public static function generateTemplateTypeVote($types, $emotions) {
        $htmlTemplate='';
        foreach($types as $type) {
            $totalVotes=array_sum((array) $emotions);
            $totalEmotionPurcentage=($totalVotes==0) ? 1 : $totalVotes;
            $slug = $type['slug'];
            $count = isset($emotions->$slug) ? $emotions->$slug : 0;
            $htmlTemplate.='<div class="emotion-reaction" data-reaction="' . esc_attr($slug) . '" data-number="' . $count . '" data-label="' . esc_attr__($type['label'], 'kommenta') . '" style="background: ' . $type['color'] . ';width: ' . ($count*100/$totalEmotionPurcentage) . '%;"></div>';
        }
        return $htmlTemplate;
    }

    public static function getVoteComment($comment_id) {
        $commentMetaKey = 'kommenta_meta_comment';
        $commentMeta=get_comment_meta($comment_id, $commentMetaKey, true);
        if(!$commentMeta) {
            return false;
        }
        return json_decode($commentMeta);
    }

    public function addVoteComment($comment_id, $reaction, $ipUser) {
        $commentMeta=get_comment_meta($comment_id, $this->commentMetaKey, true);
        if(!$commentMeta) {
            // We initialise the comment array
            $metaStructure=['emotions' => [], 'voters' => []];
            $metaStructure['emotions'][$reaction]=1;
            $metaStructure['voters'][]=$ipUser;
            add_comment_meta($comment_id, $this->commentMetaKey, json_encode($metaStructure));
            return json_decode(json_encode($metaStructure));
        }
        $commentMeta=json_decode($commentMeta);
        if($commentMeta->emotions->$reaction) {
            // Already exist, we increment
            $commentMeta->emotions->$reaction++;
        } else {
            // We init to 1
            $commentMeta->emotions->$reaction=1;  
        }
        $commentMeta->voters[]=$ipUser;
        update_comment_meta($comment_id, $this->commentMetaKey, json_encode($commentMeta));
        return $commentMeta;
    }
    public function checkVoteForbidded($comment_id, $ipUser) {
        // Fetching emotions comment from the id
        $commentMeta=get_comment_meta($comment_id, $this->commentMetaKey, true);
        if(!$commentMeta) {
            // No comment, allowed by default
            return false;
        }
        $commentMeta=json_decode($commentMeta);
        // Checking if IP already in the array
        $foundedIP=array_search($ipUser, $commentMeta->voters);
        $foundedIP=($foundedIP || $foundedIP==0) ? true : false;
        return $foundedIP;
    }
    public function komenta_vote_ajax() {
        if(!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'komenta_vote')) {
            wp_send_json_error( __("You're not allowed to execute this action", 'kommenta'), 403 );
        }

        $comment_id = isset($_POST['id_comment']) ? (int) $_POST['id_comment'] : 0;
        $reaction   = isset($_POST['reaction']) ? sanitize_key($_POST['reaction']) : '';

        if (!$comment_id || !get_comment($comment_id)) {
            wp_send_json_error(__("Invalid comment ID", 'kommenta'), 403);
        }


        $ipUser=$_SERVER['REMOTE_ADDR'];
        $voteForbidden=$this->checkVoteForbidded($comment_id, $ipUser);
        if($voteForbidden)
        {
            wp_send_json_success(['success' => false, 'comment_id' => $comment_id, 'reactions' => $returnVote->emotions, 'message' => __('You already voted', 'kommenta')]);
        }
        $returnVote=$this->addVoteComment($comment_id, $reaction, $ipUser);
        wp_send_json_success(['success' => true, 'comment_id' => $comment_id, 'reactions' => $returnVote->emotions]);
    }
}

// Initialize plugin - use plugins_loaded for earlier AJAX registration
add_action('plugins_loaded', function() {
    Kommenta_Wordpress::get_instance();
});


// Hook comment text: 2nd param is the comment object (has comment_ID, comment_post_ID, etc.)
add_filter('comment_text', function ($comment_text, $comment, $args) {
    $comment_id = isset($comment->comment_ID) ? (int) $comment->comment_ID : 0;
    $commentEmotions=Kommenta_Wordpress::getVoteComment($comment->comment_ID);
    $allTypes=Kommenta_Wordpress::getVoteType();
    
    if($commentEmotions) {
        $totalVotes=array_sum((array) $commentEmotions->emotions);
        $totalEmotionPurcentage=($totalVotes==0) ? 1 : $totalVotes;
        $templatedKommenta=Kommenta_Wordpress::generateTemplateTypeVote($allTypes, $commentEmotions->emotions);
        $kommenta_templateHTML = '<div class="container-kommenta-inline" data-comment-id="' . esc_attr($comment_id) . '">'
        . $templatedKommenta
        . '<div class="emotion-tooltip"><span class="badge-emotion"></span><span class="emotion-name"></span></div>'
        . '<p class="total-vote-count"><span>' . $totalVotes . '</span> ' . esc_html__('votes', 'kommenta') . '</p>'
        . '<p class="toast-comment"></p>'
        . '<svg xmlns="http://www.w3.org/2000/svg" class="loader-comment" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M136,32V64a8,8,0,0,1-16,0V32a8,8,0,0,1,16,0Zm37.25,58.75a8,8,0,0,0,5.66-2.35l22.63-22.62a8,8,0,0,0-11.32-11.32L167.6,77.09a8,8,0,0,0,5.65,13.66ZM224,120H192a8,8,0,0,0,0,16h32a8,8,0,0,0,0-16Zm-45.09,47.6a8,8,0,0,0-11.31,11.31l22.62,22.63a8,8,0,0,0,11.32-11.32ZM128,184a8,8,0,0,0-8,8v32a8,8,0,0,0,16,0V192A8,8,0,0,0,128,184ZM77.09,167.6,54.46,190.22a8,8,0,0,0,11.32,11.32L88.4,178.91A8,8,0,0,0,77.09,167.6ZM72,128a8,8,0,0,0-8-8H32a8,8,0,0,0,0,16H64A8,8,0,0,0,72,128ZM65.78,54.46A8,8,0,0,0,54.46,65.78L77.09,88.4A8,8,0,0,0,88.4,77.09Z"></path></svg>'
        . '</div>';
    } else {
        // Generate template for comments with no votes yet using configured vote types
        $emptyVotesTemplate = '';
        foreach($allTypes as $type) {
            $emptyVotesTemplate .= '<div class="emotion-reaction" data-reaction="' . esc_attr($type['slug']) . '" data-number="0" data-label="' . esc_attr__($type['label'], 'kommenta') . '" style="background: ' . esc_attr($type['color']) . ';"></div>';
        }
        
        $kommenta_templateHTML = '<div class="container-kommenta-inline" data-comment-id="' . esc_attr($comment_id) . '">'
        . $emptyVotesTemplate
        . '<div class="emotion-tooltip"><span class="badge-emotion"></span><span class="emotion-name"></span></div>'
        . '<p class="total-vote-count"><span>0</span> ' . esc_html__('votes', 'kommenta') . '</p>'
        . '<p class="toast-comment"></p>'
        . '<svg xmlns="http://www.w3.org/2000/svg" class="loader-comment" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M136,32V64a8,8,0,0,1-16,0V32a8,8,0,0,1,16,0Zm37.25,58.75a8,8,0,0,0,5.66-2.35l22.63-22.62a8,8,0,0,0-11.32-11.32L167.6,77.09a8,8,0,0,0,5.65,13.66ZM224,120H192a8,8,0,0,0,0,16h32a8,8,0,0,0,0-16Zm-45.09,47.6a8,8,0,0,0-11.31,11.31l22.62,22.63a8,8,0,0,0,11.32-11.32ZM128,184a8,8,0,0,0-8,8v32a8,8,0,0,0,16,0V192A8,8,0,0,0,128,184ZM77.09,167.6,54.46,190.22a8,8,0,0,0,11.32,11.32L88.4,178.91A8,8,0,0,0,77.09,167.6ZM72,128a8,8,0,0,0-8-8H32a8,8,0,0,0,0,16H64A8,8,0,0,0,72,128ZM65.78,54.46A8,8,0,0,0,54.46,65.78L77.09,88.4A8,8,0,0,0,88.4,77.09Z"></path></svg>'
        . '</div>';
    }
    
    return $comment_text . $kommenta_templateHTML;
}, 10, 3);