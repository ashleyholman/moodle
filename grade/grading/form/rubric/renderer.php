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

/**
 * @package    gradingform
 * @subpackage rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Grading method plugin renderer
 */
class gradingform_rubric_renderer extends plugin_renderer_base {

    /**
     * This function returns html code for displaying criterion. Depending on $mode it may be the
     * code to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_rubric() to display the whole rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty criteria to the
     * rubric being designed.
     * In this case it will use macros like {NAME}, {LEVELS}, {CRITERION-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode @see gradingform_rubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array|null $criterion criterion data
     * @param string $levelsstr evaluated templates for this criterion levels
     * @param array|null $value (only in view mode) teacher's feedback on this criterion
     * @return string
     */
    public function criterion_template($mode, $options, $elementname = '{NAME}', $criterion = null, $levelsstr = '{LEVELS}', $value = null) {
        // TODO description format, remark format
        if ($criterion === null || !is_array($criterion) || !array_key_exists('id', $criterion)) {
            $criterion = array('id' => '{CRITERION-id}', 'description' => '{CRITERION-description}', 'sortorder' => '{CRITERION-sortorder}', 'class' => '{CRITERION-class}');
        } else {
            foreach (array('sortorder', 'description', 'class') as $key) {
                // set missing array elements to empty strings to avoid warnings
                if (!array_key_exists($key, $criterion)) {
                    $criterion[$key] = '';
                }
            }
        }
        $criteriontemplate = html_writer::start_tag('tr', array('class' => 'criterion'. $criterion['class'], 'id' => '{NAME}-criteria-{CRITERION-id}'));
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $criteriontemplate .= html_writer::start_tag('td', array('class' => 'controls'));
            foreach (array('moveup', 'delete', 'movedown') as $key) {
                $value = get_string('criterion'.$key, 'gradingform_rubric');
                $button = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}]['.$key.']',
                    'id' => '{NAME}-criteria-{CRITERION-id}-'.$key, 'value' => $value, 'title' => $value, 'tabindex' => -1));
                $criteriontemplate .= html_writer::tag('div', $button, array('class' => $key));
            }
            $criteriontemplate .= html_writer::end_tag('td'); // .controls
            $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][sortorder]', 'value' => $criterion['sortorder']));
            $description = html_writer::tag('textarea', htmlspecialchars($criterion['description']), array('name' => '{NAME}[criteria][{CRITERION-id}][description]', 'cols' => '10', 'rows' => '5'));
        } else {
            if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {
                $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][sortorder]', 'value' => $criterion['sortorder']));
                $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][description]', 'value' => $criterion['description']));
            }
            $description = $criterion['description'];
        }
        $descriptionclass = 'description';
        if (isset($criterion['error_description'])) {
            $descriptionclass .= ' error';
        }
        $criteriontemplate .= html_writer::tag('td', $description, array('class' => $descriptionclass, 'id' => '{NAME}-criteria-{CRITERION-id}-description'));
        $levelsstrtable = html_writer::tag('table', html_writer::tag('tr', $levelsstr, array('id' => '{NAME}-criteria-{CRITERION-id}-levels')));
        $levelsclass = 'levels';
        if (isset($criterion['error_levels'])) {
            $levelsclass .= ' error';
        }
        $criteriontemplate .= html_writer::tag('td', $levelsstrtable, array('class' => $levelsclass));
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('criterionaddlevel', 'gradingform_rubric');
            $button = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][addlevel]',
                'id' => '{NAME}-criteria-{CRITERION-id}-levels-addlevel', 'value' => $value, 'title' => $value));
            $criteriontemplate .= html_writer::tag('td', $button, array('class' => 'addlevel'));
        }
        $displayremark = ($options['enableremarks'] && ($mode != gradingform_rubric_controller::DISPLAY_VIEW || $options['showremarksstudent']));
        if ($displayremark) {
            $currentremark = '';
            if (isset($value['remark'])) {
                $currentremark = $value['remark'];
            }
            if ($mode == gradingform_rubric_controller::DISPLAY_EVAL) {
                $input = html_writer::tag('textarea', htmlspecialchars($currentremark), array('name' => '{NAME}[criteria][{CRITERION-id}][remark]', 'cols' => '10', 'rows' => '5'));
                $criteriontemplate .= html_writer::tag('td', $input, array('class' => 'remark'));
            } else if ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN) {
                $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][remark]', 'value' => $currentremark));
            }else if ($mode == gradingform_rubric_controller::DISPLAY_REVIEW || $mode == gradingform_rubric_controller::DISPLAY_VIEW) {
                $criteriontemplate .= html_writer::tag('td', $currentremark, array('class' => 'remark')); // TODO maybe some prefix here like 'Teacher remark:'
            }
        }
        $criteriontemplate .= html_writer::end_tag('tr'); // .criterion

        $criteriontemplate = str_replace('{NAME}', $elementname, $criteriontemplate);
        $criteriontemplate = str_replace('{CRITERION-id}', $criterion['id'], $criteriontemplate);
        return $criteriontemplate;
    }

    /**
     * This function returns html code for displaying one level of one criterion. Depending on $mode
     * it may be the code to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_rubric() to display the whole rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty level to the
     * criterion during the design of rubric.
     * In this case it will use macros like {NAME}, {CRITERION-id}, {LEVEL-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode @see gradingform_rubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string|int $criterionid either id of the nesting criterion or a macro for template
     * @param array|null $level level data, also in view mode it might also have property $level['checked'] whether this level is checked
     * @return string
     */
    public function level_template($mode, $options, $elementname = '{NAME}', $criterionid = '{CRITERION-id}', $level = null) {
        // TODO definition format
        if (!isset($level['id'])) {
            $level = array('id' => '{LEVEL-id}', 'definition' => '{LEVEL-definition}', 'score' => '{LEVEL-score}', 'class' => '{LEVEL-class}', 'checked' => false);
        } else {
            foreach (array('score', 'definition', 'class', 'checked') as $key) {
                // set missing array elements to empty strings to avoid warnings
                if (!array_key_exists($key, $level)) {
                    $level[$key] = '';
                }
            }
        }

        // Template for one level within one criterion
        $tdattributes = array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}', 'class' => 'level'. $level['class']);
        if (isset($level['tdwidth'])) {
            $tdattributes['width'] = round($level['tdwidth']).'%';
        }
        $leveltemplate = html_writer::start_tag('td', $tdattributes);
        $leveltemplate .= html_writer::start_tag('div', array('class' => 'level-wrapper'));
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $definition = html_writer::tag('textarea', htmlspecialchars($level['definition']), array('name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]', 'cols' => '10', 'rows' => '4'));
            $score = html_writer::empty_tag('input', array('type' => 'text', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]', 'size' => '3', 'value' => $level['score']));
        } else {
            if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {
                $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]', 'value' => $level['definition']));
                $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]', 'value' => $level['score']));
            }
            $definition = $level['definition'];
            $score = $level['score'];
        }
        if ($mode == gradingform_rubric_controller::DISPLAY_EVAL) {
            $input = html_writer::empty_tag('input', array('type' => 'radio', 'name' => '{NAME}[criteria][{CRITERION-id}][levelid]', 'value' => $level['id']) +
                    ($level['checked'] ? array('checked' => 'checked') : array()));
            $leveltemplate .= html_writer::tag('div', $input, array('class' => 'radio'));
        }
        if ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN && $level['checked']) {
            $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levelid]', 'value' => $level['id']));
        }
        $score = html_writer::tag('span', $score, array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-score', 'class' => 'scorevalue'));
        $definitionclass = 'definition';
        if (isset($level['error_definition'])) {
            $definitionclass .= ' error';
        }
        $leveltemplate .= html_writer::tag('div', $definition, array('class' => $definitionclass, 'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-definition'));
        $displayscore = true;
        if (!$options['showscoreteacher'] && in_array($mode, array(gradingform_rubric_controller::DISPLAY_EVAL, gradingform_rubric_controller::DISPLAY_EVAL_FROZEN, gradingform_rubric_controller::DISPLAY_REVIEW))) {
            $displayscore = false;
        }
        if (!$options['showscorestudent'] && in_array($mode, array(gradingform_rubric_controller::DISPLAY_VIEW, gradingform_rubric_controller::DISPLAY_PREVIEW_GRADED))) {
            $displayscore = false;
        }
        if ($displayscore) {
            $scoreclass = 'score';
            if (isset($level['error_score'])) {
                $scoreclass .= ' error';
            }
            $leveltemplate .= html_writer::tag('div', get_string('scorepostfix', 'gradingform_rubric', $score), array('class' => $scoreclass));
        }
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('leveldelete', 'gradingform_rubric');
            $button = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][delete]', 'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-delete', 'value' => $value, 'title' => $value, 'tabindex' => -1));
            $leveltemplate .= html_writer::tag('div', $button, array('class' => 'delete'));
        }
        $leveltemplate .= html_writer::end_tag('div'); // .level-wrapper
        $leveltemplate .= html_writer::end_tag('td'); // .level

        $leveltemplate = str_replace('{NAME}', $elementname, $leveltemplate);
        $leveltemplate = str_replace('{CRITERION-id}', $criterionid, $leveltemplate);
        $leveltemplate = str_replace('{LEVEL-id}', $level['id'], $leveltemplate);
        return $leveltemplate;
    }

    /**
     * This function returns html code for displaying rubric template (content before and after
     * criteria list). Depending on $mode it may be the code to edit rubric, to preview the rubric,
     * to evaluate somebody or to review the evaluation.
     *
     * This function is called from display_rubric() to display the whole rubric.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode @see gradingform_rubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string $criteriastr evaluated templates for this rubric's criteria
     * @return string
     */
    protected function rubric_template($mode, $options, $elementname, $criteriastr) {
        $classsuffix = ''; // CSS suffix for class of the main div. Depends on the mode
        switch ($mode) {
            case gradingform_rubric_controller::DISPLAY_EDIT_FULL:
                $classsuffix = ' editor editable'; break;
            case gradingform_rubric_controller::DISPLAY_EDIT_FROZEN:
                $classsuffix = ' editor frozen';  break;
            case gradingform_rubric_controller::DISPLAY_PREVIEW:
            case gradingform_rubric_controller::DISPLAY_PREVIEW_GRADED:
                $classsuffix = ' editor preview';  break;
            case gradingform_rubric_controller::DISPLAY_EVAL:
                $classsuffix = ' evaluate editable'; break;
            case gradingform_rubric_controller::DISPLAY_EVAL_FROZEN:
                $classsuffix = ' evaluate frozen';  break;
            case gradingform_rubric_controller::DISPLAY_REVIEW:
                $classsuffix = ' review';  break;
            case gradingform_rubric_controller::DISPLAY_VIEW:
                $classsuffix = ' view';  break;
        }

        $rubrictemplate = html_writer::start_tag('div', array('id' => 'rubric-{NAME}', 'class' => 'clearfix gradingform_rubric'.$classsuffix));
        $rubrictemplate .= html_writer::tag('table', $criteriastr, array('class' => 'criteria', 'id' => '{NAME}-criteria'));
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('addcriterion', 'gradingform_rubric');
            $input = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][addcriterion]', 'id' => '{NAME}-criteria-addcriterion', 'value' => $value, 'title' => $value));
            $rubrictemplate .= html_writer::tag('div', $input, array('class' => 'addcriterion'));
        }
        $rubrictemplate .= $this->rubric_edit_options($mode, $options);
        $rubrictemplate .= html_writer::end_tag('div');

        return str_replace('{NAME}', $elementname, $rubrictemplate);
    }

    /**
     * Generates html template to view/edit the rubric options. Expression {NAME} is used in
     * template for the form element name
     *
     * @param int $mode
     * @param array $options
     * @return string
     */
    protected function rubric_edit_options($mode, $options) {
        if ($mode != gradingform_rubric_controller::DISPLAY_EDIT_FULL
                && $mode != gradingform_rubric_controller::DISPLAY_EDIT_FROZEN
                && $mode != gradingform_rubric_controller::DISPLAY_PREVIEW) {
            // Options are displayed only for people who can manage
            return;
        }
        $html = html_writer::start_tag('div', array('class' => 'options'));
        $html .= html_writer::tag('div', get_string('rubricoptions', 'gradingform_rubric'), array('class' => 'optionsheading'));
        $attrs = array('type' => 'hidden', 'name' => '{NAME}[options][optionsset]', 'value' => 1);
        foreach ($options as $option => $value) {
            $html .= html_writer::start_tag('div', array('class' => 'option '.$option));
            $attrs = array('name' => '{NAME}[options]['.$option.']', 'id' => '{NAME}-options-'.$option);
            switch ($option) {
                case 'sortlevelsasc':
                    // Display option as dropdown
                    $html .= html_writer::tag('span', get_string($option, 'gradingform_rubric'), array('class' => 'label'));
                    $value = (int)(!!$value); // make sure $value is either 0 or 1
                    if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
                        $selectoptions = array(0 => get_string($option.'0', 'gradingform_rubric'), 1 => get_string($option.'1', 'gradingform_rubric'));
                        $valuestr = html_writer::select($selectoptions, $attrs['name'], $value, false, array('id' => $attrs['id']));
                        $html .= html_writer::tag('span', $valuestr, array('class' => 'value'));
                        // TODO add here button 'Sort levels'
                    } else {
                        $html .= html_writer::tag('span', get_string($option.$value, 'gradingform_rubric'), array('class' => 'value'));
                        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {
                            $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
                        }
                    }
                    break;
                default:
                    if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN && $value) {
                        $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
                    }
                    // Display option as checkbox
                    $attrs['type'] = 'checkbox';
                    $attrs['value'] = 1;
                    if ($value) {
                        $attrs['checked'] = 'checked';
                    }
                    if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN || $mode == gradingform_rubric_controller::DISPLAY_PREVIEW) {
                        $attrs['disabled'] = 'disabled';
                        unset($attrs['name']);
                    }
                    $html .= html_writer::empty_tag('input', $attrs);
                    $html .= html_writer::tag('label', get_string($option, 'gradingform_rubric'), array('for' => $attrs['id']));
                    break;
            }
            $html .= html_writer::end_tag('div'); // .option
        }
        $html .= html_writer::end_tag('div'); // .options
        return $html;
    }

    /**
     * This function returns html code for displaying rubric. Depending on $mode it may be the code
     * to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * It is very unlikely that this function needs to be overriden by theme. It does not produce
     * any html code, it just prepares data about rubric design and evaluation, adds the CSS
     * class to elements and calls the functions level_template, criterion_template and
     * rubric_template
     *
     * @param array $criteria data about the rubric design
     * @param int $mode rubric display mode @see gradingform_rubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array $values evaluation result
     * @return string
     */
    public function display_rubric($criteria, $options, $mode, $elementname = null, $values = null) {
        $criteriastr = '';
        $cnt = 0;
        foreach ($criteria as $id => $criterion) {
            $criterion['class'] = $this->get_css_class_suffix($cnt++, sizeof($criteria) -1);
            $criterion['id'] = $id;
            $levelsstr = '';
            $levelcnt = 0;
            if (isset($values['criteria'][$id])) {
                $criterionvalue = $values['criteria'][$id];
            } else {
                $criterionvalue = null;
            }
            foreach ($criterion['levels'] as $levelid => $level) {
                $level['id'] = $levelid;
                $level['class'] = $this->get_css_class_suffix($levelcnt++, sizeof($criterion['levels']) -1);
                $level['checked'] = (isset($criterionvalue['levelid']) && ((int)$criterionvalue['levelid'] === $levelid));
                if ($level['checked'] && ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN || $mode == gradingform_rubric_controller::DISPLAY_REVIEW || $mode == gradingform_rubric_controller::DISPLAY_VIEW)) {
                    $level['class'] .= ' checked';
                    //in mode DISPLAY_EVAL the class 'checked' will be added by JS if it is enabled. If JS is not enabled, the 'checked' class will only confuse
                }
                if (isset($criterionvalue['savedlevelid']) && ((int)$criterionvalue['savedlevelid'] === $levelid)) {
                    $level['class'] .= ' currentchecked';
                }
                $level['tdwidth'] = 100/count($criterion['levels']);
                $levelsstr .= $this->level_template($mode, $options, $elementname, $id, $level);
            }
            $criteriastr .= $this->criterion_template($mode, $options, $elementname, $criterion, $levelsstr, $criterionvalue);
        }
        return $this->rubric_template($mode, $options, $elementname, $criteriastr);
    }

    /**
     * Help function to return CSS class names for element (first/last/even/odd) with leading space
     *
     * @param int $idx index of this element in the row/column
     * @param int $maxidx maximum index of the element in the row/column
     * @return string
     */
    protected function get_css_class_suffix($idx, $maxidx) {
        $class = '';
        if ($idx == 0) {
            $class .= ' first';
        }
        if ($idx == $maxidx) {
            $class .= ' last';
        }
        if ($idx%2) {
            $class .= ' odd';
        } else {
            $class .= ' even';
        }
        return $class;
    }

    /**
     * Displays for the student the list of instances or default content if no instances found
     *
     * @param array $instances array of objects of type gradingform_rubric_instance
     * @param string $defaultcontent default string that would be displayed without advanced grading
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function display_instances($instances, $defaultcontent, $cangrade) {
        $return = '';
        if (sizeof($instances)) {
            $return .= html_writer::start_tag('div', array('class' => 'advancedgrade'));
            $idx = 0;
            foreach ($instances as $instance) {
                $return .= $this->display_instance($instance, $idx++, $cangrade);
            }
            $return .= html_writer::end_tag('div');
        }
        return $return. $defaultcontent;
    }

    /**
     * Displays one grading instance
     *
     * @param gradingform_rubric_instance $instance
     * @param int idx unique number of instance on page
     * @param boolean $cangrade whether current user has capability to grade in this context
     */
    public function display_instance(gradingform_rubric_instance $instance, $idx, $cangrade) {
        $criteria = $instance->get_controller()->get_definition()->rubric_criteria;
        $options = $instance->get_controller()->get_options();
        $values = $instance->get_rubric_filling();
        if ($cangrade) {
            $mode = gradingform_rubric_controller::DISPLAY_REVIEW;
            $showdescription = $options['showdescriptionteacher'];
        } else {
            $mode = gradingform_rubric_controller::DISPLAY_VIEW;
            $showdescription = $options['showdescriptionstudent'];
        }
        $output = '';
        if ($showdescription) {
            $output .= $this->box($instance->get_controller()->get_formatted_description(), 'gradingform_rubric-description');
        }
        $output .= $this->display_rubric($criteria, $options, $mode, 'rubric'.$idx, $values);
        return $output;
    }

    public function display_regrade_confirmation($elementname, $changelevel, $value) {
        $html = html_writer::start_tag('div', array('class' => 'gradingform_rubric-regrade'));
        if ($changelevel<=2) {
            $html .= get_string('regrademessage1', 'gradingform_rubric');
            $selectoptions = array(
                0 => get_string('regradeoption0', 'gradingform_rubric'),
                1 => get_string('regradeoption1', 'gradingform_rubric')
            );
            $html .= html_writer::select($selectoptions, $elementname.'[regrade]', $value, false);
        } else {
            $html .= get_string('regrademessage5', 'gradingform_rubric');
            $html .= html_writer::empty_tag('input', array('name' => $elementname.'[regrade]', 'value' => 1, 'type' => 'hidden'));
        }
        $html .= html_writer::end_tag('div');
        return $html;
    }

    /**
     * Generates and returns HTML code to display information box about how rubric score is converted to the grade
     *
     * @param array $scores
     * @return string
     */
    public function display_rubric_mapping_explained($scores) {
        $html = '';
        if (!$scores) {
            return $html;
        }
        $html .= $this->box(
                html_writer::tag('h4', get_string('rubricmapping', 'gradingform_rubric')).
                html_writer::tag('div', get_string('rubricmappingexplained', 'gradingform_rubric', (object)$scores))
                , 'generalbox rubricmappingexplained');
        return $html;
    }
}
