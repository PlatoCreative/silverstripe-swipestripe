<?php
/**
 * A checkout page for displaying the checkout form to a visitor.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPage extends Page {
	
	/**
	 * Automatically create a CheckoutPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('CheckoutPage')) {
			$page = new CheckoutPage();
			$page->Title = 'Checkout';
			$page->Content = '';
			$page->URLSegment = 'checkout';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Checkout page \'Checkout\' created', 'created');
		}
	}
	
	/**
	 * Prevent CMS users from creating another checkout page.
	 * 
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
	function canCreate($member = null) {
		return false;
	}
	
	/**
	 * Prevent CMS users from deleting the checkout page.
	 * 
	 * @see SiteTree::canDelete()
	 * @return Boolean Always returns false
	 */
	function canDelete($member = null) {
		return false;
	}

	public function delete() {
		if ($this->canDelete(Member::currentUser())) {
			parent::delete();
		}
	}
	
	/**
	 * Prevent CMS users from unpublishing the checkout page.
	 * 
	 * @see SiteTree::canDeleteFromLive()
	 * @see CheckoutPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
	function canDeleteFromLive($member = null) {
		return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 * @see CheckoutPage::canDeleteFromLive()
	 * @return FieldList Actions fieldset with unpublish action removed
	 */
	function getCMSActions() {
		$actions = parent::getCMSActions();
		$actions->removeByName('action_unpublish');
		return $actions;
	}
	
	/**
	 * Remove page type dropdown to prevent users from changing page type.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('ClassName');
		return $fields;
	}
}

/**
 * Display the checkout page, with order form. Process the order - send the order details
 * off to the Payment class.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPage_Controller extends Page_Controller {

	protected $orderProcessed = false;

	private static $allowed_actions = array (
		'index',
		'OrderForm',
		'RegistrationForm',
		'LoginForm',
		'login'
	);
	
	public function init(){
		parent::init();		
		Session::set('BackURL',$this->Link());
		
		// Include some CSS and javascript for the checkout page
		Requirements::css('swipestripe/css/Shop.css');
		
		return array( 
			'Content' => $this->Content, 
			'Form' => $this->OrderForm()
		);
	}
	
	/**
	 * Include some CSS and javascript for the checkout page
	 * 
	 * TODO why didn't I use init() here?
	 * 
	 * @return Array Contents for page rendering
	 */
	 /*
	function index() {	
		// Update stock levels
		// Order::delete_abandoned();
		
		Requirements::css('swipestripe/css/Shop.css');
		
		return array( 
			'Content' => $this->Content, 
			'Form' => $this->OrderForm()
		);
	}
	*/

	function OrderForm() {
		$order = Cart::get_current_order();
		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

		$form = OrderForm::create(
			$this, 
			'OrderForm'
		)->disableSecurityToken();

		//Populate fields the first time form is loaded
		$form->populateFields();

		return $form;
	}
	
	/*
	*	Register form before checkout
	*/
	public function RegistrationForm(){
		$fields = new FieldList(
			new CompositeField(
				EmailField::create('Email', _t('CheckoutPage.EMAIL', 'Email'))
					->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_EMAIL_ADDRESS', "Please enter your email address."))
			),
			new CompositeField(
				new FieldGroup(
					new ConfirmedPasswordField('Password', _t('CheckoutPage.PASSWORD', "Password"))
				)
			)
		);
		
		$actions = new FieldList(
			FormAction::create("register", 'Register')
		);
		
		return Form::create(
			$this,
			"RegistrationForm",
			$fields,
			FieldList::create(FormAction::create('register', 'Register')),
			RequiredFields::create(array(
				'Email'
			))
		);	
	}

	/*
	*	Do Member Registration
	*/
	public function register($data, $form) {
		//Save or create a new customer/member
		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
		if(!$member->exists()){
			$existingCustomer = Customer::get()->where("\"Email\" = '".$data['Email']."'");
			// does the customer already exist?
			if ($existingCustomer && $existingCustomer->exists()) {
				$form->sessionMessage(
					_t('CheckoutPage.MEMBER_ALREADY_EXISTS', 'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.'),
					'bad'
				);
			} else {
				// no they don't so create them and log them in.
				$member = Customer::create();
				$form->saveInto($member);
				$member->write();
				$member->addToGroupByCode('customers');
				$member->logIn();
			}		
		}
		
		return Controller::curr()->redirect("/Checkout");
	}
}