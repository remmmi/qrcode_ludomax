<?php
	include_once('config.php');
?>
<!DOCTYPE html>
<html>
<head>

	<link href="bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>choisir les jeux a qrcoder</title>
	<style type="text/css">
		.fixed {
			display: block;
			position: fixed;
			bottom: 0;
			width: 100%;
		}
	</style>
</head>
<body>
	<?php 
		handle_sql();
		display_form_jeux();
	?> 
	<script type="text/javascript">
		const rangeInput = document.getElementById('ranges');

		rangeInput.addEventListener('input', () => {
			const ranges = rangeInput.value;
			console.log(ranges);

			const checkboxIds = ranges.split(',').flatMap((range) => {
				if (range.includes('-')) {
					const [start, end] = range.split('-');
					return Array.from({ length: end - start + 1 }, (_, i) => Number(start) + i);

				}
				return Number(range);
			});

			const checkboxes = document.querySelectorAll('input[type="checkbox"]');

			checkboxes.forEach((checkbox) => {
				const id = Number(checkbox.id);
				if (checkboxIds.includes(id)) {
					checkbox.checked = true;
				} else {
					checkbox.checked = false;
				}
			});
		});


	</script>
</body>
</html>

<?php
function handle_sql(){
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$i=0;
		$sql_file = $_FILES["sql_file"];

	$file_name = "bkp.sql"; //$sql_file['name'];
	$file_tmp_name = $sql_file["tmp_name"];
	$file_size = $sql_file["size"];

	if ($file_size > 0) {
		clear_sql_folder();
		$target_directory = "sql/";
		$target_file = $target_directory . $file_name;

		if (move_uploaded_file($file_tmp_name, $target_file)) {
			echo'<div class="alert alert-success" role="alert">';

			echo "Le fichier SQL a été téléchargé avec succès dans le dossier /sql.<br>";
			$list_jeux = get_jeux_insert_list();
			recreate_table_jeux();

			foreach ($list_jeux as $jeu) {
				$jeu = replaceZeroDate(utf8_encode($jeu));
				insert_jeu($jeu);
				$i++;
			}
			echo "$i jeux ont été créés<br>";
			echo "</div>";

		} else {
			echo "Une erreur s'est produite lors du téléchargement du fichier SQL.<br>";
		}
	}
	}
}
////////////////////////////////////////////////////////////////////////////////


function display_form_jeux() {

	$dsn = "mysql:host=".$GLOBALS['dbhost'].";dbname=".$GLOBALS['dbname'];
	$user = $GLOBALS['dbuser'];
	$password = $GLOBALS['dbpass'];

	$pdo = new PDO($dsn, $user, $password, [
		PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
	]);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$stmt = $pdo->prepare('SELECT id,nom,image1, notes, date_suppression FROM '.$GLOBALS['index_ludo'].'_jeux WHERE date_suppression ="1970-01-01" ORDER BY id ASC');
	try{
		$stmt->execute();
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
	}
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo "<form method='post' action='qrcoder.php'>";
	echo '<table class="table" id="jeux">';
	echo '
	<thead>
		<tr>
			<th scope="col">qr?</th>
			<th scope="col">ID</th>
			<th scope="col">Nom</th>
			<th scope="col">Notes/youtube</th>
		</tr>
	</thead>
	<tbody>';
	foreach ($rows as $row) {
		$row['notes'] = clean_youtube($row['notes']);
		$row['nom'] = avoid_all_upper($row['nom']);
		$topost = serialize($row);
		$topost = base64_encode($topost);

		echo "<tr><td><input type='checkbox' id='". $row["id"] ."' name='toqrcode[]' value='" . $topost . "'></td><td>" . $row["id"] . "</td><td>" . $row["nom"] . "</td><td>" . $row["notes"] . "</td></tr>";
	}

	echo "</tbody></table>";
	echo "<div class='fixed'>";
	echo "<input type='text' name='depart' value='' placeholder='position 1ere étiquette'>";
	echo "<input type='text' id='ranges' name='ranges' value='' placeholder='2-4,7,9 -> imprimer 2,3,4,7,9'>";
	echo "<input type='submit' value='qrcoder'>";
	echo "</div>
	</form>";

  // Fermeture de la connexion à la base de données
	$pdo = null;
}

function clear_sql_folder()
{
	$directory = "sql/";
	$files = glob($directory . "*");
	foreach ($files as $file) {
		if (is_file($file)) {
			unlink($file);
		}
	}
}

function get_jeux_insert_list()
{
	$list = [];
	$logFile = file_get_contents("sql/bkp.sql");
	$logFile = mb_convert_encoding($logFile, "HTML-ENTITIES", "UTF-8");

	$matches = [];
	preg_match_all(
		"/(?P<insert>INSERT INTO {$GLOBALS['index_ludo']}_jeux.+?\);)/s",
		$logFile,
		$matches
	);

	foreach ($matches["insert"] as $cQuery) {
		$cQuery = str_replace(["\n", "\t", "\r"], "", $cQuery);

		array_push($list, $cQuery);
	}
	return $list;
}



function recreate_table_jeux()
{
	$dsn = "mysql:host=".$GLOBALS['dbhost'].";dbname=".$GLOBALS['dbname'];
	$user = $GLOBALS['dbuser'];
	$password = $GLOBALS['dbpass'];

	try {
		$pdo = new PDO($dsn, $user, $password, [
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
		]);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$drop_query = "DROP TABLE IF EXISTS ".$GLOBALS['index_ludo']."_jeux";
		
		$pdo->prepare($drop_query)->execute();

		$create_query =
		"CREATE TABLE `".$GLOBALS['index_ludo']."_jeux` ( `id` int(11) NOT NULL AUTO_INCREMENT, `est_un_jouet` int(1) NOT NULL DEFAULT 0, `nom` varchar(255) NOT NULL, `image1` varchar(255) NOT NULL, `image2` varchar(255) NOT NULL, `duree_pret` int(3) NOT NULL DEFAULT 1, `emprunt_en_cour` int(11) NOT NULL, `reservation_en_cour` int(1) NOT NULL DEFAULT 0, `nombre_emprunts` int(11) NOT NULL, `joueurs_min` int(11) NOT NULL, `joueurs_max` int(11) NOT NULL, `age_min` decimal(11,2) NOT NULL, `duree` varchar(255) NOT NULL, `prix_achat` varchar(255) NOT NULL, `date_achat` date NOT NULL, `annee` int(4) NOT NULL, `esar` varchar(255) NOT NULL, `fabriquant` varchar(255) NOT NULL, `editeur` varchar(255) NOT NULL, `editeur2` varchar(255) NOT NULL, `auteur` varchar(255) NOT NULL, `auteur2` varchar(255) NOT NULL, `auteur3` varchar(255) NOT NULL, `illustrateur` varchar(255) NOT NULL, `illustrateur2` varchar(255) NOT NULL, `illustrateur3` varchar(255) NOT NULL, `url_TTTV` varchar(255) NOT NULL, `composition` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `pitch` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `nb_points` int(4) NOT NULL DEFAULT 1, `etat` varchar(255) NOT NULL, `statut` int(11) NOT NULL, `option1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `option2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `option3` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `option4` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `option5` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `notes` text NOT NULL, `date_suppression` date NOT NULL DEFAULT '01970-01-01 01:01:01', PRIMARY KEY (`id`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		if($pdo->prepare($create_query)->execute()){
			echo "la table des jeux a été supprimée et recrée<br>";
		}

	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
	}
}
function insert_jeu($jeu)
{
	$dsn = "mysql:host=".$GLOBALS['dbhost'].";dbname=".$GLOBALS['dbname'];
	$user = $GLOBALS['dbuser'];
	$password = $GLOBALS['dbpass'];

	try {
		$pdo = new PDO($dsn, $user, $password, [
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
		]);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$query = $jeu;
		$pdo->prepare($query)->execute();
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
	}
}




function replaceZeroDate($str)
{
	$pattern = "/0000-00-00/";
	$replacement = "1970-01-01 00:00:01";
	$result = preg_replace($pattern, $replacement, $str);
	return $result;
}
function clean_youtube($string){
	preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $string, $match);
	if($match[1]){
		return  'https://www.youtube.com/watch?v='.$match[1];
	}else{
		return "";
	}

}
function avoid_all_upper($string) {
	$string = ucfirst($string);
	$upperCount = preg_match_all('/[A-Z]/', $string);
	$wordCount = str_word_count($string);

	// If more than 50% of the letters are uppercase, capitalize the first letter of each word
	if ($upperCount / strlen($string) >= 0.5) {
		$words = explode(' ', strtolower($string));
		foreach ($words as &$word) {
			$word = ucfirst($word);
		}
		$string = implode(' ', $words);
	}

	return $string;
}
?>
