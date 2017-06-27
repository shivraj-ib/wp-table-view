<?php
/**
 * This file will generate front-end Table view for post types using short-code 
 */
add_shortcode('wptv', 'wptvShortCodeCallback');

function wptvShortCodeCallback($args) {
    if (!isset($args['id']) || !isset($args['type']))
        return 'Invalid Short-Code';
    $post_id = $args['id'];
    $post_type = $args['type'];
    $general_settings = get_post_meta($post_id, 'wptv_general_settings', true);

    $post_fields_settings = get_post_meta($post_id, 'wptv_post_field', true);

    $post_meta_field_settings = get_post_meta($post_id, 'wptv_post_meta_field', true);

    $columns = array_merge($post_fields_settings, $post_meta_field_settings);

    $show_search_form = !empty($general_settings['wptv_show_search']) ? 'true' : 'false';
    $show_pagination = !empty($general_settings['wptv_show_pagination']) ? 'true' : 'false';
    $show_info = !empty($general_settings['wptv_show_info']) ? 'true' : 'false';
    $number_of_reco_per_page = !empty($general_settings['wptv_no_of_reco']) &&
            is_numeric($general_settings['wptv_no_of_reco']) ? $general_settings['wptv_no_of_reco'] : 10;

    usort($columns, "wptvSortByOrder");

    $column_config_array = array();
    ob_start();
    //start table building
    if (!empty($columns) && is_array($columns)) {
        echo '<table id="wptv_table_' . $post_id . '" class="wptv_table_view display" cellspacing="0" width="100%">';
        echo '<thead><tr>';
        //set headers
        foreach ($columns as $column) {
            $column_title = empty($column['custom_column_title']) ? $column['field'] : $column['custom_column_title'];
            $search_enabled = !empty($column['search_enable']) && empty($column['is_custom']) ? 1 : 0;
            $search_type = !empty($column['search_type']) ? $column['search_type'] : 'text';
            $column_config_array[] = array('search_enabled' => $search_enabled, 'search_type' => $search_type);
            echo '<th data-s-filter-type="text">' . $column_title . '</th>';
        }
        echo '</tr></thead>';
        //set columns
        echo '<tbody>';
        $post_data = wptvGetPostData($post_type, $columns);
        if (!empty($post_data) && is_array($post_data)) {
            foreach ($post_data as $data) {
                echo '<tr>';
                foreach ($data as $column_value) {
                    echo '<td>' . $column_value . '</td>';
                }
                echo '</tr>';
            }
        }
        echo '</tbody>';
        echo '</table>';
    }
    ?>
    <script>
        jQuery(document).ready(function ($) {
            var wptv_column_config = <?php echo json_encode($column_config_array); ?>;
            $('#<?php echo 'wptv_table_' . $post_id; ?>').DataTable({
                "paging": <?php echo $show_pagination; ?>,
                "info": <?php echo $show_info; ?>,
                "pageLength": <?php echo $number_of_reco_per_page; ?>,
                initComplete: function () {
                    this.api().columns().every(function (i) {
                        var column = this;
                        if (wptv_column_config[i].search_enabled == 1) {
                            if (wptv_column_config[i].search_type == 'filter') {
                                var select = $('<select><option value="">Please select</option></select>')
                                        .appendTo($(column.header()))
                                        .on('change', function () {
                                            var val = $.fn.dataTable.util.escapeRegex(
                                                    $(this).val()
                                                    );

                                            column
                                                    .search(val ? '^' + val + '$' : '', true, false)
                                                    .draw();
                                        });

                                column.data().unique().sort().each(function (d, j) {
                                    if (d != '') {
                                        select.append('<option value="' + d + '">' + d + '</option>')
                                    }
                                });
                            } else {
                                var input = $('<input type="text" value="" placeholder="search.." style="width:74%">')
                                        .appendTo($(column.header()))
                                        .on('keyup', function () {
                                            var val = $.fn.dataTable.util.escapeRegex(
                                                    $(this).val()
                                                    );

                                            column
                                                    .search(val ? '^' + val + '$' : '', true, false)
                                                    .draw();
                                        });
                            }
                        }
                    });
                }
            });
        });
    </script>
    <?php if ($show_search_form == 'false'): ?>
        <style>
            <?php echo '#wptv_table_' . $post_id . '_filter'; ?>{display:none;}
        </style>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function wptvSortByOrder($a, $b) {
    return $a["order_number"] - $b["order_number"];
}

function wptvGetPostData($post_type, $columns) {
    $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1
    );
    // The Query
    $the_query = new WP_Query($args);

    $return = array();

    if ($the_query->have_posts()) {
        wp_reset_postdata();
        while ($the_query->have_posts()) {
            $the_query->the_post();
            $value = '';
            $value_array = array();
            foreach ($columns as $column) {
                if ($column['is_meta'] == 1) {
                    $value = get_post_meta(get_the_ID(), $column['field'],true);
                } else {
                    switch ($column['field']) {
                        case 'id':
                            $value = get_the_ID();
                            break;
                        case 'title':
                            $value = get_the_title();
                            break;
                        case 'editor':
                            $value = get_the_content();
                            break;
                        case 'author':
                            $value = get_the_author();
                            break;
                        case 'thumbnail':
                            $value = get_the_post_thumbnail_url();
                            break;
                        case 'excerpt':
                            $value = get_the_excerpt();
                            break;
                        case 'date':
                            $value = get_the_date();
                            break;
                        case 'status':
                            $value = get_post_status();
                            break;
                        default:
                            break;
                    }
                }

                //if value is array

                if (is_array($value)) {
                    $value = implode(",", $value);
                }
                
                //if file id
                if(!empty($column['is_attachment_id']) && is_numeric($value)){
                    $value = wp_get_attachment_url($value);
                }
                
                if (!empty($column['is_custom'])) {
                    $value = str_replace("{" . $column['field'] . "}", $value, $column['is_custom']);
                }

                $value_array[$column['field']] = $value;
            }
            $return[] = $value_array;
        }
        wp_reset_query();
    }

    return $return;
}
