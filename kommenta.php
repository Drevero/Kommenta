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
    public function __construct() {
        // Front CSS and JS registering
        wp_register_style( 'komenta-front', plugins_url( 'assets/css/komenta-front.css', __FILE__ ) );
        wp_register_script( 'komenta-front-dynamic', plugins_url( 'assets/js/komenta-front.js', __FILE__ ) );
        add_action('wp', array($this, "kommenta_check_single"));
    }

    /**
     * Function used to inject stylesheet and JS only in single page
     * To avoid enqueuing on non concerned page
     */
    public function kommenta_check_single() {
        if(!is_single()) { return; }
        wp_enqueue_style('komenta-front');
        wp_enqueue_script('komenta-front-dynamic');
    }
}

$instange=new Kommenta_Wordpress;

// Hook test comment
add_filter('comment_text', function ($comment) {
    $kommenta_templateHTML = '<div class="container-kommenta-inline">'
        . '<div class="emotion-reaction" data-reaction="positive" data-label="Positif" style="background: #6bc9a0;"></div>'
        . '<div class="emotion-reaction" data-reaction="negative" data-label="Negatif" style="background: #e88a94;"></div>'
        . '<div class="emotion-reaction" data-reaction="neutral" data-label="Neutral" style="background: #9ba3d0;"></div>'
        . '<div class="emotion-tooltip"><span class="badge-emotion"></span><span class="emotion-name"></span></div>'
        . '</div>';
    return $comment . $kommenta_templateHTML;
});