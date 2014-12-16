<?php
/**
 * An account page which displays the order history for any given {@link Member} and displays an individual {@link Order}.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class AccountPage extends Page {

	/**
	 * Automatically create an AccountPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('AccountPage')) {
			$page = new AccountPage();
			$page->Title = 'Account';
			$page->Content = '';
			$page->URLSegment = 'account';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Account page \'Account\' created', 'created');
		}
	}
	
	/**
	 * Prevent CMS users from creating another account page.
	 * 
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
	function canCreate($member = null) {
		return false;
	}
	
	/**
	 * Prevent CMS users from deleting the account page.
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
	 * Prevent CMS users from unpublishing the account page.
	 * 
	 * @see SiteTree::canDeleteFromLive()
	 * @see AccountPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
	function canDeleteFromLive($member = null) {
		return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 * @see AccountPage::canDeleteFromLive()
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
 * Display the account page with listing of previous orders, and display an individual order.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class AccountPage_Controller extends Page_Controller {
	
	/**
	 * Allowed actions that can be invoked.
	 * 
	 * @var Array Set of actions
	 */
	private static $allowed_actions = array (
		'index',
		'vieworders',
		'order',
		'repay',
		'RepayForm',
		'RegisterAccount',
		'RegisterAccountForm',
		'EditAccount',
		'EditAccountForm',
	);
	
	public function init() {
		parent::init();
		$params = $this->getURLParams();
		if(!Permission::check('VIEW_ORDER') && $params['Action'] != 'RegisterAccount' && $params['Action'] != 'RegisterAccountForm') {
			return $this->redirect(Director::absoluteBaseURL() . 'Security/login?BackURL=' . urlencode($this->getRequest()->getVar('url')));
		}
	}
	
	/**
	 * Check access permissions for account page and return content for displaying the 
	 * default page.
	 * 
	 * @return Array Content data for displaying the page.
	 */
	function index() {
		return $this->customise(array( 
			'Content' => $this->Content, 
			'Form' => $this->Form,
			'Orders' => Order::get()->where("MemberID = " . Convert::raw2sql(Member::currentUserID()))->sort('Created DESC'),
			'Customer' => Customer::currentUser()
		));
	}
	
	function vieworders($request) {
		return $this->customise(array(
			'Orders' => Order::get()->where("MemberID = " . Convert::raw2sql(Member::currentUserID()))->sort('Created DESC')
		));
	}

	/**
	 * Return the {@link Order} details for the current Order ID that we're viewing (ID parameter in URL).
	 * 
	 * @return Array Content for displaying the page
	 */
	function order($request) {
		if($orderID = $request->param('ID')) {
			$member = Customer::currentUser();
			$order = Order::get()
				->where("\"Order\".\"ID\" = " . Convert::raw2sql($orderID))
				->First();

			if (!$order || !$order->exists()) {
				return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
			}

			if(!$order->canView($member)) {
				return $this->httpError(403, _t('AccountPage.CANNOT_VIEW_ORDER', 'You cannot view orders that do not belong to you.'));
			}

			return array(
				'Order' => $order
			);
		} else {
			return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
		}
	}
	
	function repay($request){
		if ($orderID = $request->param('ID')) {			
			$member = Customer::currentUser();
			$order = Order::get()
				->where("\"Order\".\"ID\" = " . Convert::raw2sql($orderID))
				->First();

			if (!$order || !$order->exists()) {
				return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
			}

			if (!$order->canView($member)) {
				return $this->httpError(403, _t('AccountPage.CANNOT_VIEW_ORDER', 'You cannot view orders that do not belong to you.'));
			}
			
			Session::set('Repay', array(
				'OrderID' => $order->ID
			));
			Session::save();
			
			return array(
				'Order' => $order,
				'RepayForm' => $this->RepayForm()
			);
		}
		else {
			return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
		}
	}

	function RepayForm() {
		$form = RepayForm::create(
			$this, 
			'RepayForm'
		)->disableSecurityToken();

		//Populate fields the first time form is loaded
		$form->populateFields();

		return $form;
	}
	
	/*
	*	Register and edit account functionality
	*/
	
	// Function for registering user account details
	function RegisterAccount($request){
		return $this->customise(array(
			'Title' => 'Register an account',
			'Content' => '',
			'Form' => $this->RegisterAccountForm()
		));
	}
	
	// Form for registering account
	function RegisterAccountForm(){		
		$fields = FieldList::create(
			TextField::create('FirstName', 'First Name', ''),
			TextField::create('Surname', 'Surname', ''),
			EmailField::create('Email', 'Email address', ''),
			ConfirmedPasswordField::create('Password', 'Password', '')
		);
		
		$required = RequiredFields::create('FirstName', 'Surname', 'Email', 'Password');		
		
		$this->extend('updateRegisterAccountFields', $fields, $customer, $required);
		
		$actions = FieldList::create(
			FormAction::create('doRegisterAccount', 'Create')
		);
		
		return Form::create($this, 'RegisterAccountForm', $fields, $actions, $required);
	}
	
	// Update the users account
	function doRegisterAccount($data, $form){
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
		
		$this->extend('updateAccountRegister', $data, $customer);
		
		$form->sessionMessage('Account created successfully', 'good');
		
		return Controller::curr()->redirect("/Account");
	}
	
	// Function for updating user account details
	function EditAccount($request){
		return $this->customise(array(
			'Title' => 'Edit your account details',
			'Content' => '',
			'Form' => $this->EditAccountForm()
		));
	}
	
	// Form for editing account
	function EditAccountForm(){
		$customer = Customer::currentUser();
		
		$fields = FieldList::create(
			TextField::create('FirstName', 'First Name', $customer->FirstName),
			TextField::create('Surname', 'Surname', $customer->Surname),
			EmailField::create('Email', 'Email address', $customer->Email),
			ConfirmedPasswordField::create('Password', 'Password', $customer->Password, $this, true)
		);
		
		$required = RequiredFields::create('FirstName', 'Surname', 'Email', 'Password');		
		
		$this->extend('updateEditAccountFields', $fields, $customer, $required);
		
		$actions = FieldList::create(
			FormAction::create('doEditAccount', 'Update')
		);
		
		return Form::create($this, 'EditAccountForm', $fields, $actions, $required);
	}
	
	// Update the users account
	function doEditAccount($data, $form){
		$customer = Customer::currentUser();
		
		if($customer->exists()){	
			$form->saveInto($customer);
	
			$this->extend('updateAccountEdit', $data, $customer);
			
			$customer->write();
		}
		
		return $this->redirectBack();
	}
}