<?php
/**
 * Represents a Product category, Products can be added to many categories and they
 * can have a ProductCategory as a parent in the site tree.
 */
class ProductCategory extends Page {
	private static $singular_name = 'Product Category';
	private static $plural_name = 'Product Categories';

	/**
	 * Many many relations for a ProductCategory
	 *
	 * @var Array
	 */
	private static $many_many = array(
		'Products' => 'Product'
	);

	private static $has_many = array(
		'ChildProducts' => 'Product'
	);

	private static $many_many_extraFields = array(
		'Products' => array(
			'ProductOrder' => 'Int'
		)
	);

	/**
	 * Summary fields for viewing categories in the CMS
	 *
	 * @var Array
	 */
	private static $summary_fields = array(
		'Title' => 'Name',
		'MenuTitle' => 'Menu Title'
	);

	private static $allowed_children = array('ProductCategory');

	/**
	 * Set some CMS fields for managing Categories
	 *
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		// Fields specific to CatalogAdmin
		if(Controller::curr() == 'CatalogAdmin'){
			$fields->removeByName('Main');

			// Add list of categories
			$subCategories = ProductCategory::get()->filter(array('ParentID' => $this->ID));
			if($subCategories->count() > 0){
				$subcatconf = GridFieldConfig_RelationEditor::create(40);
				$subcatconf->addComponent(new GridFieldSortableRows('Sort'));
				$subcatconf->removeComponentsByType('GridFieldDeleteAction');
				$subcatconf->removeComponentsByType('GridFieldAddNewButton');
				$subcatconf->removeComponentsByType('GridFieldAddExistingAutocompleter');
				$fields->addFieldToTab('Root.SubCategories', new GridField('Children', 'Order Sub Categories', $subCategories, $subcatconf));
			}
		}

		// Order products
		$prodorderconf = GridFieldConfig_RelationEditor::create(70);
		$prodorderconf->addComponent(new GridFieldSortableRows('ProductOrder'));
		$prodorderconf->removeComponentsByType('GridFieldDeleteAction');
		$prodorderconf->removeComponentsByType('GridFieldEditButton');
		$prodorderconf->removeComponentsByType('GridFieldAddNewButton');
		$prodorderconf->removeComponentsByType('GridFieldAddExistingAutocompleter');
		$fields->addFieldToTab('Root.Products', new GridField('Products', 'Order Products', $this->Products(), $prodorderconf));

		return $fields;
	}

	public function isSection() {
		$current = Controller::curr();
		$request = $current->getRequest();
		$url = $request->getURL();

		if (stristr($url, 'product/')) {
			$params = $request->allParams();
			$productID = $params['ID'];
			$product = Product::get()
				->where("\"URLSegment\" = '{$productID}'")
				->first();
			if ($product && $product->exists()) {
				return $this->isCurrent() || in_array($product->ID, $this->Products()->column('ID'));
			}
		}

		return parent::isSection();
	}

	public function ListboxCrumb($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false) {
		$page = $this;
		$pages = array();
		$crumb = '';

		while(
			$page
			&& (!$maxDepth || count($pages) < $maxDepth)
			&& (!$stopAtPageType || $page->ClassName != $stopAtPageType)
		) {
			if($showHidden || $page->ShowInMenus || ($page->ID == $this->ID)) {
				$pages[] = $page;
			}

			$page = $page->Parent;
		}

		$i = 1;
		foreach ($pages as $page) {

			$crumb .= $page->getMenuTitle();
			if ($i++ < count($pages)) {
				$crumb .= ' > ';
			}
		}
		return $crumb;
	}

	public static function getAllCategories(){
		$categories = ProductCategory::get()->map('ID', 'ListboxCrumb')->toArray();
		return $categories;
	}

	// Set permsissions
	public function canView($member = null) {
		return true;
	}
}

/**
 * Controller to display a ProductCategory and retrieve its Products.
 */
class ProductCategory_Controller extends Page_Controller {
	/**
	 * Allowed actions for this controller
	 *
	 * @var Array
	 */
	private static $allowed_actions = array(
		//'product'
	);

	private static $url_handlers = array(
		//'product//$ID!/$Action' => 'product'
	);

	/**
	 * Set number of products per page displayed in ProductCategory pages
	 *
	 * @var Int
	 */
	public static $products_per_page = 20;

	/**
	 * Set how the products are ordered on ProductCategory pages
	 *
	 * @see ProductCategory_Controller::Products()
	 * @var String Suitable for inserting in ORDER BY clause
	 */
	public static $products_ordered_by = "\"ProductCategory_Products\".\"ProductOrder\" ASC";
	//public static $products_ordered_by = "\"SiteTree\".\"ParentID\" ASC, \"SiteTree\".\"Sort\" ASC";

	/**
	 * Include some CSS.
	 *
	 * @see Page_Controller::init()
	 */
	function init() {
		parent::init();
		Requirements::css('swipestripe/css/Shop.css');
	}
	/*
	function product(){
		$params = $this->getURLParams();
		$product = new Product_Controller();
		$product = $product->getProductFromUrl($params['ID']);
		$pc = Product_Controller::create($product);
		$this->handleRequest($this->request, $product);

		if($pc->hasAction($params['Action'])){
			return $pc->handleAction($this->request, $params['Action']);
		} else {
			return $pc->init();
		}
	}
	*/


	/**
	 * Get Products that have this ProductCategory set or have this ProductCategory as a parent in site tree.
	 * Supports pagination.
	 *
	 * @see Page_Controller::Products()
	 * @return FieldList
	 */
	 /*
	public function Products() {
		$limit = self::$products_per_page;
		$orderBy = self::$products_ordered_by;
		$cats = array($this->ID);
		foreach ($this->Children() as $child) {
			if ($child instanceof ProductCategory) {
				$cats[] = $child->ID;
			}
		}
		$in = "('" . implode("','", $cats) . "')";
		$products = Product::get()
			->where("\"ProductCategory_Products\".\"ProductCategoryID\" IN $in OR \"ParentID\" IN $in")
			->sort($orderBy)
			->leftJoin('ProductCategory_Products', "\"ProductCategory_Products\".\"ProductID\" = \"SiteTree\".\"ID\"");
		$this->extend('updateCategoryProducts', $products);
		$list = PaginatedList::create($products, $this->request)
			->setPageLength($limit);

		return $list;
	}
	*/

	// Return all products within this category as paginated list
	public function getProductsList(){
		$limit = self::$products_per_page;
		$orderBy = self::$products_ordered_by;

		$products = ArrayList::create($this->Products()->sort($orderBy)->toArray());

		// Go second level
		$children = $this->Children()->filter(array('ClassName' => 'ProductCategory'));
		foreach($children as $child){
			$childProducts = $child->Products()->sort($orderBy)->toArray();
			$products->merge($childProducts);

			// Go thrid level
			$childChildren = $child->Children()->filter(array('ClassName' => 'ProductCategory'));
			foreach($childChildren as $subChild){
				$childProducts = $subChild->Products()->sort($orderBy)->toArray();
				$products->merge($childProducts);
			}
		}

		$products->removeDuplicates('ID');

		$list = PaginatedList::create($products, $this->request)->setPageLength($limit);

		return $list;
	}
}

class ProductCategory_Products extends DataObject {
	private static $db = array(
		'ProductOrder' => 'Int'
	);

	private static $has_one = array(
		'ProductCategory' => 'ProductCategory',
		'Product' => 'Product'
	);
}

class ProductCategory_CMSExtension extends Extension {
	function updateSearchForm($form) {
		$fields = $form->Fields();
		$cats = ProductCategory::get()->map()->toArray();
		$fields->push(DropdownField::create('q[Category]', 'Category', $cats)
			->setHasEmptyDefault(true)
		);
		$form->loadDataFrom($this->owner->request->getVars());
		$form->setFields($fields);
	}
}

/**
 * Search filter for {@link Product} categories, filtering search results for
 * certain {@link ProductCategory}s in the CMS.
 */
class ProductCategory_SearchFilter extends SearchFilter {
	/**
	 * Apply filter query SQL to a search query
	 *
	 * @see SearchFilter::apply()
	 * @return SQLQuery
	 */
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getValue();
		if ($value) {
			$query->innerJoin(
				'ProductCategory_Products',
				"\"ProductCategory_Products\".\"ProductID\" = \"SiteTree\".\"ID\""
			);
			$query->innerJoin(
				'SiteTree_Live',
				"\"SiteTree_Live\".\"ID\" = \"ProductCategory_Products\".\"ProductCategoryID\""
			);
			$query->where("\"SiteTree_Live\".\"ID\" LIKE '%" . Convert::raw2sql($value) . "%'");
		}
		return $query;
	}

	/**
	 * Determine whether the filter should be applied, depending on the
	 * value of the field being passed
	 *
	 * @see SearchFilter::isEmpty()
	 * @return Boolean
	 */
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}

	protected function applyOne(DataQuery $query) {
		SS_Log::log(new Exception(print_r($this->getValue(), true)), SS_Log::NOTICE);
		return;
	}

	protected function excludeOne(DataQuery $query) {
		return;
	}
}
