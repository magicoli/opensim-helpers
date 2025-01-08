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
}
