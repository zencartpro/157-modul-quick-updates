<?php
  function zen_get_category_tree_quickupdates($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = array(), $include_itself = false, $category_has_products = false, $limit = false) {
    global $db;

    if ($limit) {
      $limit_count = " limit 1";
    } else {
      $limit_count = '';
    }

    if (!is_array($category_tree_array)) $category_tree_array = array();
   

    if ($include_itself) {
      $category = $db->Execute("SELECT cd.categories_name
                                FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd
                                WHERE cd.language_id = " . (int)$_SESSION['languages_id'] . "
                                AND cd.categories_id = " . (int)$parent_id);

      $category_tree_array[] = array('id' => $parent_id, 'text' => $category->fields['categories_name']);
    }

    $categories = $db->Execute("SELECT c.categories_id, cd.categories_name, c.parent_id
                                FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd
                                WHERE c.categories_id = cd.categories_id
                                AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
                                AND c.parent_id = " . (int)$parent_id . "
                                ORDER BY c.sort_order, cd.categories_name");

    while (!$categories->EOF) {
      if ($category_has_products == true and zen_products_in_category_count($categories->fields['categories_id'], '', false, true) >= 1) {
        $mark = '*';
      } else {
        $mark = '&nbsp;&nbsp;';
      }
      if ($exclude != $categories->fields['categories_id']) {
        $category_tree_array[] = array('id' => $categories->fields['categories_id'], 'text' => $spacing . $categories->fields['categories_name'] . $mark);
      }
      $category_tree_array = zen_get_category_tree_quickupdates($categories->fields['categories_id'], $spacing . '&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array, '', $category_has_products);
      $categories->MoveNext();
    }

    return $category_tree_array;
  }