<!DOCTYPE html>
<html>
<head>
	<title>Upload du dernier fichier de sauvegarde SQL</title>
	<link href="bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>
<body>
	<h1>Upload du dernier fichier de sauvegarde SQL</h1>
	<form action="select-jeux.php" method="post" enctype="multipart/form-data">
		<input type="file" name="sql_file">
		<br><br>
		<input type="submit" value="Mise à jour">
	</form>
	<p><a class="button" href="select-jeux.php">Aller directement à la sélection des jeux sans rafraichir la liste</a></p>
</body>
</html>
