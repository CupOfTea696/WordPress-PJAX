<?php

/**
 * Plugin Name: WordPress PJAX
 * Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: PJAX for WordPress.
 * Version:     1.0.0
 * Author:      Sven Wittevrongel
 * Author URI:  http://cupoftea.io
 * License:     MIT
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die('No direct access allowed.');

require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use CupOfTea\WordPress\Pjax\Pjax;

$pjax = new Pjax;
