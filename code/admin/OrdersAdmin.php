<?php
/**
 * Catalog admin area for managing orders.
 *
 * @author Plato Creative
 * @package swipestripe
 * @subpackage admin
 */
class OrderAdmin extends ModelAdmin {
	private static $url_segment = 'orders';
	private static $url_priority = 50;
	private static $menu_title = 'Orders';

	public $showImportForm = false;

	private static $managed_models = array(
		'Order'
	);

	private static $url_handlers = array(
	);

	public static $hidden_sections = array(
	);

	private static $allowed_actions = array(
		'EditForm'
	);
}
