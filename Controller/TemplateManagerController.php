<?php
/**
 * COmanage Registry Yoda specific Controller
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("AppController", "Controller");
App::uses('CakeEmail', 'Network/Email');

class TemplateManagerController extends AppController {
  // Class name, used by Cake
  public $name = "TemplateManager";
  //public $components=array('');

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'name' => 'asc'
    )
  );

  public $requires_co=true;
  
  public $model = null;
  public $settings = null;
  
  private function getModel() {
    if(empty($this->model)) {
      if($this->request->is('restful')) {
        $this->Api->checkRestPost();
        $data = $this->Api->getData();
        $coid = $data["co_id"];
      } 
      else {
        $coid = $this->cur_co['Co']['id'];
      }

      $args=array();
      $args['conditions']['co_id'] = $coid;
      $args['contains']=array('Co');
      $this->model=$this->TemplateManager->find('first',$args);
      
      if(empty($this->model)) {
        $this->model = array(
          'TemplateManager' => array('api_key'=>'','settings'=>''),
          'Co' => array('id'=>$coid,'name'=>'','description'=>'')
        );
      }

      $this->settings=array();
      if(!empty($this->model) && isset($this->model['TemplateManager']) && isset($this->model['TemplateManager']['settings'])) {
        $this->settings = json_decode($this->model['TemplateManager']['settings'], TRUE);
      }
    }
    return $this->model;
  }

  /**
   * Override beforeFilter to allow authentication based on our
   * plugin specific api_key
   */
  public function beforeFilter() {
    if($this->request->is('restful')) {

      $model=$this->getModel();
      $data = $this->Api->getData();
      $api_key = $data["key"];
      if(isset($model['TemplateManager']) && $api_key == $model['TemplateManager']['api_key']) {
        // 'cheat' the authentication system by creating a transient entry for this user
        // and indicating it is an api_user
        $this->Session->write('Auth.User.name', $model['Co']['name']);
        $this->Session->write('Auth.User.api_user_id', 1);
      }
    }
    parent::beforeFilter();
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    $this->copersonid=null;
    if(isset($roles['copersonid']))
    {
      $this->copersonid = $roles['copersonid'];
    }

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Determine what operations this user can perform

    // Configure the page settings
    // We allow COAdmin as well as CMAdmins. This allows us to enroll people into the template CO
    // and have these people configure this specific CO template and only this template, without 
    // elevating them to CMAdmin level
    $p['index'] = $roles['coadmin'] || $roles['cmadmin'];

    // API calls are only allowed for 'cmadmin', which is the API user and the TemplateManager specific
    // call as set above
    // If there is an "actions" value in our model settings, use that to further restrict access
    $actions=array("instantiate","enroll");
    if(isset($this->settings["actions"]) && !empty($this->settings["actions"])) {
      $actions=$this->settings["actions"];
    }

    $p['instantiate'] = $roles['cmadmin'] && in_array("instantiate",$actions);
    $p['enroll'] = $roles['cmadmin'] && in_array("enroll",$actions);

    $this->set('permissions', $p);
    return $p[$this->action];
  }

 /**
   * Parse the named co parameter
   *
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if(in_array($this->action,array('index'))) {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    return parent::parseCOID();
  }


  public function index() {
    $model=$this->getModel();

    $args=array();
    $args['conditions']['CoMessageTemplate.co_id'] = $this->model['Co']['id'];
    $args['contain']=false;
    $tmodels=$this->TemplateManager->Co->CoMessageTemplate->find('all',$args);

    if($this->request->is(array('post','put'))) {
      try {
        $data = $this->request->data['TemplateManager'];

        // link the TemplateManager and CO instance
        if(isset($model['TemplateManager']['id'])) {
            $data['id']=$model['TemplateManager']['id'];
        }
        $data['co_id']=$model['Co']['id'];

        // convert individual settings to a JSON blob, so we do not run into
        // database conversion problems if we need to add or remove fields
        $settings=array();
        if(isset($data['from'])) $settings['from']=$data['from'];
        if(isset($data['actions'])) $settings['actions']=$data['actions'];
        if(isset($data['enroll'])) $settings['enrollment']=$data['enroll'];
        if(isset($data['script'])) $settings['run']=$data['script'];
        if(isset($data['description'])) $settings['description']=$data['description'];
        if(isset($data['name'])) $settings['name']=$data['name'];
        if(isset($data['subject'])) $settings['subject']=$data['subject'];
        if(isset($data['template_body'])) $settings['template_body']=$data['template_body'];

        // convert ID to a template description, so we can use the template copy of
        // the message template in the new CO
        if(isset($data['template_id'])) {
          foreach($tmodels as $t) {
            if($t['CoMessageTemplate']['id'] == $data['template_id']) {
              $settings['template']=$t['CoMessageTemplate']['description'];
              break;
            }
          }
        }

        $data["settings"]=json_encode($settings, JSON_PRETTY_PRINT);
        unset($data['actions']);
        unset($data['from']);
        unset($data['enroll']);
        unset($data['script']);
        unset($data['description']);
        unset($data['name']);
        unset($data['subject']);
        unset($data['template_body']);
        unset($data['template_id']);

        $ret = $this->TemplateManager->save($data);
        if(!empty($ret)) {
          $model=array_merge($model,$ret);
        }
      }
      catch(Exception $e) {
        $err = filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS);
        $this->Flash->set($err ?: _txt('er.fields'), array('key' => 'error'));
      }
    }

    // Set View variables
    $settings=json_decode($model['TemplateManager']['settings'], TRUE);
    $model['TemplateManager']['actions'] = isset($settings["actions"]) ? $settings["actions"] : array();
    $model['TemplateManager']['enroll'] = isset($settings["enrollment"]) ? $settings["enrollment"] : array();
    $model['TemplateManager']['script'] = isset($settings["run"]) ? $settings["run"] : array();
    $model['TemplateManager']['name'] = isset($settings["name"]) ? $settings["name"] : array();
    $model['TemplateManager']['from'] = isset($settings["from"]) ? $settings["from"] : array();
    $model['TemplateManager']['description'] = isset($settings["description"]) ? $settings["description"] : array();
    $model['TemplateManager']['subject'] = isset($settings["subject"]) ? $settings["subject"] : array();
    $model['TemplateManager']['template_body'] = isset($settings["template_body"]) ? $settings["template_body"] : array();
    $this->set("action_options",array("instantiate"=>"instantiate","enroll"=>"enroll"));

    $args=array();
    $args['conditions']['CoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
    $args['contain']=false;
    $efs=$this->TemplateManager->Co->CoEnrollmentFlow->find('all',$args);

    $flows=array();
    foreach($efs as $ef) {
      if($ef['CoEnrollmentFlow']['status'] == TemplateableStatusEnum::Active) {
        $flows[$ef['CoEnrollmentFlow']['name']]=$ef['CoEnrollmentFlow']['name'];
      }
    }
    $this->set("enroll_options",$flows);

    $templates=array();
    $searchFor=isset($settings['template']) ? $settings['template'] : '';
    foreach($tmodels as $m) {
      if($m['CoMessageTemplate']['description'] == $searchFor) {
        $model['TemplateManager']['template_id']=$m['CoMessageTemplate']['id'];
      }
      $templates[$m['CoMessageTemplate']['id']] = $m['CoMessageTemplate']['description'];
    }
    $this->set("template_options",$templates);
    $this->request->data = $model;
  }

  private function baseAuthorization($onsuccess, $status) {
    if($this->request->is('restful')) {

      $model=$this->getModel();
      $data = $this->Api->getData();

      if(!empty($model)) {
        Configure::load('templatemanager','default');

        if(!is_array($status)) $status=array($status);
        if(in_array($model['Co']['status'], $status)) {

          // put this in a database transaction
          $dbc = $this->TemplateManager->getDataSource();
          $dbc->begin();

          try {
            $this->{$onsuccess}($data);
            $dbc->commit();
          }
          catch(Exception $e) {
            $dbc->rollback();
            $this->Api->restResultHeader(500, $e->getMessage());
            $this->devLog("caught exception on main method: ".$e->getMessage());
          }
        } else {
          // only templates can be duplicated
          $this->Api->restResultHeader(403, "Forbidden");
          $this->devLog("CO state incorrect");
        }
      } else {
        // unconfigured template
        $this->Api->restResultHeader(403, "Forbidden");
        $this->devLog("no template configured");
      }
    } else {
      // redirect to the homepage for non-restful calls
      $this->redirect('/');
      $this->devLog("not a restful request");
    }
  }

  public function instantiate()
  {
    $this->baseAuthorization("doInstantiate", TemplateableStatusEnum::Template);
  }
  
  private function doInstantiate($data) {
    $newCoId = null;
    try {
      $newCoId = $this->TemplateManager->Co->duplicate($this->model['Co']['id']);
    }
    catch (Exception $e) {
      // internal error while duplicating... duplicate name perhaps?
      // rethrow to set the 500 error and rollback the transaction
      throw new Exception($e->getMessage());
    }

    $args=array();
    $args['conditions']['Co.id'] = $newCoId;
    $args['contain']=false;
    $newModel=$this->TemplateManager->Co->find('first',$args);

    try {
      // Perform post-configuration of the CO
      $key = $this->configureCO($newModel, $data);
      $this->runScript($newCoId);
      // return the CO id
      $this->set("co_id",$newCoId);
      $this->set("api_key",$key);
      $this->Api->restResultHeader(201, "Added");
    }
    catch (Exception $e) {
      // delete the new CO as well
      // Exceptions are caught upstream and cause a database rollback, which
      // should be fine as well
      $this->TemplateManager->Co->delete($newCoId);

      // rethrow to set the 500 error and rollback the transaction
      throw new Exception($e->getMessage());
    }
  }

  public function enroll() {
    $this->baseAuthorization("doEnroll", TemplateableStatusEnum::Active);
  }

  private function doEnroll($data) {

    if(empty($this->model['Co']) || empty($this->model['Co']['id'])) {
      $this->Api->restResultHeader(403, "Forbidden");
      return FALSE;
    }
    $newCoId = $this->model['TemplateManager']['co_id'];

    if(isset($data['invitation']) || isset($data['signup'])) {
      $enrollmentSpec = isset($data['invitation']) ? $data['invitation'] : $data['signup'];

      // check that this enrollment is allowed as per the template settings
      if(isset($this->settings['enrollment']) && isset($this->settings['enrollment'])) {
        $efs=$this->settings['enrollment'];
        if(!is_array($efs)) $efs=array($efs);

        if(!isset($enrollmentSpec['name']) || !in_array($enrollmentSpec['name'], $efs)) {
          $this->Api->restResultHeader(403, "Forbidden");
          return FALSE;
        }
      }

      $args=array();
      $args['conditions']['CoEnrollmentFlow.name'] = $enrollmentSpec['name'];
      $args['conditions']['CoEnrollmentFlow.co_id'] = $newCoId;
      $args['contain']=false;
      $ef=$this->TemplateManager->Co->CoEnrollmentFlow->find('first',$args);

      if(empty($ef) || $ef['CoEnrollmentFlow']['status'] != TemplateableStatusEnum::Active) {
        $this->Api->restResultHeader(403, "Forbidden");
        return FALSE;
      }

      // check that this is not a self-signup. For a self-signup, the
      // authorization is absent, or it is set to authorized users. 
      // (in which case we use, for example, SamlSource or EnvSource in
      // authenticate mode to read initial enrollment attributes)
      if(isset($data['signup'])) {
        if(in_array($ef['CoEnrollmentFlow']['authz_level'], array(EnrollmentAuthzEnum::None, EnrollmentAuthzEnum::AuthUser))) {
          return $this->sendSelfSignupLink($data['signup'], $ef);
        } else {
          $this->Api->restResultHeader(403, "Forbidden");
          return FALSE;
        }
      } else {
        if($ef['CoEnrollmentFlow']['authz_level'] != EnrollmentAuthzEnum::None) {
          return $this->runInviteSteps($data['invitation'], $ef, $newCoId);
        } else {
          $this->Api->restResultHeader(403, "Forbidden");
          return FALSE;
        }
      }
      return TRUE;
    }
    else if(isset($data['org_identity'])) {
      if($this->copyOrgIdentity($data['org_identity'], $newCoId)) {
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /** 
   * copyOrgIdentity
   * Copies a known OrgIdentity (by ID or by identifier) to the new CO
   * and creates a COPerson record using information provided in the settings
   * and API call.
   * This circumvents regular on-boarding using enrollment, which is a bad thing
   * auditable wise, VjgSGrOu4gWdhuzihHzRePwJVwjgTJWwbut it 'does the job' and this only applies to the first
   * admin user.
   */
  private function copyOrgIdentity($data) {
    $oid=isset($data['id']) ? $data['id'] : null;
    $args=array();
    $args['conditions']['OrgIdentity.id'] = $oid;
    $args['conditions']['OrgIdentity.co_id'] = $this->model['TemplateManager']['parent_id'];
    $oi=$this->TemplateManager->Co->OrgIdentity->find('first',$args);

    if(empty($oi)) {
      $args=array();
      $args['conditions']['Identifier.identifier'] = $oid;
      $args['conditions']['Identifier.login'] = 1;
      $args['conditions']['Identifier.co_person_id'] = null;
      $args['contain']=false;
      $identifier=$this->TemplateManager->Co->OrgIdentity->Identifier->find('first',$args);

      if(!empty($identifier)) {
        $args=array();
        $args['conditions']['OrgIdentity.id'] = $identifier['Identifier']['org_identity_id'];
        $args['conditions']['OrgIdentity.co_id'] = $this->model['TemplateManager']['parent_id'];
        $oi=$this->TemplateManager->Co->OrgIdentity->find('first',$args);
      }
    }

    if(empty($oi)) {
      $this->Api->restResultHeader(403, "Forbidden");
      return FALSE;
    }

    $newId = $this->TemplateManager->Co->OrgIdentity->duplicate($oi['OrgIdentity']['id'], $this->model['Co']['id']);
    $ea = isset($oi['EmailAddress']) ? $oi['EmailAddress'][0] : array();

    $coperson=array(
      'CoPerson' => array(
        'co_id' => $this->model['Co']['id'],
        'status' => StatusEnum::Active
      )
    );
    $coperson = $this->TemplateManager->Co->CoPerson->save($coperson);

    if(!$coperson) {
      throw new Exception("Validation errors: ".json_encode($this->TemplateManager->Co->CoPerson->validationErrors));
    }

    $role = array(
      'CoPersonRole' => array(
        'affiliation' => $this->firstOf('affiliation',$data, $oi['OrgIdentity']),
        'title' => $this->firstOf('title',$data, $oi['OrgIdentity']),
        'o' => $this->firstOf('o',$data, $oi['OrgIdentity']),
        'ou' => $this->firstOf('ou',$data, $oi['OrgIdentity']),
        'status' => StatusEnum::Active,
        'co_person_id' => $coperson['CoPerson']['id']
      )
    );
    $role = $this->TemplateManager->Co->CoPerson->CoPersonRole->save($role);
    if(!$role) {
      throw new Exception("Validation errors: ".json_encode($this->TemplateManager->Co->CoPerson->CoPersonRole->validationErrors));
    }

    $name=array(
      'Name' => array(
        'honorific' => $this->firstOf('honorific', $data, $oi['PrimaryName']),
        'given' => $this->firstOf('given', $data, $oi['PrimaryName']),
        'middle' => $this->firstOf('middle', $data, $oi['PrimaryName']),
        'family' => $this->firstOf('family', $data, $oi['PrimaryName']),
        'suffix' => $this->firstOf('suffix', $data, $oi['PrimaryName']),
        'type' => NameEnum::Preferred,
        'primary_name' => 1,
        'co_person_id' => $coperson['CoPerson']['id'],
      )
    );
    $name = $this->TemplateManager->Co->CoPerson->Name->save($name);
    if(!$name) {
      throw new Exception("Validation errors: ".json_encode($this->TemplateManager->Co->CoPerson->Name->validationErrors));
    }

    $ea = isset($oi['EmailAddress']) ? $oi['EmailAddress'][0] : array();
    $mail = array(
      'EmailAddress' => array(
        "mail" => $this->firstOf('mail',$data,$ea),
        "type" => EmailAddressEnum::Preferred,
        "verified" => 1,
        "co_person_id" => $coperson['CoPerson']['id']
      )
    );
    $mail = $this->TemplateManager->Co->CoPerson->EmailAddress->save($mail);
    if(!$mail) {
      throw new Exception("Validation errors: ".json_encode($this->TemplateManager->Co->CoPerson->EmailAddress->validationErrors));
    }

    // Link the COPerson to the OrgIdentity
    $coOrgLink = array();
    $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $newId;
    $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coperson['CoPerson']['id'];

    // CoOrgIdentityLink is not currently provisioner-enabled, but we'll disable
    // provisioning just in case that changes in the future.
    if($this->TemplateManager->Co->CoPerson->CoOrgIdentityLink->save($coOrgLink, array("provision" => false))) {
       // Create a history record
       $this->TemplateManager->Co->CoPerson->HistoryRecord->record(
           $coperson['CoPerson']['id'],
           $role['CoPersonRole']['id'],
           $newId,
           null,
           ActionEnum::CoPersonOrgIdLinked);
    }
    else {
      throw new Exception("Error creating link to OrgIdentity");
    }
    return TRUE;
  }

  private function firstOf($key, $lst1, $lst2, $def="") {
    return isset($lst1[$key]) ? $lst1[$key] : (isset($lst2[$key]) ? $lst2[$key] : $def);
  }

  /**
   * sendSelfSignupLink
   * Takes a default template and sends the provided email address an e-mail containing
   * the relevant link to start a self-signup enrollment.
   */
  private function sendSelfSignupLink($data, $ef) {
    // determine the template to use first
    $template= $this->firstOf('template',$data,$this->settings);
    $body= $this->firstOf('template_body',array(),$this->settings);
    $subject= $this->firstOf('subject',$data, $this->settings);
    $from= $this->firstOf('from',$data, $this->settings);

    // see if there is a template with this name
    $args=array();
    $args['conditions']['CoMessageTemplate.description'] = $template;
    $args['conditions']['CoMessageTemplate.co_id'] = $this->model['Co']['id'];
    $args['contain']=false;
    $tmodel=$this->TemplateManager->Co->CoMessageTemplate->find('first',$args);
    if(!empty($tmodel)) {
      $body = $tmodel['CoMessageTemplate']['message_body'];
      $subject = $tmodel['CoMessageTemplate']['message_subject'];
    }
    else if(!empty($template)) {
      $body = $template;
    }

    $substitutions = array(
      'CO_NAME'   => $this->model['Co']['name'],
      'INVITE_URL' => Router::url(array(
                                  'plugin'     => null,
                                  'controller' => 'co_petitions',
                                  'action'     => 'start',
                                  'coef'       => $ef['CoEnrollmentFlow']['id']
                                 ),
                                 true)
    );

    $subject = processTemplate($subject, $substitutions);
    $body = processTemplate($body, $substitutions);

    $email = new CakeEmail('default');
    if(!empty($from)) {
      $email->from($from);
    }

    if(!empty($tmodel) && !empty($tmodel['CoMessageTemplate']['cc'])) {
      $email->cc(explode(',', $tmodel['CoMessageTemplate']['cc']));
    }
    if(!empty($tmodel) && !empty($tmodel['CoMessageTemplate']['bcc'])) {
      $email->bcc(explode(',', $tmodel['CoMessageTemplate']['bcc']));
    }

    $email->emailFormat('text')->to($data['email'])->subject($subject)->send($body);
    return TRUE;
  }

  /**
   * runEnrollmentStep
   * Executes enrollment steps for invitation-style flows.
   * This executes the first steps of the enrollment flow up to entering the
   * petitioner attributes and sending a confirmation.
   * This does NOT execute plugin enrollers.
    */
  private function runInviteSteps($data, $ef, $newCoId) {
    $efid=$ef['CoEnrollmentFlow']['id'];
    // take the shortcut for a typical invite flow and execute the relevant steps in order:
    //
    // start generates the petition and displays start information.
    $ptid = $this->TemplateManager->Co->CoPetition->initialize($efid, $newCoId, null, null);

    // selectEnrollee allows matching the enrollee Identity by the petitioner beforehand.
    // This is typical for complicated enrollments that provide additional roles to 
    // people already enrolled. We do not need this (IdentityMatching is set to None).

    // selectOrgIdentity is only enabled if there are any OrgIdentitySources attached to the
    // current enrollment flow. For invite flows, the administrator can then select from the
    // list of OrgIdentities from that source and create an invite for that user. As we do
    // not support plugins, which enrollment sources are, we can skip this.

    // This step is either required (if there are attributes) or optional. In our case, we
    // take the defined attributes and set them using the supplied data and settings

    // first retrieve the defined enrollment attributes
    $efAttrs = $this->TemplateManager->Co->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($efid);
    $requestData=array();
    $attributes=isset($data['attributes']) ? $data['attributes'] : array();
    if(!is_array($attributes)) $attributes=array();

    foreach($efAttrs as $efAttr) {
      $label = isset($efAttr['label']) ? $efAttr['label'] : "";

      $attrname = $efAttr['attribute'];
      $modelName = $efAttr['model'];
      $fieldName = $efAttr['field'];
      $value = isset($efAttr['default']) ? $efAttr['default'] : "";

      if(!empty($label) && isset($attributes[$label])) {
        $value = $attributes[$label];
      }

      if(!empty($value)) {
        // first we build up the dot separated model.field name, then we
        // convert it to the expected data format (as defined in Cake/View/Helper.php)
        // Finally, we parse this field back using parse_str to get the right 
        // data structure
        $name = $modelName.'.'.$fieldName;
        $field = $this->convertNameToData($name, $value);
        $requestData=$this->mergeData($requestData,$field);
      }
    }
    $this->TemplateManager->Co->CoPetition->saveAttributes($ptid, $efid, $requestData['data'], null);

    // finally send a confirmation message and hand over the petition to the regular flow
    $ea = $this->TemplateManager->Co->CoPetition->needConfirmation($ptid);
    if(!empty($ea)) {
      $this->TemplateManager->Co->CoPetition->sendConfirmation($ptid, $ea, null);
      $this->TemplateManager->Co->CoPetition->updateStatus($ptid, PetitionStatusEnum::PendingConfirmation, null);
    }
    return TRUE;
  }

  private function mergeData($request, $field) {
    // field contains a single key=>value, but deep. Merge it exactly in the request,
    // but do not assume numeric keys are regular array indexes
    $key = current(array_keys($field));
    return $this->mergeKey($request, $key, $field[$key]);
  }
  
  private function mergeKey($lst, $key, $field) {
    if(!isset($lst[$key])) {
      $lst[$key]=$field;
    } else {
      if(is_array($field)) {
        $subkey = current(array_keys($field));
        $lst[$key] = $this->mergeKey($lst[$key],$subkey, $field[$subkey]);
      } else {
        $lst[$key]=array($lst[$key],$field);
      }
    }
    return $lst;
  }

  private function convertNameToData($field,$value) {
    // field is a value like EnrolleeOrgIdentity.Name.555.co_enrollment_attribute_id
    // We need to separate this based on dots, then construct an array of this
    $fields = explode('.',$field);
    $str = 'data[' . implode('][', $fields) . ']='.urlencode($value);

    $o=null;
    parse_str($str, $o);
    if(is_array($o)) {
      return $o;
    }
    return $o;
  }

  private function runScript($id) {
    if(isset($this->settings['run']) && strlen($this->settings['run'])) {
      $scriptName = basename($this->settings['run']);
      $root = Configure::read('templatemanager.script_root');
      if(!empty($scriptName) && !empty($root) && is_dir($root)) {
        $path = $root."/".$scriptName;
        if(file_exists($path) && is_executable($path)) {
          $o="";
          $v=0;
          exec("$path $id", $o, $v);

          if($v != 0) {
            throw new Exception("Error running $scriptName: ($v) ".json_encode($o));
          }
        } else {
          throw new Exception("Invalid script specification");
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  private function configureCO($newModel, $data) {
    $newModel['Co']['name']=$this->firstOf('name',$data,$this->settings,$this->model['Co']['name']." (Copy)");
    $newModel['Co']['description']=$this->firstOf('description',$data,$this->settings);
    $newModel['Co']['status']=TemplateableStatusEnum::Active;

    $this->TemplateManager->Co->save($newModel);
    //$this->TemplateManager->Co->saveAssociated($model);
    
    // copy the TemplateManager object of the original CO to the new CO,
    // but change the "actions" setting to only allow enroll
    $settings=$this->settings;
    $settings["actions"]=array("enroll");

    $template = $this->model['TemplateManager'];
    unset($template['id']);
    $template["settings"]=json_encode($settings);
    $template["parent_id"]=$template['co_id'];
    $template["co_id"]=$newModel["Co"]['id'];

    // change the key to the supplied key, or create one ourselves
    CakeLog::write('debug','api key in data is '.json_encode($data));
    $template['api_key'] = isset($data['api_key']) ? $data['api_key'] : $this->generateKey();

    CakeLog::write('debug','saving template '.json_encode($template));
    $this->TemplateManager->save($template);

    return $template["api_key"];
  }

  private function generateKey() {
    // https://stackoverflow.com/questions/6101956/generating-a-random-password-in-php/31284266#31284266
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < 32; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
  }

  private function devLog($msg)
  {
    //CakeLog::write('debug',$msg);
  }
}
