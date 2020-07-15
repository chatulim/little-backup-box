<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<meta charset="utf-8">
	<title>Little Backup Box</title>
	<link rel="shortcut icon" href="favicon.png" />
	<link rel="stylesheet" href="css/lit.css">
	<link href="https://fonts.googleapis.com/css2?family=Barlow" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		button {
			width: 100%;
		}
		.btn {
			width: 100%;
		}
	</style>
</head>

<body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<div class="c">
		<h1>Little Backup Box</h1>
		<hr>
		<a style="margin-right:0.5em;" href="sysinfo.php"><?php echo L::sysinfo; ?></a> &bull;
		<a style="margin-right:0.5em; margin-left:0.5em;" href="raw-viewer/"><?php echo L::viewer; ?></a> &bull;
		<a style="margin-left:0.5em;" href="edit.php"><?php echo L::edit; ?></a>
		<hr style="margin-bottom: 2em;">
		<p>
			<form method="post">
				<button class="btn primary" name="cardbackup"><?php echo L::cardbackup_b; ?></button>
			</form>
		</p>
		<p>
			<form method="post">
				<button class="btn primary" name="camerabackup"><?php echo L::camerabackup_b; ?></button>
			</form>
		</p>
		<p>
			<form method="post">
				<button class="btn primary" name="internalbackup"><?php echo L::internalbackup_b; ?></button>
			</form>
		</p>
		<p>
			<form method="post">
				<button class="btn" name="cancelshutdown"><?php echo L::cancel_shutdown_b; ?></button>
			</form>
		</p>
		<p>
			<form method="post">
				<button class="btn" name="shutdown"><?php echo L::shutdown_b; ?></button>
			</form>
		</p>

		<?php
		if (isset($_POST['cardbackup'])) {
			shell_exec('sudo pkill -f card-backup*');
			shell_exec('sudo umount /media/storage');
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			shell_exec('sudo ./card-backup.sh > /dev/null 2>&1 & echo $!');
			echo '<script language="javascript">';
			echo 'alert("' . L::cardbackup_m . '")';
			echo '</script>';
		}
		if (isset($_POST['camerabackup'])) {
			shell_exec('sudo pkill -f camera-backup*');
			shell_exec('sudo umount /media/storage');
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			shell_exec('sudo ./camera-backup.sh > /dev/null 2>&1 & echo $!');
			echo '<script language="javascript">';
			echo 'alert("' . L::camerabackup_m . '")';
			echo '</script>';
		}
		if (isset($_POST['internalbackup'])) {
			shell_exec('sudo pkill -f internal-backup*');
			shell_exec('sudo umount /media/storage');
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			shell_exec('sudo ./internal-backup.sh > /dev/null 2>&1 & echo $!');
			echo '<script language="javascript">';
			echo 'alert("' . L::internalbackup_m . '")';
			echo '</script>';
		}
		if (isset($_POST['shutdown'])) {
			shell_exec('sudo shutdown -h now > /dev/null 2>&1 & echo $!');
			echo '<script language="javascript">';
			echo 'alert("' . L::shutdown_m . '")';
			echo '</script>';
		}
		if (isset($_POST['cancelshutdown'])) {
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			echo '<script language="javascript">';
			echo 'alert("' . L::cancel_shutdown_m . '")';
			echo '</script>';
		}
		?>
		<p>
			<details>
				<summary><?php echo L::help; ?></summary>
				<div style="display: inline-block; text-align: left;"><?php echo L::help_txt; ?></div>
			</details>
		</p>
	</div>
</body>

</html>