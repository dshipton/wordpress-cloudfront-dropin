<?php
/*
 * License!
 *
 */

/**
 * Manages syncing local assets to S3 based assets.
 * @author Daniel E. Shipton
 * @package cloudfront-dropin
 * @since 1.0
 */
class CloudFrontDropInSyncManager
{
   function CloudFrontDropInSyncManager()
   {
   }

   function OpenS3Connection()
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

   function ListS3Files()
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

   function GetMimeTypes()
   {
      // http://en.wikipedia.org/wiki/Internet_media_type
      $mime_types = Array();
      $mime_types['css'] = 'text/css';
      $mime_types['gif'] = 'image/gif';
      $mime_types['jpg'] = 'image/jpeg';
      $mime_types['js']  = 'application/javascript';
      $mime_types['pdf'] = 'application/pdf';
      $mime_types['png'] = 'image/png';
      $mime_types['tif'] = 'image/tiff';

      return $mime_types;
   }

   function UploadMissingOrChangedFiles($missing_files)
   {
      $bucket = '';
      $mime_types = $this->GetMimeTypes();

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

   function UpdateS3Files()
   {
      CloudFrontDropInSyncManager::OpenS3Connection();
      $current_s3_files = CloudFrontDropInSyncManager::ListS3Files();
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

}

?>
