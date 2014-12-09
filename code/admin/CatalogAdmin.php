<?php
/**
 * Catalog admin area for managing products, catgeories and attributes / options.
 * 
 * @author Plato Creative
 * @package swipestripe
 * @subpackage admin
 */
class CatalogAdmin extends ModelAdmin {

	private static $url_segment = 'catalog';
	private static $url_priority = 50;
	private static $menu_title = 'Catalog';

	public $showImportForm = false;

	private static $managed_models = array(
		'ProductCategory',
		'Product',
		'Attribute'
	);
	
	private static $url_handlers = array(
	);

	public static $hidden_sections = array();
	
	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);
		return $items;
	}
	
	public function init(){
		parent::init();	
	}

	public function getManagedModels() {
		$models = $this->stat('managed_models');
		if(is_string($models)) {
			$models = array($models);
		}
		
		if(!count($models)) {
			user_error(
				'ModelAdmin::getManagedModels(): 
				You need to specify at least one DataObject subclass in private static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"', 
				E_USER_ERROR
			);
		}

		// Normalize models to have their model class in array key
		foreach($models as $k => $v) {
			if(is_numeric($k)) {
				$models[$v] = array('title' => singleton($v)->i18n_plural_name());
				unset($models[$k]);
			}
		}
		return $models;
	}

	/**
	 * Returns managed models' create, search, and import forms
	 * @uses SearchContext
	 * @uses SearchFilter
	 * @return SS_List of forms 
	 */
	protected function getManagedModelTabs() {

		$forms  = new ArrayList();

		$models = $this->getManagedModels();
		foreach($models as $class => $options) { 
			$forms->push(new ArrayData(array (
				'Title'     => $options['title'],
				'ClassName' => $class,
				'Link' => $this->Link($this->sanitiseClassName($class)),
				'LinkOrCurrent' => ($class == $this->modelClass) ? 'current' : 'link'
			)));
		}
		
		return $forms;
	}
	
	public function getList() {
        $list = parent::getList();
		
        // Always limit by model class, in case you're managing multiple
        if($this->modelClass == 'Product') {
        	$list = Product::get();//$list->exclude('Price', '0');
        }
				
        return $list;
    }
	
	public function getEditForm($id = null, $fields = null) {		
		$list = $this->getList();

		$buttonAfter = new GridFieldButtonRow('after');
		$exportButton = new GridFieldExportButton('buttons-after-left');
		$exportButton->setExportColumns($this->getExportFields());
		
		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))->addComponent($buttonAfter)->addComponent($exportButton);
		$fieldConfig->removeComponentsByType('GridFieldExportButton');
		
		// Product category display settings
		if ($this->modelClass == 'ProductCategory') {
			$fieldConfig->removeComponentsByType('GridFieldAddNewButton');
			$fieldConfig->removeComponentsByType('GridFieldDeleteAction');
			$fieldConfig->removeComponentsByType('GridFieldAddExistingAutocompleter');
			//$fieldConfig->removeComponentsByType('GridFieldEditButton');
			$list = ProductCategory::get()->filter(array('ParentID' => 0));
		}

		$listField = new GridField(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig
		);
		
		$categories = ProductCategory::getAllCategories();
		if($this->modelClass == 'Product' && !$categories){
			$listField = new LiteralField('CategoryWarning', '<p class="message warning">Please create a category in the site tree before creating products.</p>');	
		}

		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
		}

		$form = new Form(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		);
		
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm'));
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);
		
		return $form;
	}
}


/**
 * Shop admin area for managing product attributes.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
 /*
class CatalogAdmin_Attribute extends CatalogAdmin {
	
	private static $tree_class = 'ShopConfig';
	
	private static $allowed_actions = array(
		'AttributeSettings',
		'AttributeSettingsForm',
		'saveAttributeSettings'
	);

	private static $url_rule = 'catalog/attribute';
	private static $url_priority = 75;
	private static $menu_title = 'Attributes & Options';

	private static $url_handlers = array(
		'catalog/Attribute/AttributeSettingsForm' => 'AttributeSettingsForm',
		'catalog/Attribute' => 'AttributeSettings'
	);

	public function init() {
		parent::init();
		if (!in_array(get_class($this), self::$hidden_sections)) {
			$this->modelClass = 'ShopConfig';
		}
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Attribute Settings',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'Attribute'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {
		return $this->AttributeSettingsForm();
	}

	public function AttributeSettings($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->AttributeSettingsForm()->forTemplate();
					},
					'Content' => function() use(&$controller) {
						return $controller->renderWith('ShopAdminSettings_Content');
					},
					'Breadcrumbs' => function() use (&$controller) {
						return $controller->renderWith('CMSBreadcrumbs');
					},
					'default' => function() use(&$controller) {
						return $controller->renderWith($controller->getViewer('show'));
					}
				),
				$this->response
			); 
			return $responseNegotiator->respond($this->getRequest());
		}

		return $this->renderWith('ShopAdminSettings');
	}

	public function AttributeSettingsForm() {

		$shopConfig = ShopConfig::get()->First();

		$fields = new FieldList(
			$rootTab = new TabSet('Root',
				$tabMain = new Tab('Attribute',
					GridField::create(
						'Attributes',
						'Attributes',
						$shopConfig->Attributes(),
						GridFieldConfig_HasManyRelationEditor::create()
					)
				)
			)
		);

		$actions = new FieldList();
		$actions->push(FormAction::create('saveAttributeSettings', _t('GridFieldDetailForm.Save', 'Save'))
			->setUseButtonTag(true)
			->addExtraClass('ss-ui-action-constructive')
			->setAttribute('data-icon', 'add'));

		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);

		$form->setTemplate('ShopAdminSettings_EditForm');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
		if($form->Fields()->hasTabset()){
			$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		}
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'Attribute/AttributeSettingsForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function saveAttributeSettings($data, $form) {
		//Hack for LeftAndMain::getRecord()
		self::$tree_class = 'ShopConfig';

		$config = ShopConfig::get()->First();
		$form->saveInto($config);
		$config->write();
		$form->sessionMessage('Saved Attribute Settings', 'good');

		$controller = $this;
		$responseNegotiator = new PjaxResponseNegotiator(
			array(
				'CurrentForm' => function() use(&$controller) {
					//return $controller->renderWith('ShopAdminSettings_Content');
					return $controller->AttributeSettingsForm()->forTemplate();
				},
				'Content' => function() use(&$controller) {
					//return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
				},
				'Breadcrumbs' => function() use (&$controller) {
					return $controller->renderWith('CMSBreadcrumbs');
				},
				'default' => function() use(&$controller) {
					return $controller->renderWith($controller->getViewer('show'));
				}
			),
			$this->response
		); 
		return $responseNegotiator->respond($this->getRequest());
	}

	public function getSnippet() {

		if (!$member = Member::currentUser()) return false;
		if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) return false;

		return $this->customise(array(
			'Title' => 'Attribute Management',
			'Help' => 'Create default attributes',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'Attribute'),
			'LinkTitle' => 'Edit default attributes'
		))->renderWith('ShopAdmin_Snippet');
	}
}
*/