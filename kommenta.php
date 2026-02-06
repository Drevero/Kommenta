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

    public function __construct() {
        // Load translations
        add_action('init', array($this, 'load_textdomain'));

        // Front CSS and JS registering
        wp_register_style( 'komenta-front', plugins_url( 'assets/css/komenta-front.css', __FILE__ ) );
        wp_register_script( 'komenta-front-dynamic', plugins_url( 'assets/js/komenta-front.js', __FILE__ ) );
        add_action('wp', array($this, "kommenta_check_single"));
    
        // Registering AJAX endpoint to receive vote (logged-in + guest)
        add_action( 'wp_ajax_komenta_vote', array($this, 'komenta_vote_ajax'));
        add_action( 'wp_ajax_nopriv_komenta_vote', array($this, 'komenta_vote_ajax'));
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('kommenta', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Function used to inject stylesheet and JS only in single page
     * To avoid enqueuing on non concerned page
     */
    public function kommenta_check_single() {
        if(!is_single()) { return; }
        wp_enqueue_style('komenta-front');
        wp_enqueue_script('komenta-front-dynamic');
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

    public static function getVoteType() {
        $defaultTypes=[[
            'label' => "Positive",
            'slug' => "positive",
            'color' => "#6bc9a0"
        ], [
            'label' => "Negative",
            'slug' => "negative",
            'color' => "#e88a94"
        ], [
            'label' => "Neutral",
            'slug' => "neutral",
            'color' => "#9ba3d0"
        ],
        [
            'label' => "Fictive",
            'slug' => "fictive",
            'color' => "#010101"
        ]];
        return $defaultTypes;
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
            // Convert to object for consistent return type
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


        // TO DO : Store and check IP to forbid multiple vote for the same user
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

add_action('init', function() {
    $komentaInstance=new Kommenta_Wordpress;
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
        $kommenta_templateHTML = '<div class="container-kommenta-inline" data-comment-id="' . esc_attr($comment_id) . '">'
        . '<div class="emotion-reaction" data-reaction="positive" data-number="0" data-label="' . esc_attr__('Positive', 'kommenta') . '" style="background: #6bc9a0;"></div>'
        . '<div class="emotion-reaction" data-reaction="negative" data-number="0" data-label="' . esc_attr__('Negative', 'kommenta') . '" style="background: #e88a94;"></div>'
        . '<div class="emotion-reaction" data-reaction="neutral" data-number="0" data-label="' . esc_attr__('Neutral', 'kommenta') . '" style="background: #9ba3d0;"></div>'
        . '<div class="emotion-tooltip"><span class="badge-emotion"></span><span class="emotion-name"></span></div>'
        . '<p class="total-vote-count"><span>0</span> ' . esc_html__('votes', 'kommenta') . '</p>'
        . '<p class="toast-comment"></p>'
        . '<svg xmlns="http://www.w3.org/2000/svg" class="loader-comment" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M136,32V64a8,8,0,0,1-16,0V32a8,8,0,0,1,16,0Zm37.25,58.75a8,8,0,0,0,5.66-2.35l22.63-22.62a8,8,0,0,0-11.32-11.32L167.6,77.09a8,8,0,0,0,5.65,13.66ZM224,120H192a8,8,0,0,0,0,16h32a8,8,0,0,0,0-16Zm-45.09,47.6a8,8,0,0,0-11.31,11.31l22.62,22.63a8,8,0,0,0,11.32-11.32ZM128,184a8,8,0,0,0-8,8v32a8,8,0,0,0,16,0V192A8,8,0,0,0,128,184ZM77.09,167.6,54.46,190.22a8,8,0,0,0,11.32,11.32L88.4,178.91A8,8,0,0,0,77.09,167.6ZM72,128a8,8,0,0,0-8-8H32a8,8,0,0,0,0,16H64A8,8,0,0,0,72,128ZM65.78,54.46A8,8,0,0,0,54.46,65.78L77.09,88.4A8,8,0,0,0,88.4,77.09Z"></path></svg>'
        . '</div>';
    }
    
    return $comment_text . $kommenta_templateHTML;
}, 10, 3);