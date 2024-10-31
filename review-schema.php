<?php
/**
 * @package Review_Schema
 * @version 1.7.1
 */
/*
Plugin Name: Review Schema
Plugin URI: http://wordpress.org/extend/plugins/review-schema-markup/
Description: This plugin adds review schema.org markup.
Author: James Swindle
Version: 1.7.1
Author URI: http://jamesswindle.com/
*/

define('REVIEW_SCHEMA_VERSION', '1.7.1');

include plugin_dir_path(__FILE__).'options.php';

function add_review_schema($content) {
	global $wp_query;
	
	if( get_post_type() != 'post' || !is_single())
		return $content;
		
	$max_rate = get_option('review-max-rating', 5);
	$min_rate = get_option('review-interval-rating', 0.5);
	$show_rate = get_option('review-show-rating', 1);
	$fill_style = get_option('review-fill-style', 'horizontal');
	
	$postID = $wp_query->post->ID;
	
	$name = get_post_meta($postID, 'schema_item_name', true);
	$url = get_post_meta($postID, 'schema_item_url', true);
	$rating = (float) get_post_meta($postID, 'schema_rating', true);
	
	if($rating < $min_rate)
		return str_replace('{rating}', '', $content);
	
	if(!$show_rate){
		$style = ' style="display: none;"';
	} else {
		$style = '';
	}
	
	$customPlacement = strpos($content, '{rating}') !== false;
	
	if($customPlacement){
		$custom = '<div class="inline-rating"><span class="review-rating">' . $rating . '</span> / <span class="best-rating">' . $max_rate . '</span> stars ';
		$stars = $rating;
		for($i = 1; $i <= $max_rate; $i++){
			$custom .= '<span class="review-star-empty">';
			if($stars > 1){
				$custom .= '<span class="review-star full">&nbsp;</span>';
			} elseif($stars > 0) {
				$size = $stars * 16;
				if($fill_style == 'vertical'){
					$css = 'height: ' . $size . 'px;width:16px;background-position:0 -' . (16 - $size) . 'px;vertical-align:-' . (16 - $size) . 'px;';
				} else {
					$css = 'width: ' . $size . 'px;';
				}
				$custom .= '<span class="review-star" style="' . $css . '">&nbsp;</span>';	
			} else {
				$custom .= '&nbsp;';
			}
			$stars--;
			$custom .= '</span>';
		}
		$custom .= '</div>';
		$content = str_replace('{rating}', $custom, $content);
		$style = ' style="display: none;"';
	}
	$return = '<div itemscope itemtype="http://schema.org/Review"><div itemprop="reviewBody">' . "\n" . $content . "\n</div>";
	$return .= "\n\n<!-- Review Schema -->\n";
	$return .= '<meta itemprop="name" content="' . get_the_title() . '" />';
	$return .= '<meta itemprop="author" content="' . get_the_author() . '" />';
	$return .= '<meta itemprop="datePublished" content="' . get_the_date('c')  . '" />' . "\n";

	if($name || $url){
		$return .= '<div itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing">' . "\n";
		if($name)
			$return .= '<meta itemprop="name" content="' . $name . '" />' . "\n";
		if($url)
    		$return .= '<meta itemprop="url" content="' . $url . '" />' . "\n";
		$return .= "</div>\n";
	}

	$return .= '<div class="review-data" itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating"' . $style . '>';
	$return .= '<meta itemprop="worstRating" content="' . $min_rate . '" />';
    	$return .= '<span class="star-rating"><span itemprop="ratingValue" class="review-rating">' . $rating . '</span> / <span itemprop="bestRating" class="best-rating">' . $max_rate . '</span> stars</span>';

	$stars = $rating;
	for($i = 1; $i <= $max_rate; $i++){
		$return .= '<span class="review-star-empty">';
		if($stars > 1){
			$return .= '<span class="review-star full">&nbsp;</span>';
		} else {
			$size = $stars * 16;
			if($fill_style == 'vertical'){
				$css = 'height: ' . $size . 'px;width:16px;background-position:0 -' . (16 - $size) . 'px;vertical-align:middle;';
			} else {
				$css = 'width: ' . $size . 'px;';
			}
			$return .= '<span class="review-star" style="' . $css . '">&nbsp;</span>';	
		}
		$stars--;
		$return .= '</span>';
	}
	/*$return .= '<span class="review-blank">';
	$return .= '<span class="review-stars" style="width: ' . ($rating / $max_rate) * 84 . 'px;">&nbsp;</span>';
	$reutnr .= '</span>';*/
	$return .= '</div>';	
	
	
	
	$return .= '</div>';
	
	return $return;
}

function add_review_box() {
	add_meta_box('review_schema', 'Review Info', 'review_box', 'post', 'side', 'high');
}

function review_box($post) {
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'review_nonce' );
	
	$max_rate = get_option('review-max-rating', 5);
	$min_rate = get_option('review-interval-rating', 0.5);
	
	$min_rate = ($min_rate > 0)?$min_rate:0.5;
	
	// The actual fields for data entry
	echo '<label for="schema_item_name">Name of Item Reviewed</label><br>';
	echo '<input type="text" id="schema_item_name" name="schema_item_name" placeholder="Ex: Left 4 Dead" value="' . get_post_meta($post->ID, 'schema_item_name', true) . '" size="25" /><br>';
	
	echo '<label for="schema_item_url">URL to Item</label><br>';
	echo '<input type="text" id="schema_item_url" name="schema_item_url" placeholder="Ex: http://www.l4d.com" value="' . get_post_meta($post->ID, 'schema_item_url', true) . '" size="25" /><br>';
	
	echo '<label for="schema_rating">Rating</label><br>';
	echo '<select id="schema_rating" name="schema_rating" style="width:75px;">';
	
	$rating =  floatval(get_post_meta($post->ID, 'schema_rating', true));
	if(empty($rating) || $rating == 0)
		echo '<option value="0" selected>None</option>';
	else
		echo '<option value="0">None</option>';
				
	for($i = $min_rate; $i <= $max_rate; $i += $min_rate){
		$select = (number_format($rating,2,'.','') == number_format($i,2,'.',''))?" selected":"";
		echo '<option value="' . $i . '"'. $select . '>' . number_format($i, 1) . '</option>';
	}
	echo '</select>';
}

function review_save_postdata( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	  return;
	
	if ( !wp_verify_nonce( $_POST['review_nonce'], plugin_basename( __FILE__ ) ) )
	  return;
	
	
	// Check permissions
	if ( !current_user_can( 'edit_post', $post_id ) )
		return;
	
	
	$itemName = filter_var($_POST['schema_item_name'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_LOW);
	$itemURL = filter_var($_POST['schema_item_url'], FILTER_SANITIZE_URL);
	$rating = $_POST['schema_rating'];
	
	if(!update_post_meta($post_id, 'schema_item_name', $itemName))
		add_post_meta($post_id, 'schema_item_name', $itemName);
		
	if(!update_post_meta($post_id, 'schema_item_url', $itemURL))
		add_post_meta($post_id, 'schema_item_url', $itemURL);
		
	if(!update_post_meta($post_id, 'schema_rating', $rating))
		add_post_meta($post_id, 'schema_rating', $rating);
}

function add_review_header(){
	echo '<link type="text/css" rel="stylesheet" href="' . plugins_url( 'review-schema.css' , __FILE__ ) . '" />' . "\n";
}

add_filter('the_content', 'add_review_schema');

add_action('add_meta_boxes', 'add_review_box');
add_action('save_post', 'review_save_postdata');
add_action('wp_head', 'add_review_header');

if (is_admin()){
  add_action('admin_menu', 'add_review_options_page');
  add_action('admin_init', 'register_review_settings');
}


?>
