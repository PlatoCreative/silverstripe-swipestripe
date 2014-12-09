<?php
/**
 * Order admin area for managing current and completed orders.
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
        	$list = Product::get();
        }
				
        return $list;
    }
	
	public function getEditForm($id = null, $fields = null) {		
		$list = $this->getList();

		$buttonAfter = new GridFieldButtonRow('after');
		$exportButton = new GridFieldExportButton('buttons-after-left');
		$exportButton->setExportColumns($this->getExportFields());
		
		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))->addComponent($buttonAfter)->addComponent($exportButton);
		//$fieldConfig->removeComponentsByType('GridFieldExportButton');
		$fieldConfig->removeComponentsByType('GridFieldAddNewButton');
		$fieldConfig->removeComponentsByType('GridFieldDeleteAction');
		$fieldConfig->removeComponentsByType('GridFieldAddExistingAutocompleter');
	
		$listField = new GridField(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig
		);

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