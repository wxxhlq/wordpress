<?php

if (!defined('ABSPATH')) exit;

if( !class_exists( 'VIDGALBLK_VideoGallery' ) ){
    class VIDGALBLK_VideoGallery{
        function __construct(){
            $this -> loaded_classes();
        }
        function loaded_classes(){
			require_once VIDGALBLK_DIR_PATH . 'includes/rootPlugin/inc/Init.php';
			require_once VIDGALBLK_DIR_PATH . 'includes/rootPlugin/inc/BlockEditor.php';
			require_once VIDGALBLK_DIR_PATH . 'includes/rootPlugin/inc/Enqueue.php';
			require_once VIDGALBLK_DIR_PATH . 'includes/rootPlugin/inc/AdminMenu.php';
			require_once VIDGALBLK_DIR_PATH . 'includes/rootPlugin/inc/ShortCode.php';
			require_once VIDGALBLK_DIR_PATH . 'includes/rootPlugin/inc/CustomColumn.php';
			require_once VIDGALBLK_DIR_PATH . 'includes/class-vidgalblk-rest-handler.php';

			new VIDGALBLK\Init();
			new VIDGALBLK\BlockEditor();
			new VIDGALBLK\Enqueue();
			new VIDGALBLK\AdminMenu();
			new VIDGALBLK\ShortCode();
			new VIDGALBLK\CustomColumn();
		}
    }
    new VIDGALBLK_VideoGallery();
}
