<?php
if (is_object($term) && $term) {
    $cat_type_view = get_term_meta($term->term_id, $this->_cat_type_view, true);
    $cat_change_view = get_term_meta($term->term_id, $this->_cat_change_view, true);
    
    $per_row = get_term_meta($term->term_id, $this->_cat_per_row, true);
    $per_row_medium = get_term_meta($term->term_id, $this->_cat_per_row_medium, true);
    $per_row_small = get_term_meta($term->term_id, $this->_cat_per_row_small, true);
    
    $cat_layout_style = get_term_meta($term->term_id, $this->_cat_layout_style, true);
    $cat_masonry_mode = get_term_meta($term->term_id, $this->_cat_masonry_mode, true);
    
    $recommend_columns = get_term_meta($term->term_id, $this->_cat_recommend_columns, true);
    $recommend_columns_medium = get_term_meta($term->term_id, $this->_cat_recommend_columns_medium, true);
    $recommend_columns_small = get_term_meta($term->term_id, $this->_cat_recommend_columns_small, true);
    
    $relate_columns = get_term_meta($term->term_id, $this->_cat_relate_columns, true);
    $relate_columns_medium = get_term_meta($term->term_id, $this->_cat_relate_columns_medium, true);
    $relate_columns_small = get_term_meta($term->term_id, $this->_cat_relate_columns_small, true);
    ?>
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_type_view; ?>">
                <?php _e('Type View', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_type_view; ?>" id="<?php echo $this->_cat_type_view; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="grid"<?php echo $cat_type_view == 'grid' ? ' selected' : ''; ?>><?php echo esc_html__('Grid View Mode', 'nasa-core'); ?></option>
                    <option value="list"<?php echo $cat_type_view == 'list' ? ' selected' : ''; ?>><?php echo esc_html__('List View Mode', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_change_view; ?>">
                <?php _e('Change View Mode', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_change_view; ?>" id="<?php echo $this->_cat_change_view; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="1"<?php echo $cat_change_view == 1 ? ' selected' : ''; ?>><?php echo esc_html__('Yes, Please!', 'nasa-core'); ?></option>
                    <option value="-1"<?php echo $cat_change_view == -1 ? ' selected' : ''; ?>><?php echo esc_html__('No, Thank!', 'nasa-core'); ?></option>
                </select>
            </div>
            
            <p class="description"><?php esc_html_e('Note: This option only uses with Desktop Mode.', 'nasa-core'); ?></p>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_per_row; ?>">
                <?php _e('Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_per_row; ?>" id="<?php echo $this->_cat_per_row; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="6-cols"<?php echo $per_row == '6-cols' ? ' selected' : ''; ?>><?php echo esc_html__('6 columns', 'nasa-core'); ?></option>
                    <option value="5-cols"<?php echo $per_row == '5-cols' ? ' selected' : ''; ?>><?php echo esc_html__('5 columns', 'nasa-core'); ?></option>
                    <option value="4-cols"<?php echo $per_row == '4-cols' ? ' selected' : ''; ?>><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                    <option value="3-cols"<?php echo $per_row == '3-cols' ? ' selected' : ''; ?>><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $per_row == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                    
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_per_row_small; ?>">
                <?php _e('Mobile Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_per_row_small; ?>" id="<?php echo $this->_cat_per_row_small; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $per_row_small == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                    <option value="1-cols"<?php echo $per_row_small == '1-cols' ? ' selected' : ''; ?>><?php echo esc_html__('1 column', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_per_row_medium; ?>">
                <?php _e('Tablet Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_per_row_medium; ?>" id="<?php echo $this->_cat_per_row_medium; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="4-cols"<?php echo $per_row_medium == '4-cols' ? ' selected' : ''; ?>><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                    <option value="3-cols"<?php echo $per_row_medium == '3-cols' ? ' selected' : ''; ?>><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $per_row_medium == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_layout_style; ?>">
                <?php _e('Layout Style', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_layout_style; ?>" id="<?php echo $this->_cat_layout_style; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="grid-row"<?php echo $cat_layout_style == 'grid-row' ? ' selected' : ''; ?>><?php echo esc_html__('Grid Rows', 'nasa-core'); ?></option>
                    <option value="masonry-isotope"<?php echo $cat_layout_style == 'masonry-isotope' ? ' selected' : ''; ?>><?php echo esc_html__('Masonry Isotope', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_masonry_mode; ?>">
                <?php _e('Isotope Layout Mode', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_masonry_mode; ?>" id="<?php echo $this->_cat_masonry_mode; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="masonry"<?php echo $cat_masonry_mode == 'masonry' ? ' selected' : ''; ?>><?php echo esc_html__('Masonry', 'nasa-core'); ?></option>
                    <option value="fitRows"<?php echo $cat_masonry_mode == 'fitRows' ? ' selected' : ''; ?>><?php echo esc_html__('Fit Rows', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_recommend_columns; ?>">
                <?php _e('Recommend - Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_recommend_columns; ?>" id="<?php echo $this->_cat_recommend_columns; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="6-cols"<?php echo $recommend_columns == '6-cols' ? ' selected' : ''; ?>><?php echo esc_html__('6 columns', 'nasa-core'); ?></option>
                    <option value="5-cols"<?php echo $recommend_columns == '5-cols' ? ' selected' : ''; ?>><?php echo esc_html__('5 columns', 'nasa-core'); ?></option>
                    <option value="4-cols"<?php echo $recommend_columns == '4-cols' ? ' selected' : ''; ?>><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                    <option value="3-cols"<?php echo $recommend_columns == '3-cols' ? ' selected' : ''; ?>><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $recommend_columns == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                    
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_recommend_columns_small; ?>">
                <?php _e('Recommend - Mobile Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_recommend_columns_small; ?>" id="<?php echo $this->_cat_recommend_columns_small; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $recommend_columns_small == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                    <option value="1.5-cols"<?php echo $recommend_columns_small == '1.5-cols' ? ' selected' : ''; ?>><?php echo esc_html__('1,5 column', 'nasa-core'); ?></option>
                    <option value="1-cols"<?php echo $recommend_columns_small == '1-cols' ? ' selected' : ''; ?>><?php echo esc_html__('1 column', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_recommend_columns_medium; ?>">
                <?php _e('Recommend - Tablet Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_recommend_columns_medium; ?>" id="<?php echo $this->_cat_recommend_columns_medium; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="4-cols"<?php echo $recommend_columns_medium == '4-cols' ? ' selected' : ''; ?>><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                    <option value="3-cols"<?php echo $recommend_columns_medium == '3-cols' ? ' selected' : ''; ?>><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $recommend_columns_medium == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_relate_columns; ?>">
                <?php _e('Relate - Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_relate_columns; ?>" id="<?php echo $this->_cat_relate_columns; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="6-cols"<?php echo $relate_columns == '6-cols' ? ' selected' : ''; ?>><?php echo esc_html__('6 columns', 'nasa-core'); ?></option>
                    <option value="5-cols"<?php echo $relate_columns == '5-cols' ? ' selected' : ''; ?>><?php echo esc_html__('5 columns', 'nasa-core'); ?></option>
                    <option value="4-cols"<?php echo $relate_columns == '4-cols' ? ' selected' : ''; ?>><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                    <option value="3-cols"<?php echo $relate_columns == '3-cols' ? ' selected' : ''; ?>><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $relate_columns == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                    
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_relate_columns_small; ?>">
                <?php _e('Relate - Mobile Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_relate_columns_small; ?>" id="<?php echo $this->_cat_relate_columns_small; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $relate_columns_small == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                    <option value="1.5-cols"<?php echo $relate_columns_small == '1.5-cols' ? ' selected' : ''; ?>><?php echo esc_html__('1.5 column', 'nasa-core'); ?></option>
                    <option value="1-cols"<?php echo $relate_columns_small == '1-cols' ? ' selected' : ''; ?>><?php echo esc_html__('1 column', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="form-field nasa-term-root hidden-tag">
        <th scope="row" valign="top">
            <label for="<?php echo $this->_cat_relate_columns_medium; ?>">
                <?php _e('Relate - Tablet Columns', 'nasa-core'); ?>
            </label>
        </th>
        
        <td>
            <div class="nasa-view-mode">
                <select name="<?php echo $this->_cat_relate_columns_medium; ?>" id="<?php echo $this->_cat_relate_columns_medium; ?>" class="postform">
                    <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                    <option value="4-cols"<?php echo $relate_columns_medium == '4-cols' ? ' selected' : ''; ?>><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                    <option value="3-cols"<?php echo $relate_columns_medium == '3-cols' ? ' selected' : ''; ?>><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                    <option value="2-cols"<?php echo $relate_columns_medium == '2-cols' ? ' selected' : ''; ?>><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                </select>
            </div>
            <div class="clear"></div>
        </td>
    </tr>
    
    <tr class="space-admin nasa-term-root hidden-tag"><td colspan="2"></td></tr>
    <?php
} else {
    ?>
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_type_view; ?>">
            <?php _e('Type View', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_type_view; ?>" id="<?php echo $this->_cat_type_view; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="grid"><?php echo esc_html__('Grid View Mode', 'nasa-core'); ?></option>
                <option value="list"><?php echo esc_html__('List View Mode', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_change_view; ?>">
            <?php _e('Change View Mode', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_change_view; ?>" id="<?php echo $this->_cat_change_view; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="1"><?php echo esc_html__('Yes, Please!', 'nasa-core'); ?></option>
                <option value="-1"><?php echo esc_html__('No, Thank!', 'nasa-core'); ?></option>
            </select>
        </div>
        
        <p class="description"><?php esc_html_e('Note: This option only uses with Desktop Mode.', 'nasa-core'); ?></p>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_per_row; ?>">
            <?php _e('Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_per_row; ?>" id="<?php echo $this->_cat_per_row; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="6-cols"><?php echo esc_html__('6 columns', 'nasa-core'); ?></option>
                <option value="5-cols"><?php echo esc_html__('5 columns', 'nasa-core'); ?></option>
                <option value="4-cols"><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                <option value="3-cols"><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_per_row_small; ?>">
            <?php _e('Mobile Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_per_row_small; ?>" id="<?php echo $this->_cat_per_row_small; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                <option value="1-cols"><?php echo esc_html__('1 column', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_per_row_medium; ?>">
            <?php _e('Tablet Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_per_row_medium; ?>" id="<?php echo $this->_cat_per_row_medium; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="4-cols"><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                <option value="3-cols"><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_layout_style; ?>">
            <?php _e('Layout Style', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_layout_style; ?>" id="<?php echo $this->_cat_layout_style; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="grid-row"><?php echo esc_html__('Grid Rows', 'nasa-core'); ?></option>
                <option value="masonry-isotope"><?php echo esc_html__('Masonry Isotope', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_masonry_mode; ?>">
            <?php _e('Isotope Layout Mode', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_masonry_mode; ?>" id="<?php echo $this->_cat_masonry_mode; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="masonry"><?php echo esc_html__('Masonry', 'nasa-core'); ?></option>
                <option value="fitRows"><?php echo esc_html__('Fit Rows', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_recommend_columns; ?>">
            <?php _e('Recommend - Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_recommend_columns; ?>" id="<?php echo $this->_cat_recommend_columns; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="6-cols"><?php echo esc_html__('6 columns', 'nasa-core'); ?></option>
                <option value="5-cols"><?php echo esc_html__('5 columns', 'nasa-core'); ?></option>
                <option value="4-cols"><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                <option value="3-cols"><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_recommend_columns_small; ?>">
            <?php _e('Recommend - Mobile Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_recommend_columns_small; ?>" id="<?php echo $this->_cat_recommend_columns_small; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                <option value="1.5-cols"><?php echo esc_html__('1.5 column', 'nasa-core'); ?></option>
                <option value="1-cols"><?php echo esc_html__('1 column', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_recommend_columns_medium; ?>">
            <?php _e('Recommend - Tablet Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_recommend_columns_medium; ?>" id="<?php echo $this->_cat_recommend_columns_medium; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="4-cols"><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                <option value="3-cols"><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_relate_columns; ?>">
            <?php _e('Relate - Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_relate_columns; ?>" id="<?php echo $this->_cat_relate_columns; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="6-cols"><?php echo esc_html__('6 columns', 'nasa-core'); ?></option>
                <option value="5-cols"><?php echo esc_html__('5 columns', 'nasa-core'); ?></option>
                <option value="4-cols"><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                <option value="3-cols"><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_relate_columns_small; ?>">
            <?php _e('Relate - Mobile Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_relate_columns_small; ?>" id="<?php echo $this->_cat_relate_columns_small; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
                <option value="1.5-cols"><?php echo esc_html__('1.5 column', 'nasa-core'); ?></option>
                <option value="1-cols"><?php echo esc_html__('1 column', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="form-field nasa-term-root hidden-tag">
        <label for="<?php echo $this->_cat_relate_columns_medium; ?>">
            <?php _e('Relate - Tablet Columns', 'nasa-core'); ?>
        </label>
        
        <div class="nasa-view-mode">
            <select name="<?php echo $this->_cat_relate_columns_medium; ?>" id="<?php echo $this->_cat_relate_columns_medium; ?>" class="postform">
                <option value=""><?php echo esc_html__('Default', 'nasa-core'); ?></option>
                <option value="4-cols"><?php echo esc_html__('4 columns', 'nasa-core'); ?></option>
                <option value="3-cols"><?php echo esc_html__('3 columns', 'nasa-core'); ?></option>
                <option value="2-cols"><?php echo esc_html__('2 columns', 'nasa-core'); ?></option>
            </select>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="space-admin nasa-term-root hidden-tag"></div>
    <?php
}
