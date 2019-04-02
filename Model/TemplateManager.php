<?php
/**
 * COmanage Registry TemplateManager Model
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class TemplateManager extends AppModel {
  // Define class name for cake
  public $name = "TemplateManager";

  // define the plugin types this plugin caters
  public $cmPluginType = "config";
  
  public $useTable="templatemanager";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  //public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $hasOne = array();
  
  // Association rules from this model to other models
  // Depend on CO (if CO is deleted, also delete this entry)
  public $belongsTo = array(
    "Co" => array("dependent"=>true)
  );
  public $cmPluginHasMany = array(
    "Co" => array("TemplateManager")
  );

  // Default display field for cake generated views
  public $displayField = "TemplateManager";
  
  // Default ordering for find operations
  public $order = array("TemplateManager.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
//    'api_key' => array(
//      'rule' => 'text',
//      'required' => false,
//      'allowEmpty' => true
//    ),
//    'settings' => array(
//      'rule' => 'text',
//      'required' => false,
//      'allowEmpty' => true
//    )
  );

  public $cm_enum_types = array(
    'status' => 'TemplateableStatusEnum'
  );

  /**
   * Expose menu items.
   * 
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */

  public function cmPluginMenus() {
    return array(
      "coconfig" => array(_txt('pl.ct.templatemanager') =>
                        array('icon' => 'grid_on',
                              'controller' => 'template_manager',
                              'action'     => 'index'))
    );
  }
}
