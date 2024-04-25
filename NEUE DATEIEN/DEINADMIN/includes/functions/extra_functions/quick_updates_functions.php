<?php
function zen_get_category_tree_quickupdates($parent_id = TOPMOST_CATEGORY_PARENT_ID, $spacing = '', $exclude = '', $category_tree_array = [], $include_itself = false, $check_if_cat_has_prods = false, $limit = false)
{
    global $db;

    $limit_count = $limit ? " limit 1" : '';

    if (!is_array($category_tree_array)) $category_tree_array = [];

    // init pulldown with Top category if list is empty and top cat not marked as excluded
    if (count($category_tree_array) < 1 && $exclude != TOPMOST_CATEGORY_PARENT_ID) {
        $category_tree_array[] = ['id' => TOPMOST_CATEGORY_PARENT_ID, 'text' => TEXT_TOP];
    }

    if ($include_itself) {
        $sql = "SELECT cd.categories_name
                FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd
                WHERE cd.language_id = " . (int)$_SESSION['languages_id'] . "
                AND cd.categories_id = " . (int)$parent_id . "
                LIMIT 1";
        $results = $db->Execute($sql);
        if ($results->RecordCount()) {
            $category_tree_array[] = ['id' => $parent_id, 'text' => $results->fields['categories_name']];
        }
    }

    $sql = "SELECT c.categories_id, cd.categories_name, c.parent_id
            FROM " . TABLE_CATEGORIES . " c
            LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (c.categories_id = cd.categories_id AND cd.language_id = " . (int)$_SESSION['languages_id'] . ")
            WHERE c.parent_id = " . (int)$parent_id . "
            ORDER BY c.sort_order, cd.categories_name";
    $results = $db->Execute($sql);
    foreach ($results as $result) {
        if ($check_if_cat_has_prods && zen_products_in_category_count($result['categories_id'], '', false, true) >= 1) {
            $mark = '*';
        } else {
            $mark = '&nbsp;&nbsp;';
        }
        if ($exclude != $result['categories_id']) {
            $category_tree_array[] = ['id' => $result['categories_id'], 'text' => $spacing . $result['categories_name'] . $mark];
        }
        $category_tree_array = zen_get_category_tree($result['categories_id'], $spacing . '&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array, false, $check_if_cat_has_prods);
    }

    return $category_tree_array;
} 