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
    private static $notifyOptionKey = 'kommenta_notify_enabled';
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
        add_action('wp_ajax_kommenta_save_notification', array($this, 'save_notification_ajax'));
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
            "data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzNDIuNTYgMzQyLjU2Ij4KICA8cGF0aCBkPSJNMTcxLjI4LDBDNzYuNzMuMS4xLDc2LjczLDAsMTcxLjI4djE0My44OGMwLDE1LjE0LDEyLjI3LDI3LjQsMjcuNCwyNy40aDE0My44OGM5NC42LDAsMTcxLjI4LTc2LjY5LDE3MS4yOC0xNzEuMjhTMjY1Ljg4LDAsMTcxLjI4LDBaTTE5MS44MywxNzguMTNjMCwxMS4zNS05LjIsMjAuNTUtMjAuNTUsMjAuNTVzLTIwLjU1LTkuMi0yMC41NS0yMC41NSw5LjItMjAuNTUsMjAuNTUtMjAuNTUsMjAuNTUsOS4yLDIwLjU1LDIwLjU1Wk0xMTYuNDcsMTc4LjEzYzAsMTEuMzUtOS4yLDIwLjU1LTIwLjU1LDIwLjU1cy0yMC41NS05LjItMjAuNTUtMjAuNTUsOS4yLTIwLjU1LDIwLjU1LTIwLjU1LDIwLjU1LDkuMiwyMC41NSwyMC41NVpNMjY3LjIsMTc4LjEzYzAsMTEuMzUtOS4yLDIwLjU1LTIwLjU1LDIwLjU1cy0yMC41NS05LjItMjAuNTUtMjAuNTUsOS4yLTIwLjU1LDIwLjU1LTIwLjU1LDIwLjU1LDkuMiwyMC41NSwyMC41NVoiIHN0eWxlPSJmaWxsOiAjZmZmOyIvPgo8L3N2Zz4=",
            30
        );

        add_submenu_page('kommenta-settings', __('Kommenta Statistic', 'kommenta'), __('Statistiques', 'kommenta'), 'manage_options', 'kommenta-stats', array($this, 'render_stats_page'), 1 );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_kommenta-settings' && $hook!=='kommenta_page_kommenta-stats') {
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
     * Save notification setting via AJAX
     */
    public function save_notification_ajax() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kommenta_admin')) {
            wp_send_json_error(array('message' => __('Security check failed', 'kommenta')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'kommenta')));
            return;
        }

        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? '1' : '0';
        update_option(self::$notifyOptionKey, $enabled);

        wp_send_json_success(array('enabled' => $enabled));
    }

    /**
     * Check if notifications are enabled
     */
    public static function isNotifyEnabled() {
        return get_option(self::$notifyOptionKey, '0') === '1';
    }

    /**
     * Send notification email to comment author when someone reacts
     */
    private function sendReactionNotification($comment_id, $reaction) {
        if (!self::isNotifyEnabled()) {
            return;
        }

        $comment = get_comment($comment_id);
        if (!$comment || empty($comment->comment_author_email)) {
            return;
        }

        // Don't notify if the comment author email is empty or invalid
        if (!is_email($comment->comment_author_email)) {
            return;
        }

        $vote_types = self::getVoteType();
        $reaction_label = $reaction;
        foreach ($vote_types as $type) {
            if ($type['slug'] === $reaction) {
                $reaction_label = $type['label'];
                break;
            }
        }

        $post = get_post($comment->comment_post_ID);
        $post_title = $post ? $post->post_title : __('Unknown post', 'kommenta');
        $post_url = get_permalink($comment->comment_post_ID);

        $subject = sprintf(
            /* translators: %s: post title */
            __('Someone reacted to your comment on "%s"', 'kommenta'),
            $post_title
        );

        $message = sprintf(
            /* translators: 1: comment author name, 2: reaction label, 3: comment excerpt, 4: post title, 5: post URL */
            __("Hi %1\$s,\n\nSomeone just reacted with \"%2\$s\" to your comment:\n\n\"%3\$s\"\n\non the post \"%4\$s\".\n\nSee the post: %5\$s\n\n Kommenta", 'kommenta'),
            $comment->comment_author,
            $reaction_label,
            wp_trim_words($comment->comment_content, 20, '…'),
            $post_title,
            $post_url
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail($comment->comment_author_email, $subject, $message, $headers);
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
        $notify_enabled = self::isNotifyEnabled();
        include_once(__DIR__ . '/templates/komenta-admin-page.php');
    }

    /**
     * Render the statistics page
     */
    public function render_stats_page() {
        $vote_types = self::getVoteType();

        // Fetch all comments that have kommenta meta
        global $wpdb;
        $meta_key = $this->commentMetaKey;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT cm.comment_id, cm.meta_value, c.comment_content, c.comment_author, c.comment_date, c.comment_post_ID
                 FROM {$wpdb->commentmeta} cm
                 INNER JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
                 WHERE cm.meta_key = %s",
                $meta_key
            )
        );

        $all_comments_stats = array();
        $emotion_totals = array();

        // Initialize emotion totals from configured vote types
        foreach ($vote_types as $type) {
            $emotion_totals[$type['slug']] = 0;
        }

        foreach ($results as $row) {
            $meta = json_decode($row->meta_value);
            if (!$meta || !isset($meta->emotions)) {
                continue;
            }

            $emotions = (array) $meta->emotions;
            $total_votes = array_sum($emotions);

            if ($total_votes === 0) {
                continue;
            }

            // Accumulate global emotion totals
            foreach ($emotions as $slug => $count) {
                if (isset($emotion_totals[$slug])) {
                    $emotion_totals[$slug] += $count;
                }
            }

            $post_title = get_the_title($row->comment_post_ID);

            $all_comments_stats[] = array(
                'comment_id'      => $row->comment_id,
                'comment_content' => wp_trim_words($row->comment_content, 20, '…'),
                'comment_author'  => $row->comment_author,
                'comment_date'    => $row->comment_date,
                'post_title'      => $post_title,
                'post_id'         => $row->comment_post_ID,
                'emotions'        => $emotions,
                'total_votes'     => $total_votes,
            );
        }

        // Sort by total votes descending
        usort($all_comments_stats, function ($a, $b) {
            return $b['total_votes'] - $a['total_votes'];
        });

        // Top 5 comments
        $top_5_comments = array_slice($all_comments_stats, 0, 5);

        // Sort emotion totals descending
        arsort($emotion_totals);

        // Grand total of all votes
        $grand_total_votes = array_sum($emotion_totals);

        // Build a color map from vote types for easy access
        $color_map = array();
        $label_map = array();
        foreach ($vote_types as $type) {
            $color_map[$type['slug']] = $type['color'];
            $label_map[$type['slug']] = $type['label'];
        }

        include_once(__DIR__ . '/templates/komenta-stats-page.php');
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

        // Send email notification to comment author if enabled
        $this->sendReactionNotification($comment_id, $reaction);

        wp_send_json_success(['success' => true, 'comment_id' => $comment_id, 'reactions' => $returnVote->emotions]);
    }
}

// Initialize plugin - use plugins_loaded for earlier AJAX registration
add_action('plugins_loaded', function() {
    Kommenta_Wordpress::get_instance();
});


// Hook comment text: 2nd param is the comment object (has comment_ID, comment_post_ID, etc.)
add_filter('comment_text', function ($comment_text, $comment, $args) {
    // Prevent show the front version on Wordpress backoffice
    if(!is_single()) {
        return $comment_text;
    }
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