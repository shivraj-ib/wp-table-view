<?php

class WPTV_Html_Helper {

    public $post_fields = array('id', 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'date', 'status');
    public $option_fields_array = array(
        array(
            'field_title' => 'Enable Column',
            'field_key' => 'field',
            'field_type' => 'checkbox',
            'show_lable' => true,
            'default_value' => '',
            'help_text' => 'Check this field to show this column on front end'
        ),
        array(
            'field_title' => 'Custom Column Title',
            'field_key' => 'custom_column_title',
            'field_type' => 'text',
            'show_lable' => false,
            'default_value' => '',
            'help_text' => 'Default blank'
        ),
        array(
            'field_title' => 'Search Enable',
            'field_key' => 'search_enable',
            'field_type' => 'checkbox',
            'show_lable' => false,
            'default_value' => 1,
            'help_text' => 'Check this field to enable search for this column'
        ),
        array(
            'field_title' => 'Search Type',
            'field_key' => 'search_type',
            'field_type' => 'select',
            'show_lable' => false,
            'default_value' => '',
            'field_options' => array(
                'Please Select' => '',
                'Text' => 'text',
                'Filter' => 'filter'
            )
        ),
        array(
            'field_title' => 'Custom Output',
            'field_key' => 'is_custom',
            'field_type' => 'textarea',
            'show_lable' => false,
            'default_value' => '',
            'help_text' => 'define custom html output for this column eq. <a>{field_name}</a>'
        ),
        array(
            'field_title' => 'Order Number',
            'field_key' => 'order_number',
            'field_type' => 'text',
            'show_lable' => false,
            'default_value' => '',
            'help_text' => 'Enter order number for this column'
        ),
        array(
            'field_title' => 'Is Attachment ID',
            'field_key' => 'is_attachment_id',
            'field_type' => 'checkbox',
            'show_lable' => true,
            'default_value' => 1,
            'help_text' => 'Check this field to get file url if the field value is attachment_id/file_id'
        ),
        array(
            'field_title' => '',
            'field_key' => 'is_meta',
            'field_type' => 'hidden',
            'show_lable' => false,
            'default_value' => '',
            'help_text' => ''
        )
    );

    public function getFieldsHtml($fields, $field_type, $field_name, $post_type, $selectedOptions) {
        $returnHtml = '';
        foreach ($fields as $key => $field) {
            //check if post type support
            if ($field_type == 'post_field' && !in_array($field, array('id', 'date', 'status')) && false === post_type_supports($post_type, $field)) {
                continue;
            }
            $field_column_details = $this->option_fields_array;
            $returnHtml .= '<div class="group">';
            $columnSelected = $this->getSelectedValue($selectedOptions, 'field', $field);
            $checkIcon = empty($columnSelected) ? '' : ' <span class="dashicons dashicons-yes"></span> ';
            $selectedOrder = $this->getSelectedValue($selectedOptions, 'order_number', $field);
            $selectedOrder = empty($selectedOrder) ? '' : ' <span class="selected_order">Order number: ' . $selectedOrder . '</span> ';
            $returnHtml .= '<h3>' . $field . $checkIcon . $selectedOrder . '</h3>';
            $returnHtml .= '<div>';
            foreach ($field_column_details as $details) {
                $html_field_name = $field_name . '[' . $key . '][' . $details['field_key'] . ']';
                $value = $this->getSelectedValue($selectedOptions, $details['field_key'], $field);
                $checked = (false === empty($value)) ? 'checked' : '';
                $value = (false === empty($value)) ? $value : $details['default_value'];
                $returnHtml .= '<div class="wptv-field">';
                if ($details['field_type'] != 'hidden') {
                    $returnHtml .= '<label>' . $details['field_title'] . ': </label>';
                }
                switch ($details['field_type']) {
                    case "text":
                        $returnHtml .= '<input type="text" name="' . $html_field_name . '" value="' . $value . '">';
                        break;
                    case "checkbox":
                        if ($details['field_key'] == 'field') {
                            $value = $field;
                        }
                        $returnHtml .= '<input type="checkbox" name="' . $html_field_name . '" value="' . $value . '" ' . $checked . '>';
                        break;
                    case "select":
                        $returnHtml .= '<select name="' . $html_field_name . '">';
                        foreach ($details['field_options'] as $option_title => $option_value) {
                            $default_selected = '';
                            if (!empty($value)) {
                                $default_selected = ($option_value == $value) ? 'selected="selected"' : $default_selected;
                            }
                            $returnHtml .= '<option value="' . $option_value . '" ' . $default_selected . '>' . $option_title . '</option>';
                        }
                        $returnHtml .= '<select>';
                        break;
                    case "textarea":
                        $returnHtml .= '<textarea name="' . $html_field_name . '" rows="4" cols="30">' . $value . '</textarea>';
                        break;
                    case "hidden":
                        $value = ($field_type == 'post_field') ? 0 : 1;
                        $returnHtml .= '<input type="hidden" name="' . $html_field_name . '" value="' . $value . '">';
                        break;
                    default:
                        $returnHtml .= '<input type="text" name="' . $html_field_name . '" value="' . $details['default_value'] . '">';
                }
                if (!empty($details['help_text'])) {
                    $returnHtml .= '<div class="wptv_help_text">(' . htmlentities($details['help_text']) . ')</div>';
                }
                $returnHtml .= '</div>';
            }
            $returnHtml .= '</div>';
            $returnHtml .= '</div>';
        }
        return $returnHtml;
    }

    public function generateOptionFieldsHtml($fields = array(), $field_type, $field_name, $post_type, $options = array()) {
        $returnHtml = '';
        if (empty($fields)) {
            return $returnHtml;
        }
        $field_column_details = $this->option_fields_array;
        $returnHtml .= '<div class="wptv-accordion">';
        $returnHtml .= $this->getFieldsHtml($fields, $field_type, $field_name, $post_type, $options);
        $returnHtml .= '</div>';
        return $returnHtml;
    }

    public function getSelectedValue($selectedOptions, $field_key, $field) {
        $return = '';
        if (empty($selectedOptions) || empty($field)) {
            return $return;
        }

        foreach ($selectedOptions as $value) {
            if ($value['field'] == $field) {
                $return = $value[$field_key];
            }
        }
        return $return;
    }

}
