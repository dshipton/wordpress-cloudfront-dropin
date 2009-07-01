<?php
/**
 * Manages local file mapping to the respective S3 based assets.
 * @author Daniel E. Shipton
 * @package cloudfront-dropin
 * @since 1.0
 */
class CloudFrontDropInFileManager
{
   function CloudFrontDropInFileManager()
   {
   }

   function MapLocalFiles()
   {
      $file_exts = $this->GetFileExtensions();
      $hashed_array = Array();
      foreach($file_exts as $fext)
      {
         $filename_array = $this->RecursiveGlobByExtension(ABSPATH, $fext);

         foreach($filename_array as $filename)
         {
            $hashed_array[$filename] = hash_file('sha1', $filename) . '.' . $fext;
         }

      }
      return $hashed_array;
   }

   /**
    * Get the file extensions that are to be synced with CloudFront.
    *
    * @return array An array that contains the file extensions to sync.
    */
   function GetFileExtensions()
   {
      $file_exts = get_option('dropin_cloudfront_file_exts');
      if( empty($file_exts) )
      {
         $file_exts = Array('gif','jpg');
         $this->SetFileExtensions($file_exts);
      }
      return $file_exts;
   }

   /**
    * Set the file extensions that are to be synced with CloudFront.
    *
    * @param $fileExtensions array The extensions to sync.
    */
   function SetFileExtensions($fileExtensions)
   {
      update_option('dropin_cloudfront_file_exts', $fileExtensions);
   }

   /**
    * Get the file map pertaining to the given file extension.
    *
    * @param $fileExtension string The extension to look up the file map for.
    * @return array The file map for the given extension.
    */
   function GetFileMapByExtension($fileExtension)
   {
      return get_option('dropin_cloudfront_'.$fileExtension.'_hash');
   }

   /**
    * Set the file map for a given extension.
    *
    * @param $fileMap array The file map for the given extension.
    * @param $fileExtension string The extension corresponding to the file map.
    */
   function SetFileMapByExtension($fileMap, $fileExtension)
   {
      update_option('dropin_cloudfront_'.$fileExtension.'_hash', $fileMap);
   }

   /**
    * Gets the file map of all extensions.
    *
    * @return array The extensions to sync.
    */
   function GetFileMap()
   {
      return get_option('dropin_cloudfront_files_hash');
   }

   /**
    * Sets the file map of all files and extensions.
    *
    * @param $filemap array The file map of all files and extensions.
    */
   function SetFileMap($fileMap)
   {
      update_option('dropin_cloudfront_files_hash', $fileMap);
   }

   /**
    * Updates WP cache of the file map of all files and extensions.
    *
    * @param $filemap array The file map of all files and extensions.
    */
   function UpdateFileMapCache($fileMap)
   {
      foreach($fileMap as $filename=>$hashname)
      {
         wp_cache_add($filename, $hashname, 'dropin_cloudfront_hashcache');
      }
   }

   /**
    * Recursively find files matching the specified pattern from a given directory.
    *
    * @param $baseDirectory string The directory to start searching from.
    * @param $fileExtension string The file extension to search for.
    * @return array An array that contains all the matching files.
    */
   function RecursiveGlobByExtension($baseDirectory, $fileExtension)
   {
      $files = Array();
      $file_tmp = glob($dir . sql_regcase('*.' . $fileExtension), GLOB_BRACE | GLOB_MARK | GLOB_NOSORT);
      $paths_tmp = glob($dir . '*', GLOB_MARK | GLOB_NOSORT | GLOB_ONLYDIR);

      foreach($file_tmp as $item)
      {
         $files[] = $item;
      }
      foreach($paths_tmp as $tmp_path)
      {
         $files = array_merge($files, $this->RecursiveGlobByExtension($tmp_path, $fileExtension));
      }
      return $files;
   }
}

?>
