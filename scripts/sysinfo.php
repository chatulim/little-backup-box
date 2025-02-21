<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<script src="js/refresh_iframe.js"></script>
	<script src="js/refresh_site.js"></script>
</head>

<body onload="refreshIFrame(); refresh_site()" <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::sysinfo_sysinfo; ?></h1>

	<div class="card">
		<?php
		$temp = shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
		$temp = round((float) $temp / 1000, 1);

		$cpuusage = 100 - (float) shell_exec("vmstat | tail -1 | awk '{print $15}'");

		$mem_ram_frac = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
		$mem_ram_all = shell_exec("free | grep Mem | awk '{print $2 / 1024}'");
		$mem_ram = round((float) $mem_ram_frac, 1) . " % * " . round((float) $mem_ram_all) . " MB";

		$mem_swap_frac = shell_exec("free | grep Swap | awk '{print $3/$2 * 100.0}'");
		$mem_swap_all = shell_exec("free | grep Swap | awk '{print $2 / 1024}'");
		$mem_swap = round($mem_swap_frac, 1) . " % * " . round($mem_swap_all) . " MB";

		$abnormal_conditions = shell_exec("sudo python3 ${WORKING_DIR}/lib_system.py get_abnormal_system_conditions");

			if (isset($temp) && is_numeric($temp)) {
				echo "<p>" . L::sysinfo_temp . ": <strong>" . $temp . "°C</strong></p>";
			}

			if (isset($cpuusage) && is_numeric($cpuusage)) {
				echo "<p>" . L::sysinfo_cpuload . ": <strong>" . $cpuusage . "%</strong></p>";
			}

			echo "<p>" . L::sysinfo_memory_ram . ": <strong>" . $mem_ram . "</strong></p>";

			echo "<p>" . L::sysinfo_memory_swap . ": <strong>" . $mem_swap . "</strong></p>";

			echo "<p>" . L::sysinfo_conditions . ": <strong>" . $abnormal_conditions . "</strong></p>";

		?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_devices; ?></h3>
			<?php
			echo '<pre>';
			passthru("sudo lsblk");
			echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_diskspace; ?></h3>
			<?php
				echo '<pre>';
				passthru("sudo df -H");
				echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_camera; ?></h3>
			<?php
				echo '<pre>';
					exec("sudo gphoto2 --summary | grep 'Model' | cut -d: -f2 | tr -d ' '",$DEVICES);
					if (count($DEVICES)) {
						echo "<ul>";
							foreach ($DEVICES as $DEVICE) {
								$DEVICE=mb_ereg_replace("([^a-zA-Z0-9-_\.])", '_', $DEVICE);
								echo "<li>$DEVICE</li>";
							}
						echo "</ul>";
					}

					echo '<h3>' . L::sysinfo_camera_serial.'</h3>';
					exec("sudo gphoto2 --summary | grep 'Serial Number' | cut -d: -f2 | tr -d ' '",$SERIALS);
					if (count($SERIALS)) {
						echo "<ul>";
							foreach ($SERIALS as $SERIAL) {
								$SERIAL=mb_ereg_replace("([^a-zA-Z0-9-_\.])", '_', $SERIAL);
								echo "<li>$SERIAL</li>";
							}
						echo "</ul>";
					}
					else
					{
						echo "-";
					}

					echo '<h3>' . L::sysinfo_camera_storages.'</h3>';
					exec("sudo gphoto2 --storage-info | grep 'basedir' | cut -d= -f2 | tr -d ' '",$STORAGES);
					if (count($STORAGES)) {
						echo "<ul>";
							foreach ($STORAGES as $STORAGE) {
								echo "<li>$STORAGE</li>";
							}
						echo "</ul>";
					}
					else
					{
						echo "-";
					}
				echo '</pre>';
			?>
	</div>

	<div class="text-center"><button onClick="history.go(0)" role="button"><?php echo (L::sysinfo_refresh_button); ?></button></div>

	<?php include "sub-logmonitor.php"; ?>

	<?php include "sub-footer.php"; ?>

</body>

</html>
