<?php
/*
 * License!
 *
 */

function CloudFrontDropInAdminPage()
{
   if( isset($_POST['TriggerManualDropInS3Sync']) )
   {
      echo '<div class="updated"><p><strong>S3 Synced Updated.</strong></p></div>';
   }
   else
   {
      echo '<div class=wrap>';
      echo '<form method="post" action="' . $_SERVER["REQUEST_URI"] . '">';
      echo '<h2>S3 and Cloudfront DropIn Plugin</h2>';
      echo '<div class=submit>';
      echo '<input type="submit" name="TriggerManualDropInS3Sync" value="Trigger S3 Sync" />';
      echo '</div></form></div>';
   }
}

function CloudFrontDropInAdminGUI()
{
   add_options_page('S3 Cloudfront DropIn', 'S3 Cloudfront DropIn', 9, basename(__FILE__), 'CloudFrontDropInAdminPage');
}

add_action('admin_menu', 'CloudFrontDropInAdminGUI');
?>
