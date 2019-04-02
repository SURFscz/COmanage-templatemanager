<?php
    $model = $this->name;
    $req = Inflector::singularize($model);
    $submit_label = _txt('op.save');

    print $this->element("coCrumb");
    $args = array();
    $args['plugin'] = "template_manager";
    $args['controller'] = 'template_manager';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('pl.ct.templatemanager'), $args);


    print $this->Form->create($req, array('inputDefaults' => array('label' => false, 'div' => false)));
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script>
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    // If a validation template is selected, hide the subject and body fields
    var vtemplate = document.getElementById('TemplateManagerTemplateId').value;
    
    if(vtemplate) {
      $("#TemplateManagerSubject").closest("li").hide('fade');
      $("#TemplateManagerTemplateBody").closest("li").hide('fade');
    } else {
      $("#TemplateManagerSubject").closest("li").show('fade');
      $("#TemplateManagerTemplateBody").closest("li").show('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }

</script>

<ul id="<?php print $this->action; ?>_templatemanager_config" class="fields form-list form-list-admin">
 <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.api_key'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.api_key.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('api_key'); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.actions'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.actions.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->select('actions', $action_options, array(
          'multiple'=>'checkbox'
          )); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.script'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.script.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input("script"); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.name'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.name.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input("name"); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.description'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.description.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input("description"); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.enroll'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.enroll.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->select('enroll', $enroll_options, array(
          'multiple'=>'checkbox'
          )); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.from'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.from.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input("from"); ?>
    </div>
  </li>
  
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.template_id'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.template_id.descr'); ?></div>
    </div>
    <div class="field-info">
<?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (!empty($this->request->data['TemplateManager']['template_id'])
              ? $this->request->data['TemplateManager']['template_id']
              : null);
        $attrs['empty'] = true;
        $attrs['onchange'] = "fields_update_gadgets();";

        print $this->Form->select('template_id',$template_options, $attrs);

        if($this->Form->isFieldError('template_id')) {
          print $this->Form->error('template_id');
        }
?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.subject'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.subject.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('subject'); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.fd.templatemanager.template_body'); ?></div>
      <div class="field-desc"><?php print _txt('pl.fd.templatemanager.template_body.descr'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->textarea('template_body'); ?>
    </div>
  </li>

  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit($submit_label); ?>
    </div>
  </li>
</ul>
<?php
  print $this->Form->end();
?>
