<?php
/**
 * Product gallery image
 * 
 * @author Plato Creative
 * @package swipestripe
 * @subpackage product
 */
class ProductImage extends DataObject {
	private static $singular_name = 'Product Image';
	private static $plural_name = 'Product Images';

	/**
	 * DB fields for this Image
	 * 
	 * @var Array
	 */
	private static $db = array(
		'Title' => 'Varchar(255)',
		'SortOrder' => 'Int'
	);

	/**
	 * Has one relations for image
	 * 
	 * @var Array
	 */
	private static $has_one = array(
		'Image' => 'Image',
		'Product' => 'Product'
	);
	
	private static $default_sort = 'SortOrder';

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields = FieldList::create(
			TextField::create('Title', 'Title'),
			UploadField::create('Image', 'Upload an image')->setFolderName('ProductImages')
		);
		
		return $fields;
	}
	
	public function Thumbnail() {
		$image = $this->Image();
		if($image && $image->exists()) {
			return $image->SetRatioSize(100,100);
		}		
		return null;
	}
}