<?php
$site_title = $site_title ?? 'OpenSimulator Helpers';
$page_title = $page_title ?? 'Unknown page';
$content = $content ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in dui mauris.';

$sidebar_left = $sidebar_left ?? '';
$sidebar_right = $sidebar_right ?? '';
$version = $version ?? file_get_contents( dirname(__DIR__) . '/.version');
$footer = $footer ?? sprintf( _('OpenSimulator Helpers %s'), $version );

$menus['main'] = $menu ?? array(
    'home' => array(
        'url' => '/',
        'label' => 'Home',
    ),
    'about' => array(
        'url' => '/about',
        'label' => 'About',
    ),
);

$menus['user'] = array(
    'userprofile' => array(
        'url' => '/profile',
        'label' => 'Profile',
        'which_users' => 'logged_in',
        'children' => array(
            'account' => array(
                'url' => '/account',
                'label' => 'Account Settings',
            ),
            'logut' => array(
                'url' => '/profile/?logout',
                'label' => 'View',
            ),
        ),
    ),
    'login' => array(
        'url' => '/login',
        'label' => 'Login',
        'which_users' => 'logged_out',
    ),
);

$menus['footer'] = array(
    'github' => array(
        'url' => 'http://github.com/magicoli/opensim-helpers',
        'label' => 'GitHub Repository',
    ),
    'w4os' => array(
        'url' => 'https://w4os.org',
        'label' => 'W4OS Project',
    ),
);

/**
 * Build HTML menu using Bootstrap styles
 */
function format_menu( $menu, $slug = 'main', $class = '' ) {
    $id = "navbar-$slug";
    $html = sprintf(
        '<div class="collapse navbar-collapse" id="%s">',
        $id
    );
    $ul_class = "navbar-nav navbar-$slug ms-auto";
    $html .= sprintf(
        '<ul class="%s">',
        $ul_class
    );
    foreach ($menu as $item) {
        if (isset($item['children'])) {
            $html .= '<li class="nav-item dropdown">';
            $html .= '<a class="nav-link dropdown-toggle" href="' . htmlspecialchars($item['url']) . '" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
            $html .= htmlspecialchars($item['label']);
            $html .= '</a>';
            $html .= '<ul class="dropdown-menu" aria-labelledby="navbarDropdown">';
            foreach ($item['children'] as $child) {
                $html .= '<li><a class="dropdown-item" href="' . htmlspecialchars($child['url']) . '">' . htmlspecialchars($child['label']) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</li>';
        } else {
            $html .= '<li class="nav-item">';
            $html .= '<a class="nav-link" href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['label']) . '</a>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}

$branding = '<a class="navbar-brand" href="#">' . htmlspecialchars($GLOBALS['site_title']) . '</a>';

// Generate HTML for each menu
$main_menu_html = format_menu( $menus['main'], 'main' );
$user_menu_html = format_menu( $menus['user'], 'user' );
$footer_menu_html = format_menu( $menus['footer'], 'footer' );

require( 'template-page.php' );
