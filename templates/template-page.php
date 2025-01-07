<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="page.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-light text-center mt-auto">
        <nav class="container navbar navbar-expand-lg navbar-light bg-light">
            <?php echo $branding; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".navbar-collapse" aria-controls="navbar-header" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php echo $main_menu_html; ?>
            <?php echo $user_menu_html; ?>
        </nav>
    </header>
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php if ( ! empty( $sidebar_left ) ) : ?>
            <aside class="col-md-3 sidebar-left">
                <?php echo $sidebar_left; ?>
            </aside>
            <?php endif; ?>
            <main class="col-md-6 container">
                <h1 class="page-title py-4"><?php echo $page_title; ?></h1>
                <div class="content py-4 text-start">
                    <?php echo $content; ?>
                </div>
            </main>
            <?php if ( ! empty( $sidebar_right ) ) : ?>
            <aside class="col-md-3 sidebar-right">
                <?php echo $sidebar_right; ?>
            </aside>
            <?php endif; ?>
        </div>
    </div>
    <footer class="bg-light text-center mt-auto">
        <nav class="container navbar navbar-expand-lg navbar-light bg-light">
            <?php echo $footer; ?>
            <?php echo $footer_menu_html; ?>
        </nav>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
