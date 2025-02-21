<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	ini_set("session.use_only_cookies", 0);
	ini_set("session.use_trans_sid", 1);

	session_start();
	session_write_close();

	$CMD			= $_SESSION['CMD'];
	$PARAM1			= $_SESSION['PARAM1'];
	$PARAM2			= $_SESSION['PARAM2'];
	$MAIL_RESULT	= $_SESSION['MAIL_RESULT'];

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include "${WORKING_DIR}/sub-security.php";
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
	<head>
		<script language="javascript">
			var int = self.setInterval("window.scrollBy(0,1000);", 200);
		</script>
	</head>
	<body>
		<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

		<?php
// 			allowed parameters

			if ($CMD !== '') {

				switch($CMD) {
					case 'update':
						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
						$COMMAND_LINE	.= ";cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | sudo -u pi bash";
						break;

					case 'update_development':
						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
						$COMMAND_LINE	.= ";cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/development/install-little-backup-box.sh | sudo -u pi bash -s -- development";
						break;

					case 'fsck':
						$DEVICE_FSTYPE	= exec("sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep /dev/".clean_argument($PARAM1));
						$DEVICE_FSTYPE	= explode('FSTYPE=',$DEVICE_FSTYPE)[1];
						$DEVICE_FSTYPE	= explode('"',$DEVICE_FSTYPE)[1];

						if ($PARAM2 == 'repair') {
							if ($DEVICE_FSTYPE	== 'exfat') {
								$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -p -y '/dev/".clean_argument($PARAM1)."'";
							}
							else {
								$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -p -f -y '/dev/".clean_argument($PARAM1)."'";
							}
						}
						else {
							$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE '/dev/".clean_argument($PARAM1)."'";
						}

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_fsck_start1 . "' ':" . L::box_cmd_fsck_start2 . "' ':" . clean_argument($PARAM2,array(' ')) . "'";
						$COMMAND_LINE	.= ";echo '$COMMAND_LINE'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_fsck_stop1 . "' ':" . L::box_cmd_fsck_stop2 . "' ':" . clean_argument($PARAM2,array(' ')) . "'";
						break;

					case 'format':
						if (($PARAM1 !== "-") and ($PARAM1 !== " ") and ($PARAM2 !== "-") and ($PARAM2 !== " ")) {
							if ($PARAM2 == "FAT32") {
								$MAIN_COMMAND	= "mkfs.vfat -v -I -F32 '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "exFAT") {
								$MAIN_COMMAND	= "mkfs.exfat '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "NTFS (compression enabled)") {
								$MAIN_COMMAND	= "mkfs.ntfs --enable-compression --force --verbose '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "NTFS (no compression)") {
								$MAIN_COMMAND	= "mkfs.ntfs --force --verbose '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "Ext4") {
								$MAIN_COMMAND	= "mkfs.ext4 -v -F '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "Ext3") {
								$MAIN_COMMAND	= "mkfs.ext3 -v -F '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "HFS Plus") {
								$MAIN_COMMAND	= "mkfs.hfsplus '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck.hfsplus '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "HFS") {
								$MAIN_COMMAND	= "mkfs.hfs '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck.hfs '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
							}

							else {
								$COMMAND_LINE	= '';
							}

						} else {
							$COMMAND_LINE	= '';
						}
						break;

					case 'f3':
						switch($PARAM2) {
							case 'f3probe_non_destructive':
								$MAIN_COMMAND	= "f3probe --time-ops '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_start1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
								break;

							case 'f3probe_destructive':
								$MAIN_COMMAND	= "f3probe --destructive --time-ops '/dev/".clean_argument($PARAM1)."'";

								$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_start1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
								break;

							default:
								$COMMAND_LINE	= "";
						}
						break;

					case 'comitup_reset':
						$MAIN_COMMAND	= "comitup-cli d";

						$COMMAND_LINE	= "echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::config_comitup_section . "' ':" . L::cmd_reset . "'";
						break;

					default:
						$COMMAND_LINE	= "";
				}

				if ($COMMAND_LINE !== "") {
					ob_implicit_flush(true);
					ob_end_flush();

					$descriptorspec = array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w")
					);

					# write lockfile
					$lockfile = fopen($constants["const_CMD_RUNNER_LOCKFILE"],"w");
					fwrite($lockfile, $COMMAND_LINE);
					fclose($lockfile);

					# start command
					echo ('WORKING...');
// 					echo ($COMMAND_LINE);
					$process = proc_open($COMMAND_LINE, $descriptorspec, $pipes, realpath('./'), array());

					echo '<pre>';

					if (is_resource($process)) {
						$RESULT = '';
						while ($s = fgets($pipes[1])) {
							if (strpos($s,'lib_display.py') == false) {
								print $s;
								$RESULT .= $s;
							}
						}
					}
					echo '</pre>';

					unlink($constants["const_CMD_RUNNER_LOCKFILE"]);

					if ($MAIL_RESULT) {
						$RESULT=str_replace('`',"'",$RESULT);
						$RESULT=str_replace('"',"'",$RESULT);

						shell_exec('sudo python3 ' . $WORKING_DIR . '/lib_mail.py "' . $CMD . ' ' . clean_argument($PARAM1,array(' ')) . ' ' . clean_argument($PARAM2,array(' ')) . '" "' . $RESULT . '"');
					}
				}
			} else {
				echo "NOT AUTHORISED";
			}
		?>

	</body>
</html>

