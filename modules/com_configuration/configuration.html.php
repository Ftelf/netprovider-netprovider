<?php

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

class HTML_Configuration {
	/**
	 * showConfiguration
	 * @param $conf
	 */
	static function showConfiguration(&$conf) {
		global $core;
?>

<div id="content-box">
  <div class="padding">
    <div id="toolbar-box">
      <div class="t">
        <div class="t">
          <div class="t"></div>
        </div>
      </div>

      <div class="m">
        <div class="header icon-48-configuration">
          <?php echo _("Configuration list"); ?>
        </div>

        <div class="clr"></div>
      </div>
       <div class="b">
        <div class="b">
          <div class="b"></div>
        </div>
      </div>
    </div>

    <div class="clr"></div>

    <div id="element-box">
    <form action="index2.php" method="post" name="adminForm">
<?php
	$indent = "";
	foreach ($conf as $k => $v) {
		echo $indent;
?>
    <table class="adminlist">
    <thead>
    <tr>
     <th colspan="2" class="title"><?php echo $k ?></th>
    </tr>
    </thead>
    <tbody>
<?php
		$k = 0;
		foreach ($v as $name => $value) {
?>
    <tr class="<?php echo "row$k"; ?>">
     <td width="10%">
       <?php echo $name; ?>
     </td>
     <td>
       '<?php echo $value; ?>'
     </td>
   </tr>
<?php
			$k = 1 - $k;
		}
?>
   </tbody>
   </table>
<?php
	$indent = "<br/>";
	}
?>
   <input type="hidden" name="option" value="com_configuration" />
    </form>
    </div>
    
    <div class="clr"></div>
  
  </div>

  <div class="clr"></div>
</div>
<?php
	}
}
?>