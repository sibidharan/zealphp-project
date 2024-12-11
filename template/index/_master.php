<?
use ZealPHP\App;
?>

<!DOCTYPE html>
<html lang="en">
<? App::render('_head', [
    'title' => $title,
]);
?>
<body>

    <!-- Header Section -->
    <header>
        <h1><?=$title?></h1>
        <p><?=$description?></p>
    </header>

    <?App::render('content', [
        'content' => $content
    ]);?>

<?App::render('_footer');?>

</body>
</html>
