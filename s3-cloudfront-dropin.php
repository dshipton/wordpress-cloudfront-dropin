<?php
/*
Plugin Name: CloudFront Sync
Plugin URI: http://redshiptechnologies.com
Description: Checks for cloudfront ness.
Author: Daniel Shipton
Version: 1.0
Author URI: http://redshiptechnologies.com
*/

// Uses S3 php class from http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1448&categoryID=47
// http://undesigned.org.za/2007/10/22/amazon-s3-php-class

/*
 * License!
 *
 */

/**
 * Entry point and manager for the entire CloudFront DropIn plugin.
 * @author Daniel E. Shipton
 * @package cloudfront-dropin
 * @since 1.0
 */
class CloudFrontDropIn
{
   function CloudFrontDropIn()
   {
   }

   //add_action('admin_footer', 'dropin_cloudfront_update_s3_files');
   //add_filter('get_header', 'cloudfront_replace_attachment_url');
   //add_filter('get_footer', 'cloudfront_replace_attachment_url');
   //add_filter('get_sidebar', 'cloudfront_replace_attachment_url');

   function TemplateRedirect()
   {
      // http://wordpress.org/support/topic/58626
      // Full page filtering!!!!
      ob_start(array('CloudFrontDropIn','ReplaceWithMappedUrls'));
   }

   function Enable()
   {
      //add_filter('the_content', 'cloudfront_replace_attachment_url');
      add_action('template_redirect', array('CloudFrontDropIn','TemplateRedirect'));
      CloudFrontDropIn::EnableAdmin();
   }

   function EnableAdmin()
   {
      require_once( trailingslashit(dirname(__FILE__)) . 's3-cloudfront-dropin-gui.php' );
   }

   function ReplaceWithMappedUrls($content)
   {
      $s3_cache_url = 'http://';
      $hashed_files = get_option('dropin_cloudfront_files_hash');
      $blog_url = site_url() . '/';
      //$blog_url = 'http://localhost/~dshipton/wordpress/';
      $find_urls = Array();
      $replacement_urls = Array();
      foreach($hashed_files as $filename=>$hashedname)
      {
         $find_urls[] = str_replace(ABSPATH, $blog_url, $filename);
         $replacement_urls[] = $s3_cache_url.$hashedname;
      }
      return str_replace($find_urls, $replacement_urls, $content);
   }
}

if( defined('ABSPATH') && defined('WPINC') )
{
   add_action('init', array('CloudFrontDropIn', 'Enable'));
}
?>
