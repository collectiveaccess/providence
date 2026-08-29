<?php

$current_page = $this->getVar("current_page");
$current_page_id = $current_page->get('ca_site_pages.page_id');

$pages = ca_site_pages::getPageList(['type' => 'PROVIDENCE_HELP_TEXT']);

if(is_array($pages) && sizeof($pages)){
        print "<nav>";
        foreach( $pages as $page ) {
                $page_id = $page['page_id'];
                $page_title = $page['title'];
                print "<h2>";
                print caNavLink($this->request, $page_title, ($current_page_id == $page_id) ? 'sf-menu-selected' : '', 'helpMenu', 'Show', $page_id);
                print "</h2>";
        }
        print "</nav>";
}
?>