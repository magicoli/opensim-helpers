<?php

class OpenSim_Page {
    protected $page_title;
    protected $content;

    public function __construct() {

    }

    public function get_page_title() {
        return $this->page_title;
    }

    public function get_content() {
        return $this->content;
    }

    public function get_sidebar_left() {
        return '';
    }

    public function get_sidebar_right() {
        $html = '';
        $html .= OpenSim_Grid::grid_info_card();
        if( ! empty( $html ) ) {
            $html = '<div class="sidebar sidebar-right">' . $html . '</div>';
        }
        return $html;
    }
}
