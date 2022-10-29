<?php
/**
 * @package Quick Updates
 * @copyright Portions Copyright 2006 Paul Mathot http://www.beterelektro.nl/zen-cart
 * @copyright Copyright 2006 Andrew Berezin andrew@eCommerce-service.com
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * Zen Cart German Version - www.zen-cart-pro.at
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: quick_updates.php 2022-10-29 10:59:04 webchills $
 */
require('includes/application_top.php');

// without these lines the taxprice will be rounded to 0 decimals
// extended to avoid php 7.4 log error notices
require DIR_WS_CLASSES . 'currencies.php';
if (!isset($currencies)) 
$currencies = new currencies();
$currencies->currencies[DEFAULT_CURRENCY]['decimal_places'] = 4;

define('QUICKUPDATES_DISPLAY_TVA_PRICES', QUICKUPDATES_DISPLAY_TVA_OVER);

// bof functions
function zen_quickupdates_table_head($sort_field, $head_text, $cols=1) {
  $str = '';
  $str .= '<td class="dataTableHeadingContent" align="center" valign="middle"' . ($cols > 1 ? ' colspan="' . $cols . '"' : '') . '>';
  if($sort_field != '') {
    $str .= '<a href="' . zen_href_link(FILENAME_QUICK_UPDATES, 'sort_by=' . trim($sort_field) . ' ASC') . '">' . zen_image(DIR_WS_IMAGES . 'icon_up.gif', TEXT_SORT_ALL . $head_text . ' ' . TEXT_ASCENDINGLY) . '</a>';
    $str .= '<a href="' . zen_href_link(FILENAME_QUICK_UPDATES, 'sort_by=' . trim($sort_field) . ' DESC') . '">' . zen_image(DIR_WS_IMAGES . 'icon_down.gif', TEXT_SORT_ALL . $head_text . ' ' . TEXT_DESCENDINGLY) . '</a><br />';
  }
  $str .= $head_text . '</td>';
  return $str;
}
// eof functions

////
// This module changes the $_POST array! (moves import data to $_POST['quick_updates_new'])


if (isset($_GET['products_status'])){
/// do not convert to int here! (conversion is done later anyway)
$_SESSION['quick_updates']['products_status'] = zen_db_prepare_input($_GET['products_status']);
}

$current_category_id = 1;
if (isset($_GET['cPath'])){
  $_SESSION['quick_updates']['cPath'] = (int)$_GET['cPath'];
}
if(isset($_SESSION['quick_updates']['cPath'])){
 $current_category_id = $_SESSION['quick_updates']['cPath'];
}

if (isset($_REQUEST['categories_switch'])){
$_SESSION['quick_updates']['categories_switch'] = zen_db_prepare_input($_REQUEST['categories_switch']);
}
if(!isset($_SESSION['quick_updates']['categories_switch'])){
$_SESSION['quick_updates']['categories_switch'] = 'linked_cats'; // or master_cats
}
$sort_by = 'p.products_id DESC';
if (isset($_GET['sort_by'])){
$_SESSION['quick_updates']['sort_by'] = zen_db_prepare_input($_GET['sort_by']);
}
if(isset($_SESSION['quick_updates']['sort_by'])){
$sort_by = $_SESSION['quick_updates']['sort_by'];
}
// by default show most recent added products first

$manufacturer = 0;
if (isset($_GET['manufacturer'])){
$_SESSION['quick_updates']['manufacturer'] = (int)$_GET['manufacturer'];
}
if(isset($_SESSION['quick_updates']['manufacturer'])){
$manufacturer = $_SESSION['quick_updates']['manufacturer'];
}


// using the stored pagenumber doesn't always make sense, reset it in that cases
if(isset($_REQUEST['row_by_page']) || isset($_REQUEST['products_status']) || isset($_REQUEST['sort_by'])){
$_SESSION['quick_updates']['page'] = 1;
}
if(isset($_REQUEST['page'])){
$_SESSION['quick_updates']['page'] = (int)$_REQUEST['page'];
}

if(isset($_SESSION['quick_updates']['page'])){
$page = $_SESSION['quick_updates']['page'];
}
  
$row_by_page = 30;
if (isset($_GET['row_by_page'])){
$_SESSION['quick_updates']['row_by_page'] = (int)$_GET['row_by_page'];
}
if(isset($_SESSION['quick_updates']['row_by_page'])){
$row_by_page = $_SESSION['quick_updates']['row_by_page'];
}
if(!$row_by_page > 0){
$row_by_page = MAX_DISPLAY_SEARCH_RESULTS;
}

define('MAX_DISPLAY_ROW_BY_PAGE' , $row_by_page );

// define the szen for rollover lines per page
$row_bypage_array = array();
//for ($i = 10; $i <=100 ; $i=$i+5)
for ($i = 5; $i <= 320 ; $i=$i*2) {
  $row_bypage_array[] = array('id' => $i,
                              'text' => $i);
}

// bof get tx classes
$tax_class_array = array(array('id' => '0', 'text' => NO_TAX_TEXT));
$classes = $db->Execute("select tax_class_id, tax_class_title
                         from " . TABLE_TAX_CLASS . "
                         order by tax_class_title");
while (!$classes->EOF) {
  $tax_class_array[] = array('id' => $classes->fields['tax_class_id'],
                             'text' => $classes->fields['tax_class_title']);
  $classes->MoveNext();
}
// eof get tx classes

// bof get manufacturers
$manufacturers_array = array(array('id' => '0', 'text' => NO_MANUFACTURER));
$manufacturers = $db->Execute("select manufacturers_id, manufacturers_name
                         from " . TABLE_MANUFACTURERS . "
                         order by manufacturers_name");
while (!$manufacturers->EOF) {
  $manufacturers_array[] = array('id' => $manufacturers->fields['manufacturers_id'],
                                 'text' => $manufacturers->fields['manufacturers_name']);
  $manufacturers->MoveNext();
}
// eof get manufacturers
// bof get category_tree
$quick_updates_category_tree = zen_get_category_tree_quickupdates();
// eof get category_tree

// bof Update database

switch (isset($_GET['action']) ? $_GET['action'] : '') {
  case 'update' :
    // bof prepare al new data for database input
    
    if(sizeof($_POST['quick_updates_new']) > 0){
      foreach($_POST['quick_updates_new'] as $key => $value){
       
        $_POST['quick_updates_new'][$key] = zen_db_prepare_input($value);
      }
    }
    // eof prepare al new data for database input

    $quick_updates_count = array();
    if(isset($_POST['quick_updates_new']['products_model'])){
        foreach($_POST['quick_updates_new']['products_model'] as $products_id => $new_value) {
        if (trim($_POST['quick_updates_new']['products_model'][$products_id]) != trim($_POST['quick_updates_old']['products_model'][$products_id])) {
          $quick_updates_count['products_model'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_model='" . zen_db_input($new_value) . "', products_last_modified=NOW() WHERE products_id=" . (int)$products_id);
        }
      }
    }

                
    if(isset($_POST['quick_updates_new']['products_name'])){
      foreach($_POST['quick_updates_new']['products_name'] as $products_id => $new_value) {
        if (trim(stripslashes($_POST['quick_updates_new']['products_name'][$products_id])) != trim(stripslashes($_POST['quick_updates_old']['products_name'][$products_id]))) {
          $quick_updates_count['products_name'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS_DESCRIPTION . " SET products_name='" . zen_db_input($new_value) . "' WHERE products_id=" . (int)$products_id . " and language_id=" . (int)$_SESSION['languages_id']);
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_last_modified=now() WHERE products_id=" . (int)$products_id);
        }
      }
    }
    
    if(isset($_POST['quick_updates_new']['products_price'])){
      foreach($_POST['quick_updates_new']['products_price'] as $products_id => $new_value) {
        // we look if it's a price markup and if so we look if this product has been unchecked for the markup
        if((!isset($_POST['flag_markup'])) || ($_POST['markup_checked'][$products_id] == true)){
          // not doing markups, or this product is checked for markup
          // (this saves a lot of obsolete hidden $_POST's,  when not doing price markups)
          $apply_price_update = true;
        }else{
          // doing markups, but this products is not checked
          $apply_price_update = false;
        }
        if (($_POST['quick_updates_new']['products_price'][$products_id] != $_POST['quick_updates_old']['products_price'][$products_id]) && $apply_price_update) {
          $quick_updates_count['products_price'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_price='" . zen_db_input($new_value) . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
          // fix the sort order for prices (catalog side)
          zen_update_products_price_sorter((int)$products_id);
        }
      }
    }
    if(isset($_POST['quick_updates_new']['products_weight'])){
      foreach($_POST['quick_updates_new']['products_weight'] as $products_id => $new_value) {
        if ($_POST['quick_updates_new']['products_weight'][$products_id] != $_POST['quick_updates_old']['products_weight'][$products_id]) {
          $quick_updates_count['products_weight'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_weight='" . zen_db_input($new_value) . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
        }
      }
    }
    if(isset($_POST['quick_updates_new']['products_quantity'])){
      foreach($_POST['quick_updates_new']['products_quantity'] as $products_id => $new_value) {
        if ($_POST['quick_updates_new']['products_quantity'][$products_id] != $_POST['quick_updates_old']['products_quantity'][$products_id]) {
          $quick_updates_count['products_quantity'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_quantity='" . zen_db_input($new_value) . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
        }
      }
    }
    if(isset($_POST['quick_updates_new']['manufacturers_id'])){
      foreach($_POST['quick_updates_new']['manufacturers_id'] as $products_id => $new_value) {
        if ($_POST['quick_updates_new']['manufacturers_id'][$products_id] != $_POST['quick_updates_old']['manufacturers_id'][$products_id]) {
          $quick_updates_count['manufacturers_id'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET manufacturers_id='" . (int)$new_value . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
        }
      }
    }
    if(isset($_POST['quick_updates_new']['products_sort_order'])){
      foreach($_POST['quick_updates_new']['products_sort_order'] as $products_id => $new_value) {
        if (trim($_POST['quick_updates_new']['products_sort_order'][$products_id]) != trim($_POST['quick_updates_old']['products_sort_order'][$products_id])) {
          $quick_updates_count['products_sort_order'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_sort_order='" . zen_db_input($new_value) . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
        }
      }
    }
    
    if(isset($_POST['quick_updates_old']['products_status'])){
      foreach($_POST['quick_updates_old']['products_status'] as $products_id => $status) {
        if(!isset($_POST['quick_updates_new']['products_status'][$products_id])) $_POST['quick_updates_new']['products_status'][$products_id] = '0';
        if ($_POST['quick_updates_new']['products_status'][$products_id] != $_POST['quick_updates_old']['products_status'][$products_id]) {
          $quick_updates_count['products_status'][$products_id] = $products_id;
          zen_set_product_status((int)$products_id, (int)$_POST['quick_updates_new']['products_status'][$products_id]);
        }
      }
    }
    if(isset($_POST['quick_updates_new']['products_tax_class_id'])){
      foreach($_POST['quick_updates_new']['products_tax_class_id'] as $products_id => $new_value) {
        if ($_POST['quick_updates_new']['products_tax_class_id'][$products_id] != $_POST['quick_updates_old']['products_tax_class_id'][$products_id]) {
          $quick_updates_count['products_tax_class_id'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_tax_class_id='" . (int)$new_value . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
        }
      }
    }
    if(isset($_POST['quick_updates_new']['categories_id'])){
      foreach($_POST['quick_updates_new']['categories_id'] as $products_id => $new_value) {
        if ($_POST['quick_updates_new']['categories_id'][$products_id] != $_POST['quick_updates_old']['categories_id'][$products_id]) {
          if(zen_childs_in_category_count($new_value)) {
            $messageStack->add(TEXT_CATEGORY_WITH_CHILDS . ' ' . zen_get_category_name($new_value, (int)$_SESSION["languages_id"]) . ' [' . $new_value . ']', 'error');
            continue;
          }
          // if the categories_id that links the master_categories_id is updated, we update the master accordingly (to prevent invalid linked master id's)
          if($_POST['quick_updates_old']['categories_id'] == $_POST['quick_updates_old']['master_categories_id']){
            $quick_updates_count['master_categories_id'][$products_id] = $products_id;
            $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET master_categories_id='" . (int)$new_value . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
            zen_update_products_price_sorter((int)$products_id); // needed?
          }
          $quick_updates_count['categories_id'][$products_id] = $products_id;
          
          $db->Execute("UPDATE " . TABLE_PRODUCTS_TO_CATEGORIES . " SET categories_id='" . (int)$new_value . "', products_id=" . (int)$products_id . " WHERE products_id=" . (int)$products_id . " AND categories_id=" . (int)$_POST['quick_updates_old']['categories_id'][$products_id]);
        }
      }
    }

    if(isset($_POST['quick_updates_new']['master_categories_id'])){
      foreach($_POST['quick_updates_new']['master_categories_id'] as $products_id => $new_value) {
        if ($_POST['quick_updates_new']['master_categories_id'][$products_id] != $_POST['quick_updates_old']['master_categories_id'][$products_id]) {
          if(zen_childs_in_category_count($new_value)) {
            $messageStack->add(TEXT_CATEGORY_WITH_CHILDS . ' ' . zen_get_category_name($new_value, (int)$_SESSION["languages_id"]) . ' [' . $new_value . ']', 'error');
            continue;
          }
          // add invalid warning here?? (if the new master_cat is not linked)
          $quick_updates_count['master_categories_id'][$products_id] = $products_id;
          $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET master_categories_id='" . (int)$new_value . "', products_last_modified=now() WHERE products_id=" . (int)$products_id);
          zen_update_products_price_sorter((int)$products_id); // needed?       
        }
      }
    }    
    
    $quick_updates_count_string = '';
    if(sizeof($quick_updates_count) > 0){
      $quick_updates_count_string = '<table id="quick_updates_count">' . "\n";
      foreach($quick_updates_count as $key => $value){
        $quick_updates_count_string .=  '<tr><th>' . $key . TEXT_PRODUCTS_UPDATED_IDS . ': </th><td>' . implode(', ',$value) . '</td></tr>' . "\n";
        foreach($value as $key2 => $value2){
          $quick_updates_ids[$key2] = true;
        }
      }
      $quick_updates_count_string .= '</table>' . "\n";

      $messageStack->add(sizeof($quick_updates_ids) . ' ' . TEXT_PRODUCTS_UPDATED . $quick_updates_count_string, 'success');
    }

    break;

case 'calcul' :
    if (isset($_POST['price_markup'])){
    $preview_markup_price = true;
  }
    break;
} // end switch ($_GET['action'])
// eof Update database

// bof get products data from db
//// control string sort page
  if ($sort_by && !preg_match('/order by/', $sort_by)){
     $sort_by = 'order by ' . $sort_by ;
   }else{
     // added default sort order
      $sort_by = 'order by ' . 'products_id DESC' ;
   }

  //// control length (lines per page)
  $split_page = $page;
  if (!empty($split_page))
    $rows = (int) $split_page * (int)MAX_DISPLAY_ROW_BY_PAGE - (int)MAX_DISPLAY_ROW_BY_PAGE;
  
    
  $products_query_raw = "select p.products_id, p.products_type, p.products_image,                                
                                p.products_model, pd.products_name, p.products_status,
                                p.products_weight, p.products_quantity, p.manufacturers_id,
                                p.products_price, p.products_tax_class_id, p.products_date_added,
                                p.products_last_modified, p.products_date_available,
                                p.products_quantity_order_min, p.products_quantity_order_units, p.products_priced_by_attribute,
                                p.product_is_free, p.product_is_call, p.products_quantity_mixed, p.product_is_always_free_shipping,
                                pd.products_description,
                                p.products_quantity_order_max, p.products_sort_order,
                                p.master_categories_id,
                                m.manufacturers_name,
                                p2c.categories_id
                         from  " . TABLE_PRODUCTS . " p

                          LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id and pd.language_id = '" . (int)$_SESSION["languages_id"] . "')
                          LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (p.manufacturers_id = m.manufacturers_id)                          
                          LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON (p.products_id = p2c.products_id)";

  $where = array();
  if(is_numeric($_SESSION['quick_updates']['products_status'])){
    $where[] = "p.products_status = '" . (int)$_SESSION['quick_updates']['products_status'] . "'";
  }
  if ($current_category_id > 0){
    $where[] = "p2c.categories_id = '" . $current_category_id . "'";
  }
  if($manufacturer){
    $where[] = "p.manufacturers_id = '" . (int)$manufacturer . "'";
  }
  if(sizeof($where) > 0) {
    $products_query_raw .= " where " . implode(' and ', $where);
  }
 
  $products_query_raw .= " " . $sort_by;


//// page splitter and display each products info
  $products_split = new splitPageResults($split_page, MAX_DISPLAY_ROW_BY_PAGE, $products_query_raw, $products_query_numrows);
  $products = $db->Execute($products_query_raw);
// eof get products data from db

// Let's start displaying page with forms
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<link rel="stylesheet" type="text/css" href="includes/stylesheet_quick_updates.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="includes/javascript/quick_updates_price_calculations.js"></script>
<script type="text/javascript">
<!--
function init()
{
   cssjsmenu('navbar');
   if (document.getElementById)
   {
     var kill = document.getElementById('hoverJS');
     kill.disabled = true;
   }
 }

function popupWindow(url) {
  window.open(url, 'popupWindow', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no');
}

var browser_family;
var up = 1;

if (document.all && !document.getElementById)
  browser_family = "dom2";
else if (document.layers)
  browser_family = "ns4";
else if (document.getElementById)
  browser_family = "dom2";
else
  browser_family = "other";

-->
</script>
</head>
<body onLoad="init()" id="quickUpdates">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- bof #quickUpdatesWrapper -->
<table id="quickUpdatesWrapper">
  <tr>
    <td>
    <!-- bof pageHeading -->
      <table>
        <tr>
          <td class="pageHeading"><?php echo HEADING_TITLE; ?> - Version <?php echo QUICKUPDATES_VERSION; ?></td>
          <td class="pageHeading" align="right">
<?php
// bof show the current categories_image or manufacturers_image
$image_sql = '';
if($current_category_id > 0){
  $image_sql = "select c.categories_image as image from " . TABLE_CATEGORIES . " c where c.categories_id=" . $current_category_id;
} else {
  if($manufacturer){
    $image_sql = "select manufacturers_image as image from " . TABLE_MANUFACTURERS . " where manufacturers_id=" . $manufacturer;
  }
}
if(!empty($image_sql)) {
  $image = $db->Execute($image_sql);
  echo zen_image(DIR_WS_CATALOG . DIR_WS_IMAGES . $image->fields['image'], '', 40);
}
// eof show the current categories_image or manufacturers_image
?>
          </td>
        </tr>
      </table>
      <div class="quHeadingText"><?php echo QU_HEADING_TEXT; ?></div>
	
   
      <!-- eof pageHeading -->
            
      <!-- bof top forms -->

      <table class="quTop">
        <tr>
          <td class="smallText"><?php echo zen_draw_form('row_by_page', FILENAME_QUICK_UPDATES, zen_get_all_get_params(array('row_by_page')), 'get') . TEXT_MAXI_ROW_BY_PAGE . '&nbsp;&nbsp;' . zen_draw_pull_down_menu('row_by_page', $row_bypage_array, $row_by_page, 'onChange="this.form.submit();"'); ?></form></td>
          <td class="smallText" align="center" valign="top"><?php echo zen_draw_form('manufacturers', FILENAME_QUICK_UPDATES, zen_get_all_get_params(array('manufacturer')), 'get') . DISPLAY_MANUFACTURERS . '&nbsp;&nbsp' . zen_draw_pull_down_menu("manufacturer", $manufacturers_array, $manufacturer, 'onChange="this.form.submit();"'); ?></form></td>
          <td class="smallText" align="center" valign="top"><?php echo zen_draw_form('manufacturers', FILENAME_QUICK_UPDATES, zen_get_all_get_params(array('manufacturer')), 'get') . DISPLAY_STATUS . '&nbsp;&nbsp' . zen_draw_pull_down_menu("products_status", array('0' => array('id' => 'all', 'text' => 'All'), '1' => array('id' => '1', 'text' => 'Active'), '2' => array('id' => '0', 'text' => 'Inactive')), $_SESSION['quick_updates']['products_status'] ,'onChange="this.form.submit();"'); ?></form></td>
          <td class="smallText" align="center" valign="top"><?php echo zen_draw_form('categorie', FILENAME_QUICK_UPDATES, zen_get_all_get_params(array('cPath')), 'get') . DISPLAY_CATEGORIES . '&nbsp;&nbsp;' . zen_draw_pull_down_menu('cPath', $quick_updates_category_tree, $current_category_id, 'onChange="this.form.submit();"') . '</form>'; ?></td>
          <td>
            <?php
            echo zen_draw_form('form_categories_switch', FILENAME_QUICK_UPDATES);
            $array = array();            
            $array[] = array('id' => 'linked_cats','text' => 'Edit Linked Cats');
            $array[] = array('id' => 'master_cats','text' => 'Edit Master Cats');
            echo zen_draw_pull_down_menu('categories_switch',  $array, $_SESSION['quick_updates']['categories_switch'], 'onChange="this.form.submit();"');
            echo '</form>';
            ?>
          </td>  
            

        </tr>
      </table>
      <!-- eof top forms -->

      <!-- bof quick_updates form -->
      <?php echo zen_draw_form('quick_updates', FILENAME_QUICK_UPDATES, zen_get_all_get_params(array('action')) . 'action=update', 'post'); ?>
      <!-- bof quick_updates form table -->
      <table class="quFormTable"  cellspacing="0">
        <tr>
          <td>
            <!-- bof button_update table -->
            <table>
              <tr>
                <td class="smalltext" align="middle"><?php echo WARNING_MESSAGE; ?> </td>
                
                <td align="right" valign="middle"><?php echo zen_image_submit('button_update.gif', IMAGE_UPDATE);?></td>
               </tr>
            </table>
            <!-- eof button_update table -->
            <!-- bof wrapper quickUpdatesProductsTable -->
            <table id="quickUpdatesProductsTable"  cellspacing="0">
              <tr>
                <td valign="top">
                  <!-- bof quickUpdates Table -->
                  <table  cellspacing="0">
                    <!-- bof dataTableHeadingRow -->
                    <tr class="dataTableHeadingRow">
<?php
echo zen_quickupdates_table_head('p.products_id', TABLE_HEADING_ID);
if(QUICKUPDATES_DISPLAY_THUMBNAIL == 'true'){
  echo zen_quickupdates_table_head('', TABLE_HEADING_IMAGE);
}
if(QUICKUPDATES_MODIFY_MODEL == 'true'){
echo zen_quickupdates_table_head('p.products_model', TABLE_HEADING_MODEL);
}  
if(QUICKUPDATES_MODIFY_NAME == 'true'){
echo zen_quickupdates_table_head('pd.products_name', TABLE_HEADING_PRODUCTS);
}
if(QUICKUPDATES_MODIFY_MANUFACTURER == 'true'){
echo zen_quickupdates_table_head('m.manufacturers_name', TABLE_HEADING_MANUFACTURERS);
}
if(QUICKUPDATES_MODIFY_STATUS == 'true'){
echo zen_quickupdates_table_head('p.products_status', TABLE_HEADING_STATUS);
}
if(QUICKUPDATES_MODIFY_SORT_ORDER == 'true'){
echo zen_quickupdates_table_head('p.products_sort_order', TABLE_HEADING_SORT_ORDER);
}
if(QUICKUPDATES_MODIFY_QUANTITY == 'true'){
echo zen_quickupdates_table_head('p.products_quantity', TABLE_HEADING_QUANTITY);
}
echo zen_quickupdates_table_head('p.products_price', TABLE_HEADING_PRICE);
if(QUICKUPDATES_DISPLAY_TVA_PRICES == 'true'){
echo zen_quickupdates_table_head('p.products_price', TABLE_HEADING_TAX_PRICE);
}
if(QUICKUPDATES_MODIFY_WEIGHT == 'true'){
echo zen_quickupdates_table_head('p.products_weight', TABLE_HEADING_WEIGHT);
}
if(QUICKUPDATES_MODIFY_TAX == 'true'){
  echo zen_quickupdates_table_head('p.products_tax_class_id', TABLE_HEADING_TAX);
}
if(QUICKUPDATES_MODIFY_CATEGORY == 'true'){
  echo zen_quickupdates_table_head('p2c.categories_id', TABLE_HEADING_CATEGORY);
}
if(QUICKUPDATES_DISPLAY_EDIT == 'true'){
  echo zen_quickupdates_table_head('', '&nbsp;');
}
?>
                    </tr>
                    <!-- eof dataTableHeadingRow -->
<?php

if (isset ($_POST['price_markup'])){
  $flag_markup = true;
  // better move markup type/value detection etc here (outside the while loop)  
}

// bof walk products object
while (!$products->EOF) {

    $rows++;
    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
    //// check for global add value or rates, calcul and round values rates
    if (isset ($flag_markup)){
      if (substr($_POST['price_markup'],-1) == '%') {
        $value = trim($_POST['price_markup'], '%');
        if(strpos($_POST['price_markup'], '-') === 0){
          $value = trim($value, '-');
          // substract percentage
          $price = sprintf("%01.4f", round($products->fields  ['products_price'] - (($value/ 100) * $products->fields  ['products_price']),4));
        } else {
          $value = trim($value, '+');
          // add percentage
          //(add is the same as substract of course, but I retain this if/else structure because different treatment might be desired)
          $valeur = (1 - (str_replace("%", '', $_POST['price_markup']) / 100));
          $price = sprintf("%01.4f", round($products->fields  ['products_price'] + (($value/ 100) * $products->fields  ['products_price']),4));     
        }
      } else {
        // add value
        $price = sprintf("%01.4f", round($products->fields  ['products_price'] + $_POST['price_markup'],4));
      }
    } else {
      $price = $products->fields  ['products_price'] ;
    }

    //// Check Tax_rate for displaying TTC
    $tax_rate = $db->Execute("select r.tax_rate, c.tax_class_title from " . TABLE_TAX_RATES . " r, " . TABLE_TAX_CLASS . " c where r.tax_class_id=" . $products->fields  ['products_tax_class_id'] . " and c.tax_class_id=" . $products->fields  ['products_tax_class_id']);

    if(empty($tax_rate->fields['tax_rate'])){
      $tax_rate->fields['tax_rate'] = 0;
}
    //// display Product Infomation Lines
    $tr = '<tr class="dataTableRow" onmouseover="';
    if(isset ($flag_markup)) {
      if(QUICKUPDATES_DISPLAY_TVA_OVER == 'true'){
        $tr .= 'display_ttc(\'display\', ' . $price . ', ' . $tax_rate->fields['tax_rate'] . ');';
      }
      $tr .= 'this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="';
      if(QUICKUPDATES_DISPLAY_TVA_OVER == 'true'){
        $tr .= 'display_ttc(\'delete\');';
      }
    } else {
      if(QUICKUPDATES_DISPLAY_TVA_OVER == 'true'){
        $tr .= 'display_ttc(\'display\', ' . $products->fields  ['products_price'] . ', ' . $tax_rate->fields['tax_rate'] . ');';
      }
      $tr .= 'this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="';
      if(QUICKUPDATES_DISPLAY_TVA_OVER == 'true'){
        $tr .= 'display_ttc(\'delete\', \'\', \'\', 0);';
      }
    }
    $tr .= 'this.className=\'dataTableRow\'">';
    echo $tr;

    
      echo '<td class="smallText">';
      // added for external links paulm
      if (defined('QUICKUPDATES_DISPLAY_ID_INFO')){
        // handler page needed for products type
        $handler_page = '';
        
        echo sprintf(QUICKUPDATES_DISPLAY_ID_INFO, $products->fields  ['products_id'], $handler_page, zen_image(DIR_WS_IMAGES . 'icon_info.gif', QUICKUPDATES_DISPLAY_ID_INFO_ALT));
      }
      echo $products->fields  ['products_id'];
      echo '</td>' . "\n";


    if(QUICKUPDATES_DISPLAY_THUMBNAIL == 'true'){
      echo '<td class="smallText productsImage">' .  
      zen_image(DIR_WS_CATALOG_IMAGES . $products->fields  ['products_image'], '', QUICKUPDATES_DISPLAY_THUMBNAIL_WIDTH, QUICKUPDATES_DISPLAY_THUMBNAIL_HEIGHT, 'id="SelectImageName_' . $products->fields  ['products_id'] . '_img"') . ''
       . '</td>' . "\n";
    }

    if(QUICKUPDATES_MODIFY_MODEL == 'true') {
      echo '<td class="smallText productsModel">';
      echo zen_draw_input_field('quick_updates_new[products_model][' . $products->fields  ['products_id'] . ']', stripslashes($products->fields  ['products_model']), 'size="12"') . zen_draw_hidden_field('quick_updates_old[products_model][' . $products->fields  ['products_id'] . ']', stripslashes($products->fields  ['products_model']));  
      // added for external links paulm
      if (defined('QUICKUPDATES_MODIFY_MODEL_INFO')){
        echo sprintf(QUICKUPDATES_MODIFY_MODEL_INFO, $products->fields  ['products_id'], stripslashes($products->fields  ['products_model']), zen_image(DIR_WS_IMAGES . 'icon_info.gif', stripslashes($products->fields  ['products_model'])));
      }
      echo '</td>' . "\n";
      }    
    

    if(QUICKUPDATES_MODIFY_NAME == 'true'){
      // added div wrapper to allow advanced :hover styling
      echo '<td class="smallText productsName"><div>' . zen_draw_input_field('quick_updates_new[products_name][' . $products->fields  ['products_id'] . ']', stripslashes($products->fields  ['products_name']), 'size="16"') . zen_draw_hidden_field('quick_updates_old[products_name][' . $products->fields  ['products_id'] . ']', stripslashes($products->fields  ['products_name'])) . '</div></td>' . "\n";
    }    

    if(QUICKUPDATES_MODIFY_MANUFACTURER == 'true') {
      echo '<td class="smallText manufacturersID">' . zen_draw_pull_down_menu('quick_updates_new[manufacturers_id][' . $products->fields  ['products_id'] . ']', $manufacturers_array, $products->fields  ['manufacturers_id'], 'style="width: 6em;"') . zen_draw_hidden_field('quick_updates_old[manufacturers_id][' . $products->fields  ['products_id'] . ']', $products->fields  ['manufacturers_id']) . '</td>' . "\n";
    }

    if(QUICKUPDATES_MODIFY_STATUS == 'true') {
      echo '<td class="smallText">' . zen_draw_checkbox_field('quick_updates_new[products_status][' . $products->fields  ['products_id'] . ']', 1, false, $products->fields  ['products_status'], '') . zen_draw_hidden_field('quick_updates_old[products_status][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_status']) .
      '</td>' . "\n";
    }

    if(QUICKUPDATES_MODIFY_SORT_ORDER == 'true') {
      echo '<td class="smallText">' . zen_draw_input_field('quick_updates_new[products_sort_order][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_sort_order'], 'size="3"') . zen_draw_hidden_field('quick_updates_old[products_sort_order][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_sort_order']) .
      '</td>' . "\n";
    }

    if(QUICKUPDATES_MODIFY_QUANTITY == 'true') {
      echo '<td class="smallText">' . zen_draw_input_field('quick_updates_new[products_quantity][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_quantity'], 'size="3"') . zen_draw_hidden_field('quick_updates_old[products_quantity][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_quantity']) . '</td>' . "\n";
    }   
    

    //// get the specials products list
    $specials_array = array();
    $specials = $db->Execute("select p.products_id, s.products_id, s.specials_id from " . TABLE_PRODUCTS . " p, " . TABLE_SPECIALS . " s where s.products_id = p.products_id");
    while (!$specials->EOF) {
      $specials_array[] = $specials->fields['products_id'];
      $specials->MoveNext();
    }
    //// check specials
    $parameters = 'size="6"';
    if(QUICKUPDATES_DISPLAY_TVA_PRICES == 'true'){
      // updateMargin on products_price(was only on products_purchase_price before)
      $parameters .= ' onKeyUp="updateGross(' . $products->fields  ['products_id'] . '); updateMargin(' . $products->fields  ['products_id'] . ');"';
    }

    if (in_array($products->fields  ['products_id'], $specials_array)){      
      $spec = $db->Execute("select s.products_id, s.specials_id from " . TABLE_PRODUCTS . " p, " . TABLE_SPECIALS . " s where s.products_id = " . (int)$products->fields  ['products_id'] . "");
      $flag_special = true;
      echo '<td class="smallText specialPrice">';
    }else{
      $flag_special = false;
      echo '<td class="smallText productsPrice">';
    }
    echo zen_draw_input_field('quick_updates_new[products_price][' . $products->fields  ['products_id'] . ']', $price, $parameters);
    
    if (isset($flag_markup)){
      echo zen_draw_checkbox_field('markup_checked[' . $products->fields  ['products_id'] . ']', '1', (!($flag_special)&&($_POST['marge'])));
      
    } else {
      // this has become obsolete since we changed prices to update by default when markup is not set
      
    }
    if($flag_special){
      echo '&nbsp;<a target=blank href="' . zen_href_link(FILENAME_SPECIALS, 'sID=' . $spec->fields['specials_id'] . '&action=edit') . '" target="_blank">'. zen_image(DIR_WS_IMAGES . 'icon_info.gif', TEXT_SPECIALS_PRODUCTS) . '</a>';    
    }
      
    if(QUICKUPDATES_DISPLAY_TVA_PRICES == 'true'){
      $parameters = 'size="6"';

      $parameters .= ' onKeyUp="updateNet(' . $products->fields  ['products_id'] . '); updateMargin(' . $products->fields  ['products_id'] . ');"';

      // $taxprice needs the $currencies->currencies[DEFAULT_CURRENCY]['decimal_places'] to be set (done at top of file)
      // an alternative might be to use $price (i.s.o. $taxprice) and update it with updatGross('$products->fields  ['products_id']') for each product ?)
      $tax_price = zen_add_tax($price, $tax_rate->fields['tax_rate']);
      $tax_price = sprintf("%01.2f", round($tax_price, 4));
      echo '</td>' . "\n";

      echo '<td class="smallText">' . zen_draw_input_field('quick_updates_new[products_taxprice][' . $products->fields  ['products_id'] . ']', $tax_price, $parameters);

      
      echo zen_draw_hidden_field('quick_updates_old[products_tax_value]['.$products->fields  ['products_id'].']', $tax_rate->fields['tax_rate']);
    }


    echo zen_draw_hidden_field('quick_updates_old[products_price][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_price']);

    echo '<a target="_blank" href="' . zen_href_link(FILENAME_PRODUCTS_PRICE_MANAGER, 'products_filter=' . $products->fields  ['products_id']) . '">' . zen_image(DIR_WS_IMAGES . 'icon_products_price_manager.gif', QUICKUPDATES_PPM_LINK_ALT) . '</a>';
    
    echo '</td>' . "\n";

    if(QUICKUPDATES_MODIFY_WEIGHT == 'true') {
      echo '<td class="smallText">' . zen_draw_input_field('quick_updates_new[products_weight][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_weight'], 'size="4"') . zen_draw_hidden_field('quick_updates_old[products_weight][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_weight']) . '</td>' . "\n";
    }

    if(QUICKUPDATES_MODIFY_TAX == 'true') {
      echo '<td class="smallText">' . zen_draw_pull_down_menu('quick_updates_new[products_tax_class_id][' . $products->fields  ['products_id'] . ']', $tax_class_array, $products->fields  ['products_tax_class_id'], 'style="width: 5em;"') . zen_draw_hidden_field('quick_updates_old[products_tax_class_id][' . $products->fields  ['products_id'] . ']', $products->fields  ['products_tax_class_id']) . '</td>' . "\n";
    }

    if(QUICKUPDATES_MODIFY_CATEGORY == 'true') {

      //products_to_categories.php?products_filter=198
      $zen_get_master_categories_pulldown = zen_get_master_categories_pulldown($products->fields  ['products_id']);
      
      $multilinked = false;      
      if(count($zen_get_master_categories_pulldown) > 2){
        $multilinked = true;
      }
      $invalidcat = false;
      if(($multilinked == false)&&($products->fields  ['master_categories_id'] != $products->fields  ['categories_id'])){
        $invalidcat = true;
      }
      $prod2cat_link =  '<a href="' . zen_href_link('products_to_categories.php', 'products_filter=' . (int)$products->fields  ['products_id']) . '">(' . $products->fields  ['categories_id'] . '/' . $products->fields  ['master_categories_id']. ')</a>';

      //
      
      echo '<td class="smallText">';
      
      if($_SESSION['quick_updates']['categories_switch'] == 'master_cats'){
        // show/edit the master cats products table
        echo zen_draw_pull_down_menu('quick_updates_new[master_categories_id][' . $products->fields  ['products_id'] . ']', zen_get_master_categories_pulldown($products->fields  ['products_id']), $products->fields  ['master_categories_id']);  
      }else{
        // show/edit the linked cats products_to_categories table
        if($invalidcat == true){
           echo TEXT_QU_CHECK_CAT_INVALID;          
        }elseif($multilinked == true){
           echo TEXT_QU_CHECK_CAT_MULTILINKS;
        }else{
          echo zen_draw_pull_down_menu('quick_updates_new[categories_id][' . $products->fields  ['products_id'] . ']', $quick_updates_category_tree, $products->fields  ['categories_id'], '');
          echo zen_draw_hidden_field('quick_updates_old[categories_id][' . $products->fields  ['products_id'] . ']', $products->fields  ['categories_id']);
        }        
      }

      // we need the old master_categories_id value in both cases
      echo zen_draw_hidden_field('quick_updates_old[master_categories_id][' . $products->fields  ['products_id'] . ']', $products->fields  ['master_categories_id']);
      echo $prod2cat_link;
      echo '</td>' . "\n";

    } // eof QUICKUPDATES_MODIFY_CATEGORY

     //// link to full edit
    $type_handler = $zc_products->get_admin_handler($products->fields  ['products_type']);    
    if(QUICKUPDATES_DISPLAY_EDIT == 'true')
      echo '<td class="smallText"><a href="' . zen_href_link($type_handler, 'cPath=' . $products->fields  ['master_categories_id'] . '&product_type=' . $products->fields  ['products_type'] . '&pID=' . $products->fields  ['products_id']  . '&action=new_product') . '" target="blank">' . zen_image(DIR_WS_IMAGES . 'icon_edit.gif', ICON_EDIT) . '</a></td>' . "\n";
    echo '</tr>';    

  $products->MoveNext();
  
}
// eof walk products object
?>
                  </table>
                  <!-- eof quickUpdates Table -->
                </td>
              </tr>
            </table>
            <!-- eof wrapper quickUpdatesProductsTable -->
          </td>
        </tr>
        <tr>
          <td align="right">
<?php
  // post flag_markup (is being used while updating prices)
  if (isset($flag_markup)){
    echo zen_draw_hidden_field('flag_markup', '1');
  }
  // bof  display bottom page buttons  
  echo zen_image_submit('button_update.gif', IMAGE_UPDATE);
  echo '&nbsp;&nbsp;<a href="' . zen_href_link(FILENAME_QUICK_UPDATES, "row_by_page=$row_by_page") . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
  // eof  display bottom page buttons
?>
          </td>
        </tr>
        <tr>
          <td>
            <!-- bof  bottom page selection -->
            <table>
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_ROW_BY_PAGE, $split_page, TEXT_DISPLAY_NUMBER_OF_PRODUCTS);  ?></td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_ROW_BY_PAGE, MAX_DISPLAY_PAGE_LINKS, $split_page); ?></td>
              </tr>
            </table>
            <!-- eof  bottom page selection -->
          </td>
        </tr>
      </table>
      <!-- bof quick_updates form table -->
      </form>
      <!-- eof quick_updates form -->

      
    </td>
  </tr>
</table><!-- eof #quickUpdatesWrapper -->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>