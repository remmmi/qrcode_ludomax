<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Planche etiquettes <?php datetz(); ?></title>
		<style type="text/css">
			/* Définir la taille de la page A4 */
			@page {
				size: A4;
				margin-top: 0.85cm;
				margin-left: 0.22cm;
				margin-right: 0.79cm;
				margin-bottom: 0.64cm;
			}
			/* Définir la grille pour les étiquettes */
			.container {
				display: grid;
				grid-template-columns: repeat(3, 7cm);
				grid-template-rows: repeat(5, 3.5cm);
				grid-gap: 0.19cm;
				break-after: page;
			}
			/* Définir le style des étiquettes */
			.label {
				background-color: #fff;
				border: 1px solid #fff;
				padding: 0.1cm;
				font-size: 16px;
				text-align: center;
				box-sizing: border-box;
			}
			.titre {
				margin-top: 0.4cm;
				width:49%;
				display: inline-block;
			}
			img {
				width: 49%;
				float: right;
				display: inline-block;
			}
		</style>
	</head>
	<body>
		<?php
		print_labels();
		?>
	</body>
</html>
<?php
include_once('config.php');
$i=0;
function display_jeu($jeu){
	echo '<div class="label">';
		echo '<div class="titre">'.$jeu['nom']."<br><br>".$jeu['id']."</div>";
		echo '<img src="qrcode.php?s=qrl&d='.$jeu['notes'].'&sf=6">';
	echo '</div>';
}
function display_empty(){
	echo '<div class="label">';
		echo '<div class="titre"></div>';
		echo '<img src="">';
	echo '</div>';
}
function print_labels(){
	$depart = $_POST['depart'];
	if($depart>24){$depart=0;}
	if ($i % 24 == 0 or $i == 0) {
		echo '<div class="container">';
		}
		for ($x=$depart; $x >0 ; $x--) {
			
			display_empty();
		}
		foreach ($_POST['toqrcode'] as $key => $value) {
			$i++;
			$jeu = unserialize(base64_decode($value));;
			if (empty($jeu['notes'])){continue;}
			display_jeu($jeu);
		}
		if ($i % 24 == 0 or $i == 0) {
		echo '</div>';
	}
}
function datetz(){
	$tz = 'Europe/Paris';
	$timestamp = time();
	$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
	$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
	echo $dt->format('d.m.Y-H:i:s');
}
?>