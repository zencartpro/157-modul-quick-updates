#############################################################################################
# Quick Updates 2.2.0 Uninstall - 2022-10-29 - webchills
# NUR AUSFÜHREN FALLS SIE DAS MODUL VOLLSTÄNDIG ENTFERNEN WOLLEN!!!
#############################################################################################
DELETE FROM configuration_group WHERE configuration_group_title = 'Quick Updates';
DELETE FROM configuration WHERE configuration_key LIKE 'QUICKUPDATES_%';
DELETE FROM configuration_language WHERE configuration_key LIKE 'QUICKUPDATES_%';
DELETE FROM admin_pages WHERE page_key = 'configQuickUpdates';
DELETE FROM admin_pages WHERE page_key = 'catalogQuickUpdates';
DELETE FROM admin_pages WHERE page_key = 'Quick Updates';
DELETE FROM admin_pages WHERE page_key = 'quick_updates_config';