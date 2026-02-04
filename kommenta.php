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
        // Front CSS and JS registering
        wp_register_style( 'komenta-front', plugins_url( 'assets/css/komenta-front.css', __FILE__ ) );
        wp_register_script( 'komenta-front-dynamic', plugins_url( 'assets/js/komenta-front.js', __FILE__ ) );
        add_action('wp', array($this, "kommenta_check_single"));
    
        // Registering AJAX endpoint to receive vote (logged-in + guest)
        add_action( 'wp_ajax_komenta_vote', array($this, 'komenta_vote_ajax'));
        add_action( 'wp_ajax_nopriv_komenta_vote', array($this, 'komenta_vote_ajax'));
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
        ]);
    }

    public function getVoteComment($comment_id) {
        $commentMeta=get_comment_meta($comment_id, $this->commentMetaKey, true);
        if(!$commentMeta) {
            return false;
        }
        return json_decode($commentMeta);
    }

    public function addVoteComment($comment_id, $reaction) {
        $commentMeta=get_comment_meta($comment_id, $this->commentMetaKey, true);
        if(!$commentMeta) {
            // We initialise the comment array
            $metaStructure=['emotions' => []];
            $metaStructure['emotions'][$reaction]=1;
            $commentMeta=add_comment_meta($comment_id, $this->commentMetaKey, json_encode($metaStructure));
            return $metaStructure;
        } else {
            $commentMeta=json_decode($commentMeta);
            if($commentMeta->emotions->$reaction) {
                // Already exist, we increment
                $commentMeta->emotions->$reaction++;
            } else {
                // We init to 1
                $commentMeta->emotions->$reaction=1;  
            }
            update_comment_meta($comment_id, $this->commentMetaKey, json_encode($commentMeta));
            return $commentMeta;
        }
    }

    public function komenta_vote_ajax() {
        if(!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'komenta_vote')) {
            wp_send_json_error( "You're not allowed to execute this action sorry", 403 );
        }

        $comment_id = isset($_POST['id_comment']) ? (int) $_POST['id_comment'] : 0;
        $reaction   = isset($_POST['reaction']) ? sanitize_key($_POST['reaction']) : '';

        if (!$comment_id || !get_comment($comment_id)) {
            wp_send_json_error("Invalid comment id", 403);
        }


        // TO DO : Store and check IP to forbid multiple vote for the same user

        $returnVote=$this->addVoteComment($comment_id, $reaction);

        wp_send_json_success(['success' => true, 'comment_id' => $comment_id, 'reactions' => $returnVote->emotions]);
    }
}

$komentaInstance=new Kommenta_Wordpress;

// Hook comment text: 2nd param is the comment object (has comment_ID, comment_post_ID, etc.)
add_filter('comment_text', function ($comment_text, $comment, $args) use ($komentaInstance) {
    $comment_id = isset($comment->comment_ID) ? (int) $comment->comment_ID : 0;
    $commentEmotions=$komentaInstance->getVoteComment($comment->comment_ID);
    $numberEmotions=3;

    if($commentEmotions) {
        $totalEmotionPurcentage=$commentEmotions->emotions->positive+$commentEmotions->emotions->negative+$commentEmotions->emotions->neutral;
        $totalEmotionPurcentage=($totalEmotionPurcentage==0) ? 1 : $totalEmotionPurcentage;
        $kommenta_templateHTML = '<div class="container-kommenta-inline" data-comment-id="' . esc_attr($comment_id) . '">'
        . '<div class="emotion-reaction" data-reaction="positive" data-number="' . ($commentEmotions->emotions->positive) . '" data-label="Positif" style="background: #6bc9a0;width: ' . ($commentEmotions->emotions->positive*100/$totalEmotionPurcentage) . '%;"></div>'
        . '<div class="emotion-reaction" data-reaction="negative" data-number="' . ($commentEmotions->emotions->negative) . '" data-label="Negatif" style="background: #e88a94;width: ' . ($commentEmotions->emotions->negative*100/$totalEmotionPurcentage) . '%;"></div>'
        . '<div class="emotion-reaction" data-reaction="neutral" data-number="' . ($commentEmotions->emotions->neutral) . '" data-label="Neutral" style="background: #9ba3d0;width: ' . ($commentEmotions->emotions->neutral*100/$totalEmotionPurcentage) . '%;"></div>'
        . '<div class="emotion-tooltip"><span class="badge-emotion"></span><span class="emotion-name"></span></div>'
        . '</div>';
    } else {
        $kommenta_templateHTML = '<div class="container-kommenta-inline" data-comment-id="' . esc_attr($comment_id) . '">'
        . '<div class="emotion-reaction" data-reaction="positive" data-number="0" data-label="Positif" style="background: #6bc9a0;"></div>'
        . '<div class="emotion-reaction" data-reaction="negative" data-number="0" data-label="Negatif" style="background: #e88a94;"></div>'
        . '<div class="emotion-reaction" data-reaction="neutral" data-number="0" data-label="Neutral" style="background: #9ba3d0;"></div>'
        . '<div class="emotion-tooltip"><span class="badge-emotion"></span><span class="emotion-name"></span></div>'
        . '</div>';
    }
    
    return $comment_text . $kommenta_templateHTML;
}, 10, 3);