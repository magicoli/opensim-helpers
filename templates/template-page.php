<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <?php 
        OpenSim::get_styles( 'head', true );
        OpenSim::get_scripts( 'head', true );
    ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-primary text-center mt-auto">
        <nav class="container navbar navbar-expand-lg">
            <?php echo $branding; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".navbar-collapse" aria-controls="navbar-header" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php echo $main_menu_html; ?>
            <?php echo $user_menu_html; ?>
        </nav>
    </header>
    <div class="container-fluid flex-grow-1 p-4">
        <!-- <div class="row justify-content-center"> -->
        <div class="row justify-content-center">
            <?php 
            // DEBUG
            // $sidebar_left = '<div class="card">Card<div>';
            // $sidebar_right = '<div class="card">Card<div>';
            ?>
            <main class="col-lg-auto">
                <h1 class="page-title"><?php echo $page_title; ?></h1>
                <div class="content text-start">
                    <?php echo $content; ?>
                </div>
            </main>
            <?php if ( ! empty( $sidebar_left ) ) : ?>
            <aside class="col-lg-3 col-xl-3 order-2 order-lg-first">
                <?php echo $sidebar_left; ?>
            </aside>
            <?php endif; ?>
            <?php if ( ! empty( $sidebar_right ) ) : ?>
                <aside class="col-xl-3">
                <?php echo $sidebar_right; ?>
            </aside>
            <?php endif; ?>
        </div>
    </div>
    <footer class="bg-secondary text-center mt-auto">
        <nav class="container navbar navbar-expand-lg">
            <?php echo $footer; ?>
            <?php echo $footer_menu_html; ?>
        </nav>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php
        OpenSim::get_scripts( 'footer', true );
        OpenSim::get_styles( 'footer', true );
    ?>
</body>
</html>
