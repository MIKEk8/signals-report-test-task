<?php
include('connect_db.php');
include('common_function.php');

$fnc = new Fnc($mysqli);

include('report/report_function.php');
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=$title_seo;?></title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="/css/style.css">
</head>
<body>
	<div class="container-fluid p-4">
		<?php include('report/report.php'); ?>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
