<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$bookTitle = 'Het Receptenboek';
$bookNote  = 'Bekijk alle recepten. Kies links een categorie en sla de bladzijden om.';
$categories = recipeCategories();
$db = recipeDb();
$counts = recipeCounts($db);
$perPage = 3;

$cat = isset($_GET['category']) && is_string($_GET['category']) ? $_GET['category'] : 'all';

if (!isset($categories[$cat])) {
    $cat = 'all';
}

$countQuery = $cat === 'all'
    ? $db->query('SELECT COUNT(*) FROM recipes')
    : $db->prepare('SELECT COUNT(*) FROM recipes WHERE category = :category');
if ($cat !== 'all') {
    $countQuery->execute(array(':category' => $cat));
}
$total = (int)$countQuery->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$pageInput = isset($_GET['page']) && is_scalar($_GET['page']) ? (int)$_GET['page'] : 1;
$page       = min($totalPages, max(1, $pageInput));
$offset = ($page - 1) * $perPage;

if ($cat === 'all') {
    $query = $db->prepare('SELECT * FROM recipes ORDER BY id DESC LIMIT :limit OFFSET :offset');
    $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
} else {
    $query = $db->prepare('SELECT * FROM recipes WHERE category = :category ORDER BY id DESC LIMIT :limit OFFSET :offset');
    $query->bindValue(':category', $cat, PDO::PARAM_STR);
    $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
}
$query->execute();
$visible = $query->fetchAll();
$activeCategory = $cat;
$documentTitle = $categories[$cat];

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'recipe_view.php';
