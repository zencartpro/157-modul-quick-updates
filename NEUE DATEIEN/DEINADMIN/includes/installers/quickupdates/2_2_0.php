<?php
/**
 * @package Quick Updates
 * Zen Cart German Specific
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * Zen Cart German Version - www.zen-cart-pro.at
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: 2_2_0.php 2022-10-29 12:00:16Z webchills $
 */
$db->Execute(" SELECT @gid:=configuration_group_id
FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Quick Updates'
LIMIT 1;");

$db->Execute("INSERT IGNORE INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function)  VALUES 
		('Display the ID.',                          'QUICKUPDATES_DISPLAY_ID',          'true',  'Enable/Disable the products id displaying',                     @gid, '1', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),'), 
		('Display the thumbnail.',                   'QUICKUPDATES_DISPLAY_THUMBNAIL',   'true',  'Enable/Disable the products thumbnail displaying',              @gid, '2', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the model.',                        'QUICKUPDATES_MODIFY_MODEL',        'true',  'Enable/Disable the products model displaying and modification', @gid, '3', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the name.',                         'QUICKUPDATES_MODIFY_NAME',         'true',  'Enable/Disable the products name editing',                      @gid, '4', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the status of the products.',       'QUICKUPDATES_MODIFY_STATUS',       'true',  'Allow/Disallow the Status displaying and modification',       @gid, '6',  NULL, NOW(), NULL,  'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the weight of the products.',       'QUICKUPDATES_MODIFY_WEIGHT',       'true',  'Allow/Disallow the Weight displaying and modification?',      @gid, '7',  NULL, NOW(), NULL,  'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the quantity of the products.',     'QUICKUPDATES_MODIFY_QUANTITY',     'true',  'Allow/Disallow the quantity displaying and modification',     @gid, '8',  NULL, NOW(), NULL,  'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the manufacturer of the products.', 'QUICKUPDATES_MODIFY_MANUFACTURER', 'false', 'Allow/Disallow the Manufacturer displaying and modification', @gid, '9',  NULL, NOW(), NULL,  'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the class of tax of the products.', 'QUICKUPDATES_MODIFY_TAX',          'false', 'Allow/Disallow the Class of tax displaying and modification', @gid, '10', NULL, NOW(), NULL,  'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the category.',                     'QUICKUPDATES_MODIFY_CATEGORY',     'true',  'Enable/Disable the products category modify',                 @gid, '11', NULL, NOW(), NULL,  'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Display price with all included of tax.',  'QUICKUPDATES_DISPLAY_TVA_OVER',    'true',  'Enable/Disable the displaying of the Price with all tax included when your mouse is over a product', @gid, '12', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),'),		
		('Display the link towards the page where you will be able to edit the product.', 'QUICKUPDATES_DISPLAY_EDIT',               'true',  'Enable/Disable the display of the link towards the page where you will be able to edit the product', @gid, '14', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),'),
		('Modify the sort order.',                   'QUICKUPDATES_MODIFY_SORT_ORDER',        'true', 'Enable/Disable the products sort order modify',               @gid, '16', NULL, NOW(), NULL, 'zen_cfg_select_option(array(\"true\", \"false\"),')");
		
		$db->Execute("REPLACE INTO " . TABLE_CONFIGURATION_LANGUAGE . " (configuration_title, configuration_key, configuration_language_id, configuration_description) VALUES
		('Zeige Artikel ID', 'QUICKUPDATES_DISPLAY_ID', 43, 'Anzeige der Artikel ID an/aus'),
    ('Zeige Artikelbild (Thumbnail)', 'QUICKUPDATES_DISPLAY_THUMBNAIL', 43, 'Anzeige des Artikelbilds (Thumbnail) an/aus'),
    ('Artikelnummer editierbar', 'QUICKUPDATES_MODIFY_MODEL', 43, 'Die Artikelnummer wird angezeigt und ist editierbar an/aus'),
    ('Artikelname editierbar', 'QUICKUPDATES_MODIFY_NAME', 43, 'Der Artikelname wird angezeigt und ist editierbar an/aus'),   
    ('Artikelstatus editierbar', 'QUICKUPDATES_MODIFY_STATUS', 43, 'Der Artikelstatus wird angezeigt und ist editierbar an/aus'),
    ('Artikelgewicht editierbar', 'QUICKUPDATES_MODIFY_WEIGHT', 43, 'Das Artikelgewicht wird angezeigt und ist editierbar an/aus'),
    ('Lagerbestand editierbar', 'QUICKUPDATES_MODIFY_QUANTITY', 43, 'Der Lagerbestand wird angezeigt und ist editierbar an/aus'),
    ('Hersteller editierbar', 'QUICKUPDATES_MODIFY_MANUFACTURER', 43, 'Der Hersteller wird angezeigt und ist editierbar an/aus'),
    ('Steuerklasse editierbar', 'QUICKUPDATES_MODIFY_TAX', 43, 'Die Steuerklasse wird angezeigt und ist editierbar an/aus'),
    ('Kategorie editierbar', 'QUICKUPDATES_MODIFY_CATEGORY', 43, 'Die Kategorie wird angezeigt und ist editierbar an/aus'),
    ('Bruttopreis editierbar', 'QUICKUPDATES_DISPLAY_TVA_OVER', 43, 'Der Bruttopreis wird angezeigt und ist editierbar an/aus'),    
    ('Link zur Artikelbearbeitung', 'QUICKUPDATES_DISPLAY_EDIT', 43, 'Anzeige eines Links zur normalen Artikelbearbeitung an/aus'),    
    ('Sortierung editierbar', 'QUICKUPDATES_MODIFY_SORT_ORDER', 43, 'Die Sortierung wird angezeigt und ist editierbar an/aus')");
   
// delete old configuration/catalog menu
$admin_page = 'configQuickUpdates';
$db->Execute("DELETE FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = '" . $admin_page . "' LIMIT 1;");
$admin_page_catalog = 'catalogQuickUpdates';
$db->Execute("DELETE FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = '" . $admin_page_catalog . "' LIMIT 1;");
// add configuration/catalog menu
if (!zen_page_key_exists($admin_page)) {
$db->Execute(" SELECT @gid:=configuration_group_id
FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Quick Updates'
LIMIT 1;");
$db->Execute("INSERT IGNORE INTO " . TABLE_ADMIN_PAGES . " (page_key,language_key,main_page,page_params,menu_key,display_on_menu,sort_order) VALUES 
('configQuickUpdates','BOX_CONFIGURATION_QUICK_UPDATES','FILENAME_CONFIGURATION',CONCAT('gID=',@gid),'configuration','Y',@gid)");
$db->Execute(" SELECT @gid:=configuration_group_id
FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Quick Updates'
LIMIT 1;");
$db->Execute("INSERT IGNORE INTO " . TABLE_ADMIN_PAGES . " (page_key,language_key,main_page,page_params,menu_key,display_on_menu,sort_order) VALUES 
('catalogQuickUpdates','BOX_CATALOG_QUICK_UPDATES','FILENAME_QUICK_UPDATES','','catalog','Y',101)");
$messageStack->add('Quick Updates erfolgreich installiert.', 'success');  
}
