<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$bookTitle = 'Het Receptenboek';
$bookNote = 'Sla de bladzijden om en ontdek iets lekkers om te maken.';
$categories = recipeCategories();
$db = recipeDb();
$counts = recipeCounts($db);
$id = isset($_GET['id']) && is_scalar($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
$recipe = false;

if ($id !== false && $id > 0) {
    $query = $db->prepare('SELECT * FROM recipes WHERE id = :id');
    $query->execute(array(':id' => $id));
    $recipe = $query->fetch();
}

if (!$recipe) {
    http_response_code(404);
    $documentTitle = 'Recept niet gevonden';
    $activeCategory = 'all';
} else {
    $documentTitle = $recipe['title'];
    $activeCategory = $recipe['category'];
}

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'recipe_detail_view.php';
