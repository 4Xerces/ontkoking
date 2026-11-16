<?php

function recipeCategories()
{
    return array(
        'all'       => 'Alles',
        'breakfast' => 'Ontbijt',
        'lunch'     => 'Lunch',
        'dinner'    => 'Diner',
        'dessert'   => 'Dessert',
        'drinks'    => 'Drankjes',
    );
}

function recipeDifficultyLabel($difficulty)
{
    $labels = array(
        'Easy' => 'Makkelijk',
        'Medium' => 'Gemiddeld',
        'Hard' => 'Moeilijk',
    );

    return isset($labels[$difficulty]) ? $labels[$difficulty] : $difficulty;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function recipeDb()
{
    static $db = null;

    if ($db instanceof PDO) {
        return $db;
    }

    $dataDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0750, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('The recipe data directory could not be created.');
    }

    $db = new PDO('sqlite:' . $dataDirectory . DIRECTORY_SEPARATOR . 'recipes.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec(
        'CREATE TABLE IF NOT EXISTS recipes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT NOT NULL,
            prep_time INTEGER NOT NULL DEFAULT 0,
            cook_time INTEGER NOT NULL DEFAULT 0,
            difficulty TEXT NOT NULL,
            servings INTEGER NOT NULL,
            image TEXT NOT NULL DEFAULT \'\',
            ingredients TEXT NOT NULL DEFAULT \'\',
            instructions TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $db->exec('CREATE INDEX IF NOT EXISTS recipes_category_idx ON recipes(category)');

    translateSampleRecipes($db);
    seedRecipes($db);

    return $db;
}

function translateSampleRecipes(PDO $db)
{
    $titles = array(
        'Recipe title one' => 'Voorbeeldrecept 1',
        'Recipe title two' => 'Voorbeeldrecept 2',
        'Recipe title three' => 'Voorbeeldrecept 3',
        'Recipe title four' => 'Voorbeeldrecept 4',
        'Recipe title five' => 'Voorbeeldrecept 5',
        'Recipe title six' => 'Voorbeeldrecept 6',
        'Recipe title seven' => 'Voorbeeldrecept 7',
        'Recipe title eight' => 'Voorbeeldrecept 8',
        'Recipe title nine' => 'Voorbeeldrecept 9',
        'Recipe title ten' => 'Voorbeeldrecept 10',
        'Recipe title eleven' => 'Voorbeeldrecept 11',
    );
    $update = $db->prepare(
        'UPDATE recipes
         SET title = :new_title, description = :new_description
         WHERE title = :old_title
           AND description = :old_description
           AND ingredients = \'\'
           AND instructions = \'\''
    );

    foreach ($titles as $oldTitle => $newTitle) {
        $update->execute(array(
            ':new_title' => $newTitle,
            ':new_description' => 'Een korte beschrijving van dit recept.',
            ':old_title' => $oldTitle,
            ':old_description' => 'Short description of the recipe goes here.',
        ));
    }
}

function seedRecipes(PDO $db)
{
    if ((int)$db->query('SELECT COUNT(*) FROM recipes')->fetchColumn() > 0) {
        return;
    }

    $samples = array(
        array('Voorbeeldrecept 1', 'breakfast', '20 min', 'Easy', 2),
        array('Voorbeeldrecept 2', 'breakfast', '15 min', 'Easy', 1),
        array('Voorbeeldrecept 3', 'lunch', '30 min', 'Medium', 2),
        array('Voorbeeldrecept 4', 'lunch', '25 min', 'Easy', 3),
        array('Voorbeeldrecept 5', 'dinner', '60 min', 'Hard', 4),
        array('Voorbeeldrecept 6', 'dinner', '45 min', 'Medium', 4),
        array('Voorbeeldrecept 7', 'dessert', '50 min', 'Medium', 6),
        array('Voorbeeldrecept 8', 'dessert', '35 min', 'Easy', 8),
        array('Voorbeeldrecept 9', 'drinks', '5 min', 'Easy', 1),
        array('Voorbeeldrecept 10', 'dinner', '40 min', 'Medium', 2),
        array('Voorbeeldrecept 11', 'lunch', '20 min', 'Easy', 2),
    );

    $db->beginTransaction();
    try {
        $insert = $db->prepare(
            'INSERT INTO recipes (title, category, description, cook_time, difficulty, servings)
             VALUES (:title, :category, :description, :cook_time, :difficulty, :servings)'
        );

        foreach ($samples as $sample) {
            preg_match('/\d+/', $sample[2], $minutes);
            $insert->execute(array(
                ':title' => $sample[0],
                ':category' => $sample[1],
                ':description' => 'Een korte beschrijving van dit recept.',
                ':cook_time' => isset($minutes[0]) ? (int)$minutes[0] : 0,
                ':difficulty' => $sample[3],
                ':servings' => $sample[4],
            ));
        }

        $db->commit();
    } catch (Exception $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function recipeCounts(PDO $db)
{
    $counts = array_fill_keys(array_keys(recipeCategories()), 0);
    $counts['all'] = (int)$db->query('SELECT COUNT(*) FROM recipes')->fetchColumn();

    foreach ($db->query('SELECT category, COUNT(*) AS amount FROM recipes GROUP BY category') as $row) {
        if (isset($counts[$row['category']])) {
            $counts[$row['category']] = (int)$row['amount'];
        }
    }

    return $counts;
}

function pageUrl($category, $page)
{
    return 'recipes.php?' . http_build_query(array('category' => $category, 'page' => $page));
}
