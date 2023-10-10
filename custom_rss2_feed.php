<?php
/*
Plugin Name: Custom Feed Plugin
Description: This plugin generates a custom RSS2 feed for the last 3 published blog posts.
Version: 1.0
Author: Steve Hodgkiss
Author URI: https://stevehodgkiss.net
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// Schedule the rss scan every 12 hours
function schedule_rss_scan()
{
    if (!wp_next_scheduled('rss_scan_event')) {
        wp_schedule_event(time(), 'twicedaily', 'rss_scan_event');
    }
}
add_action('wp', 'schedule_rss_scan');

// Hook to generate the custom RSS2 feed
function custom_feed_generate_rss2()
{
    // Set the content type to RSS2
    header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset'), true);

    // Create the RSS2 XML
    echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>' . PHP_EOL;
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
    echo '<channel>' . PHP_EOL;
    echo '<title>' . get_bloginfo('name') . '</title>' . PHP_EOL;
    echo '<link>' . get_bloginfo('url') . '</link>' . PHP_EOL;
    echo '<description>' . get_bloginfo('description') . '</description>' . PHP_EOL;
    echo '<atom:link href="' . esc_url(home_url('/customfeed')) . '" rel="self" type="application/rss+xml" />' . PHP_EOL;

    // Get the last 3 published posts
    $args = array(
        'post_status' => 'publish',
        'numberposts' => 3,
        'orderby' => 'post_date',
        'order' => 'DESC',
    );
    $recent_posts = wp_get_recent_posts($args);

    $readMore = ' Click the link or image below to read more';

    // Loop through the recent posts and add them to the feed
    foreach ($recent_posts as $post) {
        $postID = $post['ID'];
        $content = get_post_field('post_content', $postID);
        // store the featured image or first available image
        $image = get_the_post_thumbnail_url($postID, 'full');

        // optionally you can use the excerpt instead of the content
        $excerpt = strip_tags($content); // Remove HTML tags.
        $excerpt = wp_trim_words($excerpt, 200); // Adjust the word count as needed.
        // remove &hellip; from the end of the excerpt
        $excerpt = preg_replace('/&hellip;/', ' ...', $excerpt);

        // get the permalink, title, date and excerpt for the post
        $permalink = get_permalink($postID);
        $title = get_the_title($postID);

        // get the rss format date for the post
        $postDate = get_post_time('r', true, $postID);

        echo '<item>' . PHP_EOL;
        echo '<guid isPermaLink="false">' . $permalink . '</guid>' . PHP_EOL;
        echo '<title>' . $title . '</title>' . PHP_EOL;
        echo '<link>' . $permalink . '</link>' . PHP_EOL;
        echo '<pubDate>' . $postDate . '</pubDate>' . PHP_EOL;
        echo '<description>' . $excerpt . '</description>' . PHP_EOL;

        if ($image) {
            echo '<content:encoded><![CDATA[<img src="' . $image . '" alt="' . $title . '" width="800" /><br /><br />' . $excerpt . $readMore . ']]></content:encoded>';
        }

        echo '</item>' . PHP_EOL;
    }

    // Close the RSS2 XML
    echo '</channel>' . PHP_EOL;
    echo '</rss>' . PHP_EOL;

    // Exit to prevent WordPress from rendering anything else
    exit;
}
add_action('rss_scan_event', 'custom_feed_generate_rss2');

// Hook to add a new custom RSS2 feed endpoint
function custom_feed_add_feed_endpoint()
{
    add_feed('customfeed', 'custom_feed_generate_rss2');
}

add_action('init', 'custom_feed_add_feed_endpoint');

// Function to flush rewrite rules when the plugin is activated
function custom_feed_plugin_activation()
{
    custom_feed_add_feed_endpoint();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'custom_feed_plugin_activation');

// Function to flush rewrite rules when the plugin is deactivated
function custom_feed_plugin_deactivation()
{
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'custom_feed_plugin_deactivation');

// Disable RSS feeds
function disable_feeds()
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Feeds Are Disabled</title>
</head>
<body>
    <h1>RSS Feeds Are Disabled</h1>
    <p>This page indicates that RSS feeds are disabled on this website.</p>
</body>
</html>
HTML;
    exit;
}

add_action('do_feed', 'disable_feeds', 1);
add_action('do_feed_rdf', 'disable_feeds', 1);
add_action('do_feed_rss', 'disable_feeds', 1);
add_action('do_feed_rss2', 'disable_feeds', 1);
add_action('do_feed_atom', 'disable_feeds', 1);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'wp_generator');
