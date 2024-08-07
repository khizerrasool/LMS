<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

class waitlist_fields_profile_define_base {

    /**
     * Prints out the form snippet for creating or editing a profile field
     * @param   object   instance of the moodleform class
     */
    function define_form(&$form) {
        $form->addElement('header', '_commonsettings', get_string('profilecommonsettings', 'admin'));
        $this->define_form_common($form);

        // $form->addElement('header', '_categorysettings', get_string('profilecategorysettings', 'local_course_fields'));
        // $this->define_form_category($form);

        // $form->addElement('header', '_specificsettings', get_string('profilespecificsettings', 'admin'));
        // $this->define_form_specific($form);
    }

    /**
     * Prints out the form snippet for the part of creating or
     * editing a profile field common to all data types
     * @param   object   instance of the moodleform class
     */
    function define_form_common(&$form) {

        $strrequired = get_string('required');

        $form->addElement('text', 'shortname', get_string('profileshortname', 'admin'), 'maxlength="100" size="25"');
        $form->addRule('shortname', $strrequired, 'required', null, 'client');
        $form->setType('shortname', PARAM_ALPHANUM);

        $form->addElement('text', 'name', get_string('profilename', 'admin'), 'size="50"');
        $form->addRule('name', $strrequired, 'required', null, 'client');
        $form->setType('name', PARAM_MULTILANG);

        // $editorOptions = array('trusttext'=>true, 'subdirs'=>true, 'maxfiles'=>5, 'maxbytes'=>1024*1024);
        $editorOptions = array();
        $form->addElement('editor', 'description', get_string('profiledescription', 'admin'), null, $editorOptions);
        $form->addElement('editor', 'param2', get_string('profiledescription2', 'enrol_waitlist'), null, $editorOptions);

        $form->addElement('selectyesno', 'required', get_string('profilerequired', 'admin'));

        $form->addElement('selectyesno', 'forceunique', get_string('profileforceunique', 'admin'));

        // $form->addElement('selectyesno', 'allcategory', get_string('profileallcategory', 'local_course_fields'));

        $choices = array();
        /*
        $choices[PROFILE_VISIBLE_NONE]    = get_string('profilevisiblenone', 'admin');
        $choices[PROFILE_VISIBLE_PRIVATE] = get_string('profilevisibleprivate', 'admin');
        $choices[PROFILE_VISIBLE_ALL]     = get_string('profilevisibleall', 'admin');
        */
        $choices[0] = get_string('profilevisiblenone', 'admin');
        $choices[1] = get_string('profilevisibleprivate', 'admin');
        $choices[2] = get_string('profilevisibleall', 'admin');
        // $form->addHelpButton('visible', 'profilevisible', 'admin');
        // $form->setDefault('visible', PROFILE_VISIBLE_ALL);
        $form->setDefault('visible', 2);
        /*
        $choices = profile_list_categories();
        $form->addElement('select', 'categoryid', get_string('profilecategory', 'admin'), $choices);
        */
    }

    function define_form_category(&$form) {
        $choices = profile_list_categories();
        foreach($choices as $cid => $choice){
            $form->addElement('advcheckbox','categoryid[]',$choice,null,null,array(0,$cid));
        }
    }

    /**
     * Prints out the form snippet for the part of creating or
     * editing a profile field specific to the current data type
     * @param   object   instance of the moodleform class
     */
    function define_form_specific($form) {
        /// do nothing - overwrite if necessary
    }

    /**
     * Validate the data from the add/edit profile field form.
     * Generally this method should not be overwritten by child
     * classes.
     * @param   object   data from the add/edit profile field form
     * @return  array    associative array of error messages
     */
    function define_validate($data, $files) {

        $data = (object)$data;
        $err = array();

        $err += $this->define_validate_common($data, $files);
        $err += $this->define_validate_specific($data, $files);

        return $err;
    }

    /**
     * Validate the data from the add/edit profile field form
     * that is common to all data types. Generally this method
     * should not be overwritten by child classes.
     * @param   object   data from the add/edit profile field form
     * @return  array    associative array of error messages
     */
    function define_validate_common($data, $files) {
        global $USER, $DB;

        $err = array();

        /// Check the shortname was not truncated by cleaning
        if (empty($data->shortname)) {
            $err['shortname'] = get_string('required');

        } else {
            /// Fetch field-record from DB
            $field = $DB->get_record('waitlist_info_field', array('shortname' => $data->shortname));
            /// Check the shortname is unique
            if ($field and $field->id <> $data->id) {
                $err['shortname'] = get_string('profileshortnamenotunique', 'admin');
            }

            // NOTE: since 2.0 the shortname may collide with existing fields in $USER because we load these fields into $USER->profile array instead
        }

        /// No further checks necessary as the form class will take care of it
        return $err;
    }

    /**
     * Validate the data from the add/edit profile field form
     * that is specific to the current data type
     * @param   object   data from the add/edit profile field form
     * @param   array    files
     * @return  array    associative array of error messages
     */
    function define_validate_specific($data, $files) {
        /// do nothing - overwrite if necessary
        return array();
    }

    /**
     * Alter form based on submitted or existing data
     * @param   object   form
     */
    function define_after_data(&$mform) {
        /// do nothing - overwrite if necessary
    }

    /**
     * Add a new profile field or save changes to current field
     * @param   object   data from the add/edit profile field form
     * @return  boolean  status of the insert/update record
     */
    function define_save($data) {
        global $DB;

        $data = $this->define_save_preprocess($data); /// hook for child classes

        $old = false;
        if (!empty($data->id)) {
            $old = $DB->get_record('waitlist_info_field', array('id' => (int)$data->id));
        }

        /// check to see if the category has changed
        /*
        if (!$old or $old->categoryid != $data->categoryid) {
            $data->sortorder = $DB->count_records('course_info_field', array('categoryid'=>$data->categoryid)) + 1;
        }
        */

        if (empty($data->id)) {
            unset($data->id);
            $fieldcount = $DB->count_records('waitlist_info_field',null);
            $data->sortorder = ($fieldcount + 1);
            $data->id = $DB->insert_record('waitlist_info_field', $data);
        } else {
            $DB->update_record('waitlist_info_field', $data);
        }
    }

    /**
     * Preprocess data from the add/edit profile field form
     * before it is saved. This method is a hook for the child
     * classes to overwrite.
     * @param   object   data from the add/edit profile field form
     * @return  object   processed data object
     */
    function define_save_preprocess($data) {
        /// do nothing - overwrite if necessary
        return $data;
    }

    /**
     * Provides a method by which we can allow the default data in profile_define_*
     * to use an editor
     *
     * This should return an array of editor names (which will need to be formatted/cleaned)
     *
     * @return array
     */
    function define_editors() {
        return array();
    }
}



/**
 * Reorder the profile fields within a given category starting
 * at the field at the given startorder
 */
function profile_reorder_fields() {
    global $DB;

    $i = 1;
    if ($fields = $DB->get_records('waitlist_info_field', null, 'sortorder ASC')) {
        foreach ($fields as $field) {
            $f = new stdClass();
            $f->id = $field->id;
            $f->sortorder = $i++;
            $DB->update_record('waitlist_info_field', $f);
        }
    }

}

/**
 * Reorder the profile categoriess starting at the category
 * at the given startorder
 */
function profile_reorder_categories() {
    global $DB;

    $i = 1;
    if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $cat) {
            $c = new stdClass();
            $c->id = $cat->id;
            $c->sortorder = $i++;
            $DB->update_record('user_info_category', $c);
        }
    }
}

/**
 * Delete a profile category
 * @param   integer   id of the category to be deleted
 * @return  boolean   success of operation
 */
function profile_delete_category($id) {
    global $DB;

    /// Retrieve the category
    if (!$category = $DB->get_record('course_info_category', array('id' => $id))) {
        print_error('invalidcategoryid');
    }

    if (!$categories = $DB->get_records('course_info_category', null, 'sortorder ASC')) {
        print_error('nocate', 'debug');
    }

    unset($categories[$category->id]);
    /*
    if (!count($categories)) {
        return; //we can not delete the last category
    }
    */
    /// Does the category contain any fields
    if ($DB->count_records('waitlist_info_field', array('categoryid' => $category->id))) {
        if (array_key_exists($category->sortorder - 1, $categories)) {
            $newcategory = $categories[$category->sortorder - 1];
        } else if (array_key_exists($category->sortorder + 1, $categories)) {
            $newcategory = $categories[$category->sortorder + 1];
        } else {
            $newcategory = reset($categories); // get first category if sortorder broken
        }

        $sortorder = $DB->count_records('course_info_field', array('categoryid' => $newcategory->id)) + 1;

        if ($fields = $DB->get_records('course_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
            foreach ($fields as $field) {
                $f = new stdClass();
                $f->id = $field->id;
                $f->sortorder = $sortorder++;
                $f->categoryid = $newcategory->id;
                $DB->update_record('course_info_field', $f);
                // echo "<pre>";var_dump($f);echo"</pre>";
            }
        }
    }

    /// Finally we get to delete the category
    $DB->delete_records('course_info_categories', array('categoryid' => $category->id));
    $DB->delete_records('course_info_category', array('id' => $category->id));
    // profile_reorder_categories();
    return true;
}


function profile_delete_field($id) {
    global $DB;

    /// Remove any user data associated with this field
    if (!$DB->delete_records('waitlist_info_data', array('fieldid' => $id))) {
        print_error('cannotdeletecustomfield');
    }

    /// Try to remove the record from the database
    $res = $DB->delete_records('waitlist_info_field', array('id' => $id));

    /// Reorder the remaining fields in the same category
    profile_reorder_fields();
}

/**
 * Change the sortorder of a field
 * @param   integer   id of the field
 * @param   string    direction of move
 * @return  boolean   success of operation
 */
function profile_move_field($id, $move) {
    global $DB;

    /// Get the field object
    if (!$field = $DB->get_record('waitlist_info_field', array('id' => $id), 'id, sortorder')) {
        return false;
    }
    /// Count the number of fields in this category
    $fieldcount = $DB->count_records('waitlist_info_field',null);

    /// Calculate the new sortorder
    if ( ($move == 'up') and ($field->sortorder > 1)) {
        $neworder = $field->sortorder - 1;
    } else if ( ($move == 'down') and ($field->sortorder < $fieldcount)) {
        $neworder = $field->sortorder + 1;
    } else {
        return false;
    }

    /// Retrieve the field object that is currently residing in the new position
    if ($swapfield = $DB->get_record('waitlist_info_field', array('sortorder' => $neworder), 'id, sortorder')) {

        /// Swap the sortorders
        $swapfield->sortorder = $field->sortorder;
        $field->sortorder     = $neworder;

        /// Update the field records
        $DB->update_record('waitlist_info_field', $field);
        $DB->update_record('waitlist_info_field', $swapfield);
    }

    profile_reorder_fields();
}

/**
 * Change the sortorder of a category
 * @param   integer   id of the category
 * @param   string    direction of move
 * @return  boolean   success of operation
 */
function profile_move_category($id, $move) {
    global $DB;
    /// Get the category object
    if (!($category = $DB->get_record('user_info_category', array('id' => $id), 'id, sortorder'))) {
        return false;
    }

    /// Count the number of categories
    $categorycount = $DB->count_records('user_info_category');

    /// Calculate the new sortorder
    if ( ($move == 'up') and ($category->sortorder > 1)) {
        $neworder = $category->sortorder - 1;
    } else if ( ($move == 'down') and ($category->sortorder < $categorycount)) {
        $neworder = $category->sortorder + 1;
    } else {
        return false;
    }

    /// Retrieve the category object that is currently residing in the new position
    if ($swapcategory = $DB->get_record('user_info_category', array('sortorder' => $neworder),'id, sortorder')) {

        /// Swap the sortorders
        $swapcategory->sortorder = $category->sortorder;
        $category->sortorder     = $neworder;

        /// Update the category records
        $DB->update_record('user_info_category', $category) and $DB->update_record('user_info_category', $swapcategory);
        return true;
    }

    return false;
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function profile_list_datatypes() {
    global $CFG;

    $datatypes = array();

    $plugins = get_plugin_list('profilefield');
    foreach ($plugins as $type => $unused) {
        if($type == 'checkbox'){
               $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
        }

        if($type == 'text'){
            $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
        }

        if($type == 'textarea'){
            $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
        }
    }
    asort($datatypes);

    return $datatypes;
}

/**
 * Retrieve a list of categories and ids suitable for use in a form
 * @return   array
 */
function profile_list_categories() {
    global $DB;
    if (!$categories = $DB->get_records_menu('course_info_category', null, 'sortorder ASC', 'id, name')) {
        $categories = array();
    }
    return $categories;
}

/*
function profile_list_categories() {
    global $DB;
    if (!$categories = $DB->get_records_menu('course_categories', NULL, 'sortorder ASC', 'id, name')) {
        $categories = array();
    }
    return $categories;
}
*/

/// Are we adding or editing a cateogory?

function profile_edit_category1($id, $redirect) {
    global $CFG, $DB, $OUTPUT;

    require_once('index_category_form.php');
    $categoryform = new category_form();

    if ($category = $DB->get_record('course_info_category', array('id' => $id))) {
        $categoryform->set_data($category);
    }

    $usedCategoryIds = array();
    $courseCategorys = $DB->get_records('course_info_categories', array('categoryid' => $id));
    foreach($courseCategorys as $courseCategory){
        $usedCategoryIds[] = $courseCategory->course_category;
    }

    if ($categoryform->is_cancelled()) {
        redirect($redirect);
    } else {
        if ($data = $categoryform->get_data()) {
            $categoryIds = array();
            $postdata = data_submitted();
            $postdata = (array)$postdata;
            foreach($postdata['categoryid'] as $categoryid){
                if($categoryid > 0){
                    $categoryIds[] = $categoryid;
                }
            }

            if (empty($data->id)) {
                unset($data->id);
                $data->sortorder = $DB->count_records('course_info_category') + 1;
                $cid = $DB->insert_record('course_info_category', $data);
                if($cid){
                    foreach($categoryIds as $categoryid){
                        $cdata = new stdClass();
                        $cdata->categoryid = $cid;
                        $cdata->course_category = $categoryid;
                        $DB->insert_record('course_info_categories', $cdata);
                    }
                }
            } else {
                $DB->delete_records('course_info_categories', array('categoryid' => $id));
                foreach($categoryIds as $categoryid){
                    $cdata = new stdClass();
                             $cdata->categoryid = $data->id;
                             $cdata->course_category = $categoryid;
                             $DB->insert_record('course_info_categories', $cdata);
                }
                $DB->update_record('course_info_category', $data);
            }
            profile_reorder_categories();
            redirect($redirect);

        }

        if (empty($id)) {
            $strheading = get_string('profilecreatenewcategory', 'admin');
        } else {
            $strheading = get_string('profileeditcategory', 'admin', format_string($category->name));
        }
        array_push($usedCategoryIds,0);
        if($id){
               $checkboxjs = "
			<script>
			var selectedCategory = new Array(".implode(",",$usedCategoryIds).");
			var domcheckbox = document.getElementsByTagName('INPUT');
			for (var i=0;i<domcheckbox.length;i++ ){
				if(domcheckbox[i].type == 'checkbox'){
					for(var j=0;j<selectedCategory.length;j++){
					if(domcheckbox[i].value == selectedCategory[j]){
							domcheckbox[i].checked = true;
						}
					}
				}
			}
			</script>";
        }

        /// Print the page
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $categoryform->display();
        if($id){
               echo $checkboxjs;
        }
        echo $OUTPUT->footer();
        die;
    }

}

function profile_edit_field1($id, $datatype, $redirect) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    if (!$field = $DB->get_record('waitlist_info_field', array('id' => $id))) {
        $field = new stdClass();
        $field->datatype = $datatype;
        $field->description = '';
        $field->descriptionformat = FORMAT_HTML;
        $field->defaultdata = '';
        $field->defaultdataformat = FORMAT_HTML;

        $field->param2 = '';
    }
    /*
    if($id){
    $categoryIds = array();
    $courseCategorys = $DB->get_records('course_info_category', array('field_id'=>$id));
    foreach($courseCategorys as $courseCategory){
    $categoryIds[] = $courseCategory->category_id;
    }
    }
    */
    // Clean and prepare description for the editor
    $field->description = clean_text($field->description, $field->descriptionformat);
    $field->description = array('text' => $field->description, 'format' => $field->descriptionformat, 'itemid' => 0);

    $field->param2 = clean_text($field->param2, $field->descriptionformat);
    $field->param2 = array('text' => $field->param2, 'format' => $field->descriptionformat, 'itemid' => 0);

    require_once('index_field_form.php');
    $fieldform = new field_form(null, $field->datatype);

    // Convert the data format for
    if (is_array($fieldform->editors())) {
        foreach ($fieldform->editors() as $editor) {
            if (isset($field->$editor)) {
                $field->$editor = clean_text($field->$editor, $field->{$editor.'format'});
                $field->$editor = array('text' => $field->$editor, 'format' => $field->{$editor.'format'}, 'itemid' => 0);
            }
        }
    }

    $fieldform->set_data($field);

    if ($fieldform->is_cancelled()) {
        redirect($redirect);

    } else {
        if ($data = $fieldform->get_data()) {
            require_once($CFG->dirroot.'/enrol/waitlist/profile/field/'.$datatype.'/define.class.php');
            $newfield = 'waitlist_fields_profile_define_'.$datatype;
            $formfield = new $newfield();

            // Collect the description and format back into the proper data structure from the editor
            // Note: This field will ALWAYS be an editor
            $data->descriptionformat = $data->description['format'];
            $data->description = $data->description['text'];

            $data->param2 = $data->param2['text'];
            
            // Check whether the default data is an editor, this is (currently) only the
            // textarea field type
            /*
            if (is_array($data->defaultdata) && array_key_exists('text', $data->defaultdata)) {
                // Collect the default data and format back into the proper data structure from the editor
                $data->defaultdataformat = $data->defaultdata['format'];
                $data->defaultdata = $data->defaultdata['text'];
            }
            */

            // Convert the data format for.
            if (is_array($fieldform->editors())) {
                foreach ($fieldform->editors() as $editor) {
                    if (isset($field->$editor)) {
                        $field->{$editor.'format'} = $field->{$editor}['format'];
                        $field->$editor = $field->{$editor}['text'];
                    }
                }
            }
           
            $formfield->define_save($data);
            redirect($redirect);
        }

        $datatypes = profile_list_datatypes();

        if (empty($id)) {
            $strheading = get_string('profilecreatenewfield', 'admin', $datatypes[$datatype]);
        } else {
            $strheading = get_string('profileeditfield', 'admin', $field->name);
        }
        /*
        if($id){
        $checkboxjs = "
        <script>
        var selectedCategory = new Array(".implode(",",$categoryIds).");
        var domcheckbox = document.getElementsByTagName('INPUT');
        for (var i=0;i<domcheckbox.length;i++ ){
        if(domcheckbox[i].type == 'checkbox'){
        for(var j=0;j<selectedCategory.length;j++){
        if(domcheckbox[i].value == selectedCategory[j]){
        domcheckbox[i].checked = true;
        }
        }
        }
        }
        </script>";
        }
        */
        /// Print the page
        $PAGE->navbar->add($strheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $fieldform->display();
        // if($id){
        // echo $checkboxjs;
        // }
        echo $OUTPUT->footer();
        die;
    }
}



