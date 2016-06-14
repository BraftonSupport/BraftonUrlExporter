<?php
/**
 * @package Akismet
 */
/*
Plugin Name: Brafton Url Exporter
Plugin URI: 
Description: 
Version: 1.0.0
Author: Brafton
Author URI: 
*/

/*
 * 1. Setup menu page to get all the post types to select
 *      a. posts
 *      b. pages
 *      c. Custom post types
 * 2. Get all the Titles and permalinks for those post types
 * 3. Write a xls file with header row of Title, Post Type, Url, Redirect
 * 4. Download the file
 * */

class BraftonExport {
    
    public function __construct(){
        add_action('admin_menu', array($this, 'BraftonAdminMenu'));
        if(isset($_POST['export_post'])){
            add_action('init', array($this, 'downloadXLS'));
        }
    }
    public function BraftonAdminMenu(){
        add_management_page('Brafton Url Exporter', 'Brafton Url Exporter', 'activate_plugins', 'BraftonUrlExporter', array($this,'adminInitialize'));
    }
    public function adminInitialize(){
        if(isset($_POST['export_post'])){
            $urlList = $this->getUrlList($_POST['export_post']);
            $this->getXLS($urlList);
        }
        //This is the display for selecting the post types 
        $exclude = array('attachment', 'revision', 'nav_menu_item');
        $posttypes = get_post_types('', 'names');
        $validtypes = array();
        
        foreach($posttypes as $type){
            if(array_search($type, $exclude) === false){
                $validtypes[] = $type;
            }
        }
        
        echo "<h1>Export Urls</h1>";
        
        echo '<form method="post">';
        foreach($validtypes as $type){
            echo sprintf('<input type="checkbox" name="export_post[]" value="%s"/>%s <br/>', $type, $type);
        }
        echo '<input type="submit" value="Download Url list"/>';
        echo '</form>';
    }
    private function getUrlList($types){
        
        $args = array(
            'post_type' => $types,
            'posts_per_page'    => -1
            );
        $array = array();
        $results = new WP_Query($args);
        
        if($results->have_posts()){
            while($results->have_posts()): $results->the_post();
                $array[] = array(
                    'Title' => get_the_title(),
                    'Posttype'  => get_post_type(get_the_ID()),
                    'url'       => get_the_permalink(),
                    'redirect to'   => ''
                    );
            endwhile;
        }
        return $array;
    }
    private function cleanData(&$str){
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }
    private function getXLS($posts){
        $filename = "BraftonUrlExport" . date('YmdHis') . ".xls";

        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Type: application/vnd.ms-excel");

        $flag = false;
        foreach($posts as $row) {
        if(!$flag) {
        //echo implode("\t", array_keys($row)) . "\r\n";
        $flag = true;
        }
        array_walk($row, array($this, 'cleanData'));
        echo implode("\t", array_values($row)) . "\r\n";
        }
    }
    public function downloadXLS(){
        if(isset($_POST['export_post'])){
            $urlList = $this->getUrlList($_POST['export_post']);
            $this->getXLS($urlList);
        }
        exit();
    }
}
$initialize = new BraftonExport();