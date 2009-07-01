<?php
/**
 * @package CloudFront
 * @author Daniel Shipton
 * @version 1.0
 */

// Uses S3 php class from http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1448&categoryID=47
// http://undesigned.org.za/2007/10/22/amazon-s3-php-class


function r_glob($dir, $pattern)
{
    $files = Array();
    $file_tmp= glob($dir. sql_regcase($pattern), GLOB_BRACE | GLOB_MARK | GLOB_NOSORT);
    $paths_tmp= glob($dir.'*',GLOB_MARK | GLOB_NOSORT | GLOB_ONLYDIR);

    foreach($file_tmp as $item){ $files[] = $item; }
    foreach($paths_tmp as $tmp_path){ $files = array_merge($files,r_glob($tmp_path, $pattern)); }
    return $files;
}

function get_dropin_cloudfront_file_exts()
{
   $file_exts = get_option('dropin_cloudfront_file_exts');
   if( empty($file_exts) )
   {
      $file_exts = Array('gif','jpg');
      set_dropin_cloudfront_file_exts($file_exts);
   }
   return $file_exts;
}

function set_dropin_cloudfront_file_exts($file_exts)
{
   update_option('dropin_cloudfront_file_exts', $file_exts);
}

function get_dropin_cloudfront_files_by_ext($file_ext)
{
   return get_option('dropin_cloudfront_'.$file_ext.'_hash');
}

function set_dropin_cloudfront_files_by_ext($file_hash, $file_ext)
{
   update_option('dropin_cloudfront_'.$file_ext.'_hash', $file_hash);
}

function get_dropin_cloudfront_files()
{
   return get_option('dropin_cloudfront_files_hash');
}

function set_dropin_cloudfront_files($file_hash)
{
   update_option('dropin_cloudfront_files_hash', $file_hash);
}


function update_dropin_cloudfront_file_cache($file_hash)
{
   foreach($file_hash as $filename=>$hashname)
   {
      wp_cache_add($filename, $hashname, 'dropin_cloudfront_hashcache');
   }
}

function dropin_cloudfront_open_s3_connection()
{
   if (!class_exists('S3')) require_once 'S3.php';

   // AWS access info
   if (!defined('awsAccessKey')) define('awsAccessKey', '');
   if (!defined('awsSecretKey')) define('awsSecretKey', '');

   // Check for CURL
   if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll'))
   {
      echo "<br>ERROR: CURL extension not loaded<br><br>";
   }

   S3::setAuth(awsAccessKey, awsSecretKey);
}

function dropin_cloudfront_list_current_s3_files()
{
   $bucket = '';
   // Get a list of files already there!!!
   $bucketdump = S3::getBucket( $bucket );
   $filelist = Array();
   foreach($bucketdump as $filename=>$fileattrs)
   {
      $filelist[] = $filename;
   }
   return $filelist;
}

function dropin_cloudfront_find_and_hash()
{
   $file_exts = get_dropin_cloudfront_file_exts();
   $hashed_array = Array();
   foreach($file_exts as $fext)
   {
      $filename_array = r_glob(ABSPATH, '*.'.$fext);

      foreach($filename_array as $filename)
      {
         $hashed_array[$filename] = hash_file('sha1', $filename) . '.' . $fext;
      }

   }
   return $hashed_array;
}

function dropin_cloudfront_get_mime_types()
{
   // http://en.wikipedia.org/wiki/Internet_media_type
   $mime_types = Array();
   $mime_types['jpg'] = 'image/jpeg';
   $mime_types['gif'] = 'image/gif';
   $mime_types['png'] = 'image/png';
   $mime_types['js'] = 'application/javascript';
   $mime_types['css'] = 'text/css';

   return $mime_types;
}

function dropin_cloudfront_put_missing_files($missing_files)
{
   $bucket = '';
   $mime_types = dropin_cloudfront_get_mime_types();
   foreach($missing_files as $filename=>$hashedname)
   {
      $file_info = pathinfo($hashedname);
      $custom_headers = Array();
      //$custon_headers['Content-Encoding'] = 'gzip';
      $custom_headers['Content-Type'] = $mime_types[$file_info['extension']];
      //$content_type['Expires'] = 'application/x-javascript';

      //$put_object = S3::inputFile($filename);
      // OR
      //$put_object = S3::inputResource($handle, $bufferSize, $md5sum); // GZIP!!!!

      $meta_headers = Array();
      S3::putObject( S3::inputFile($filename), $bucket, $hashedname, S3::ACL_PUBLIC_READ, $meta_headers, $custom_headers );
      echo "put $filename to S3 as $hashedname <br>";
   }
   echo "done! <br>";
}

function dropin_cloudfront_update_s3_files()
{
   dropin_cloudfront_open_s3_connection();
   $current_s3_files = dropin_cloudfront_list_current_s3_files();
   $hashed_files = dropin_cloudfront_find_and_hash();
   $missing_files = Array();
   foreach( $hashed_files as $filename=>$hashedname )
   {
      if(! in_array($hashedname, $current_s3_files) )
      {
         echo "Missing $filename in S3 bucket<br>";
         $missing_files[$filename] = $hashedname;
      }
      else
      {
         echo ".";
      }
   }
   echo "<br>";
   //dropin_cloudfront_put_missing_files($missing_files);
   //set_dropin_cloudfront_files($hashed_files);
}

//add_action('admin_footer', 'dropin_cloudfront_update_s3_files');
//add_filter('get_header', 'cloudfront_replace_attachment_url');
//add_filter('get_footer', 'cloudfront_replace_attachment_url');
//add_filter('get_sidebar', 'cloudfront_replace_attachment_url');

function cloudfront_dropin_template_redirect()
{
   // http://wordpress.org/support/topic/58626
   // Full page filtering!!!!
   ob_start('cloudfront_replace_attachment_url');
}

//add_filter('the_content', 'cloudfront_replace_attachment_url');
//add_action('template_redirect', 'cloudfront_dropin_template_redirect');

function cloudfront_replace_attachment_url($content)
{
   $s3_cache_url = 'http://';
   $hashed_files = get_dropin_cloudfront_files();
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

?>
