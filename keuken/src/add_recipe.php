<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';
session_start();

$bookTitle = 'Het Receptenboek';
$bookNote = 'Voeg een bladzijde toe en bewaar je favoriete recept.';
$categories = recipeCategories();
$db = recipeDb();
$counts = recipeCounts($db);
$activePage = 'add';
$documentTitle = 'Recept toevoegen';
$errors = array();
$values = array(
    'title' => '',
    'category' => '',
    'description' => '',
    'prep_time' => '0',
    'cook_time' => '0',
    'difficulty' => 'Easy',
    'servings' => '2',
    'image' => '',
    'ingredients' => '',
    'instructions' => '',
);

if (empty($_SESSION['recipe_csrf_token'])) {
    $_SESSION['recipe_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['recipe_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $values[$key] = trim($_POST[$key]);
        }
    }

    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    if (!hash_equals($csrfToken, $submittedToken)) {
        $errors[] = 'Je formulier is verlopen. Verstuur het formulier opnieuw.';
    }

    if ($values['title'] === '' || strlen($values['title']) > 120) {
        $errors[] = 'Vul een titel in van maximaal 120 tekens.';
    }
    if (!isset($categories[$values['category']]) || $values['category'] === 'all') {
        $errors[] = 'Kies een receptcategorie.';
    }
    if ($values['description'] === '' || strlen($values['description']) > 1000) {
        $errors[] = 'Vul een korte beschrijving in van maximaal 1.000 tekens.';
    }

    foreach (array('prep_time' => 'Voorbereidingstijd', 'cook_time' => 'Bereidingstijd') as $field => $label) {
        if ($values[$field] === '' || !ctype_digit($values[$field]) || (int)$values[$field] > 1440) {
            $errors[] = $label . ' moet een geheel getal tussen 0 en 1.440 minuten zijn.';
        }
    }
    if (ctype_digit($values['prep_time']) && ctype_digit($values['cook_time'])
        && (int)$values['prep_time'] + (int)$values['cook_time'] < 1) {
        $errors[] = 'De totale voorbereidingstijd en bereidingstijd moeten minstens één minuut zijn.';
    }
    if (!in_array($values['difficulty'], array('Easy', 'Medium', 'Hard'), true)) {
        $errors[] = 'Kies een geldige moeilijkheidsgraad.';
    }
    if ($values['servings'] === '' || !ctype_digit($values['servings'])
        || (int)$values['servings'] < 1 || (int)$values['servings'] > 100) {
        $errors[] = 'Het aantal porties moet een geheel getal tussen 1 en 100 zijn.';
    }
    if (strlen($values['ingredients']) > 20000 || !preg_match('/\S/', $values['ingredients'])) {
        $errors[] = 'Vul de ingrediënten in, één per regel (maximaal 20.000 tekens).';
    }
    if (strlen($values['instructions']) > 20000 || !preg_match('/\S/', $values['instructions'])) {
        $errors[] = 'Vul de bereidingswijze in, één stap per regel (maximaal 20.000 tekens).';
    }
    if ($values['image'] !== '') {
        $imageParts = parse_url($values['image']);
        if (!filter_var($values['image'], FILTER_VALIDATE_URL) || !is_array($imageParts)
            || !isset($imageParts['scheme']) || !in_array(strtolower($imageParts['scheme']), array('http', 'https'), true)) {
            $errors[] = 'De afbeelding moet een geldige HTTP- of HTTPS-link zijn, of laat dit veld leeg.';
        }
    }

    if (!$errors) {
        $insert = $db->prepare(
            'INSERT INTO recipes
                (title, category, description, prep_time, cook_time, difficulty, servings, image, ingredients, instructions)
             VALUES
                (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings, :image, :ingredients, :instructions)'
        );
        $insert->execute(array(
            ':title' => $values['title'],
            ':category' => $values['category'],
            ':description' => $values['description'],
            ':prep_time' => (int)$values['prep_time'],
            ':cook_time' => (int)$values['cook_time'],
            ':difficulty' => $values['difficulty'],
            ':servings' => (int)$values['servings'],
            ':image' => $values['image'],
            ':ingredients' => $values['ingredients'],
            ':instructions' => $values['instructions'],
        ));

        header('Location: recipe.php?id=' . (int)$db->lastInsertId() . '&created=1', true, 303);
        exit;
    }
}

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'add_recipe_view.php';
