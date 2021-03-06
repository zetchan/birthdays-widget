<?php
/**
 * Adds Birthdays_Widget widget.
 */
class Birthdays_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'birthdays_widget', // Base ID
            __('Birthdays Widget'), // Name
            array( 'description' => __( 'Happy birthday widget', 'birthdays-widget' ), ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        
        if ( $instance[ 'template' ] == 2 || $instance[ 'template' ] == 3 ) {
            $birthdays = birthdays_widget_check_for_birthdays( true );
        } else {
            $birthdays = birthdays_widget_check_for_birthdays();
        }
        if ( count( $birthdays ) >= 1 ) {
            $title = apply_filters( 'widget_title', $instance[ 'title' ] );
            echo $args[ 'before_widget' ];
            if ( ! empty( $title ) )
                echo $args[ 'before_title' ] . $title . $args[ 'after_title' ];

            echo self::birthdays_code( $instance, $birthdays );

            /* TODO make again ajax support?
                wp_enqueue_script('birthdays-widget-script', plugins_url('script.js', __FILE__ ), array('jquery'));
                wp_localize_script('birthdays-widget-script', 'ratingsL10n', array( 'admin_ajax_url' => admin_url('admin-ajax.php')));
            */
            echo $args[ 'after_widget' ];
        }
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $birth_widg = get_option( 'birthdays_widget_settings' );
        $birth_widg = maybe_unserialize( $birth_widg );
        $instance = wp_parse_args( (array) $instance, $birth_widg );
        if ( !isset( $instance[ 'title' ] ) )
            $instance[ 'title' ] = "Birthdays Widget";
        if ( !isset( $instance[ 'template' ] ) )
            $instance[ 'template' ] = 0;
        ?>
        <p><fieldset class="basic-grey">
            <legend><?php _e( 'Settings', 'birthdays-widget' ); ?>:</legend>
            <label>
                <span><?php _e( 'Title', 'birthdays-widget' ); ?></span>
                <input  id="<?php echo $this->get_field_id( 'title' ); ?>" 
                        name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" 
                        value="<?php empty( $instance[ 'title' ] ) ? '' : esc_attr_e( $instance[ 'title' ] ) ; ?>" />
            </label>
            <label>
                <span><?php _e( 'Template', 'birthdays-widget' ); ?></span>
                <select id="<?php echo $this->get_field_id( 'template' ); ?>" 
                        name="<?php echo $this->get_field_name( 'template' ); ?>">
                    <option value="0" <?php if ( $instance[ 'template' ] == 0 ) echo "selected='selected'"; ?>><?php _e( 'Default', 'birthdays-widget' ); ?></option>
                    <option value="1" <?php if ( $instance[ 'template' ] == 1 ) echo "selected='selected'"; ?>><?php _e( 'List', 'birthdays-widget' ); ?></option>
                    <option value="2" <?php if ( $instance[ 'template' ] == 2 ) echo "selected='selected'"; ?>><?php _e( 'Calendar', 'birthdays-widget' ); ?></option>
                    <option value="3" <?php if ( $instance[ 'template' ] == 3 ) echo "selected='selected'"; ?>><?php _e( 'Upcoming', 'birthdays-widget' ); ?></option>
                </select>
            </label>
        </fieldset></p>
        <?php
    }

    public static function organize_days( $filtered ) {
        $days_organized = array();
        foreach ( $filtered as $user_birt ) {
            if( !isset( $user_birt->date ) )
                var_dump( $user_birt );
            $user_birt->tmp = substr( $user_birt->date, 5 );
            if ( !isset ( $days_organized[ $user_birt->tmp ] ) ) {
                $days_organized[ $user_birt->tmp ] = array();
            }
            $days_organized[ $user_birt->tmp ][] = $user_birt;
        }
        return $days_organized;
    }

    public static function birthdays_code( $instance, $birthdays = NULL ) {
        wp_enqueue_style( 'birthdays-css' );
        $html = "";
        $birthdays_settings = get_option( 'birthdays_settings' );
        $birthdays_settings = maybe_unserialize( $birthdays_settings );
        if ( isset( $instance[ 'img_width' ] ) ) {
            $birthdays_settings[ 'image_width' ] = $instance[ 'img_width' ];
        }
        if ( !isset( $instance[ 'class' ] ) ) {
            $instance[ 'class' ] = '';
        }
        if ( !isset( $instance[ 'template' ] ) ) {
            $instance[ 'template' ] = 0;
        }
        $html .= "<div class=\"birthdays-widget {$instance[ 'class' ]}\">";
            if ( $birthdays_settings[ 'image_enabled' ] ) {
                $tmp_size = $birthdays_settings[ 'image_width' ];
                if ( is_numeric( $birthdays_settings[ 'image_url' ] ) ) {
                    $default_image_src = wp_get_attachment_image_src( $birthdays_settings[ 'image_url' ], 'medium' );
                    $default_image_src = $default_image_src[ 0 ];
                } else {
                    $default_image_src = $birthdays_settings[ 'image_url' ];
                }
                $html .= "<img style=\"width: {$birthdays_settings[ 'image_width' ]}\" 
                    src=\"$default_image_src\" alt=\"birthday_cake\" class=\"aligncenter\" />";
            }
            if ( $birthdays_settings[ 'user_image_enabled' ] ) {
                if ( is_numeric( $birthdays_settings[ 'user_image_url' ] ) ) {
                    $default_user_image_src = wp_get_attachment_image_src( $birthdays_settings[ 'user_image_url' ], 'medium' );
                    $default_user_image_src = $default_user_image_src[ 0 ];
                } else {
                    $default_user_image_src = $birthdays_settings[ 'user_image_url' ];
                }
            }
            $html .= "<div class=\"birthday-wish\">{$birthdays_settings[ 'wish' ]}</div>";
            /*
             * For each user that has birthday today, if his name is
             * in the cs_birth_widg_# format (which means he is a WP User),
             * show his name if and only if the option to 
             * save Users' birthdays in our table is enabled.
             */
            $meta_key = $birthdays_settings[ 'meta_field' ];
            $prefix = "cs_birth_widg_";
            $filtered = array();
            $year = true;
            foreach ( $birthdays as $row ) {
                //Check if this is record represents a WordPress user
                $wp_usr = strpos( $row->name, $prefix );
                //var_dump( $row );
                if ( is_numeric( $row->image ) || $row->image == NULL ) {
                    if ( $instance[ 'template' ] == 2 ) {
                        $row->image = wp_get_attachment_image_src( $row->image, array( 150, 150 ) );
                    } else {
                        $row->image = wp_get_attachment_image_src( $row->image, 'medium' );
                    }
                    $row->image = $row->image[ 0 ];
                }
                if ( $wp_usr !== false ) {
                    //If birthdays are disabled for WP Users, or birthday date is drown from WP Profile, skip the record
                    if ( ( $birthdays_settings[ 'profile_page' ] == 0 && $birthdays_settings[ 'date_from_profile' ] == 0 ) 
                        || $birthdays_settings[ 'date_from_profile' ] ) {
                        continue;
                    }
                    //Get the ID from the record, which is of the format $prefixID and get the user's data
                    $birth_user = get_userdata( substr( $row->name, strlen( $prefix ) ) );
                    //If user's image is drawn from Gravatar
                    if ( $birthdays_settings[ 'wp_user_gravatar' ] ) {
                        if ( $instance[ 'template' ] == 2 ) {
                            $row->image = Birthdays_Widget_Settings::get_avatar_url( $birth_user->user_email, 96 );
                        } else {
                            $row->image = Birthdays_Widget_Settings::get_avatar_url( $birth_user->user_email, 256 );
                        }
                    }
                    //If birthdays are enabled for WP Users, draw user's name from the corresponding meta key
                    if ( $birthdays_settings[ 'profile_page' ] ) {
                        $row->name = $birth_user->{$meta_key};
                    }
                }
                //If user has no image, set the default
                if ( ( !isset( $row->image ) || empty( $row->image ) ) && $birthdays_settings[ 'user_image_enabled' ] ) {
                    $row->image = $default_user_image_src;
                }
                array_push( $filtered, $row );
            }
            switch ( $instance[ 'template' ] ) {
                case 0:
                    wp_enqueue_script( 'jquery-ui-tooltip' );
                    wp_enqueue_script( 'birthdays-script' );
                    wp_enqueue_style ( 'jquery-style' );
                    $flag = false;
                    foreach ( $filtered as $row ) {
                        $html .= '<div class="birthday_element birthday_name">';
                        if ( $flag && $birthdays_settings[ 'comma' ] ) {
                            $html .= ', ';
                        } else {
                            $flag = true;
                        }
                        $html .= $row->name;
                        $age = date( "Y" ) - date( "Y", strtotime( $row->date ) );
                        $html .= '<a href="' . $row->image . '" target="_blank" ';
                        if( $birthdays_settings[ 'user_age' ] ) {
                            $html .= 'data-age="' . $age . ' ' . __( 'years old', 'birthdays-widget' ) . '" ';
                        }
                        $html .= '></a></div>';
                    }
                    break;
                case 1:
                    $html .= '<ul class="birthday_list">';
                        foreach ( $filtered as $row ) {
                            $html .= "<li class=\"birthday_name\"><img style=\"width:{$birthdays_settings[ 'list_image_width' ]}\" 
                                    src=\"{$row->image}\" class=\"birthday_list_image\" />{$row->name}";
                            if( $birthdays_settings[ 'user_age' ] ) {
                                $age = date( "Y" ) - date( "Y", strtotime( $row->date ) );
                                $html .= '<span class="birthday_age"> ' . $age . ' ' . __( 'years old', 'birthdays-widget' ) . '</span>';
                            }
                            $html .= "</li>";
                        }
                    $html .= '</ul>';
                    break;
                case 2:
                    if ( defined( 'CALENDAR' ) ) {
                        $html .= "<span class=\"description\">" . __( 'Only one calendar template is available per page. Please check your widget and shortcode options.', 'birthdays-widget' ) . "</span>";
                        break;
                    }
                    define( 'CALENDAR' , true );
                    $days_organized = self::organize_days( $filtered );
                    wp_enqueue_style( 'birthdays-bootstrap-css' );
                    wp_enqueue_style( 'birthdays-calendar-css' );
                    wp_enqueue_script( 'birthdays-bootstrap-js' );
                    wp_enqueue_script( 'birthdays-calendar-js' );
                    global $wp_locale;
                    $months = array();
                    for( $i = 1; $i <= 12; $i++ ) {
                        $months[] = $wp_locale->get_month( $i );
                    }
                    $week_days = array();
                    for( $i = 0; $i <= 6; $i++ ) {
                        $week_days[] = $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $i ) );
                    }
                    $week_days[] = array_shift( $week_days );
                    if ( get_locale() == 'el' ) {
                        for( $i = 0; $i <= 11; $i++ ) {
                            $months[ $i ] = mb_strcut( $months[ $i ], 0, strlen( $months[ $i ] ) - 1 );
                            $months[ $i ] .= "ς";
                        }
                    }
                    $months = implode( '", "', $months );
                    $months = '[ "'.$months.'" ]';
                    $week_days = implode( '", "', $week_days );
                    $week_days = '[ "'.$week_days.'" ]';
                    $html .= '<script>
                        jQuery( document ).ready( function() {
                            var monthNames = ' . $months . ';
                            var dayNames = ' . $week_days . ';
                            var events = [ ';
                                $flag = false;
                                foreach ( $days_organized as $day ) {
                                    $html .= '{ date: "' . date( 'j/n', strtotime( $day[ 0 ]->date ) ) . '/' . date( 'Y' ) . '",';
                                    $html .= 'title: \'' . $birthdays_settings[ 'wish' ] . '\',';
                                    if ( date( 'm-d', strtotime( $day[ 0 ]->date ) ) == date( 'm-d' ) ) {
                                        $color = $birthdays_settings[ 'color_current_day' ];
                                    } else if ( $flag && $birthdays_settings[ 'second_color' ] ) {
                                        $color = $birthdays_settings[ 'color_two' ];
                                        $flag = false;
                                    } else {
                                        $color = $birthdays_settings[ 'color_one' ];
                                        $flag = true;
                                    }
                                    $html .= ' color: "' . $color . '",';
                                    $html .= ' content: \''; 
                                    $comma = false;
                                    foreach ( $day as $user ) {
                                        $html .= '<img src="' . $user->image . '" width="150" /><div class="birthday_center birthday_name">' . $user->name;
                                        if( $birthdays_settings[ 'user_age' ] ) {
                                            $age = date( "Y" ) - date( "Y", strtotime( $user->date ) );
                                            $html .= '<span class="birthday_age"> ' . $age . ' ' . __( 'years old', 'birthdays-widget' ) . '</span>';
                                        }
                                        $html .= '</div>';
                                    }
                                    $html .= '\' }, ';
                                }
                            $html .= ' ];';
                            $html .= "
                                jQuery( '#birthday_calendar' ).bic_calendar( {
                                    events: events,
                                    dayNames: dayNames,
                                    monthNames: monthNames,
                                    showDays: true,
                                    displayMonthController: true,
                                    displayYearController: false
                                } );
                            ";

                            $html .= "jQuery( '#bic_calendar_'+'";
                            $html .= date( 'd_m_Y' );
                            $html .= "' ).addClass( 'selection' ); ";
                        $html .= '} );';
                    $html .= '</script>';
                    $html .= '<div id="birthday_calendar"></div>';
                    break;
                case 3:
                    wp_enqueue_script( 'jquery-ui-tooltip' );
                    wp_enqueue_script( 'birthdays-script' );
                    wp_enqueue_style ( 'jquery-style' );
                    $days_organized = self::organize_days( $filtered );
                    //TODO get current day in format MM-DD
                    $today_key = date( 'm-d' );
                    //var_dump( $today_key );
                    $upcoming_days = $birthdays_settings[ 'upcoming_days_birthdays' ];
                    $consecutive_days = $birthdays_settings[ 'upcoming_consecutive_days' ];
                    $upcoming_mode = $birthdays_settings[ 'upcoming_mode' ];
                    /* If today is not in the array, add the key and sort the array again */
                    if ( ! array_key_exists( $today_key, $days_organized ) ) {
                        $days_organized[ $today_key ] = array();
                        ksort( $days_organized );
                    }
                    /* Find the current day in the array, then iterate to it */
                    $offset = array_search( $today_key, array_keys( $days_organized ) );
                    for ( $i = 0; $i < $offset; $i++ ) {
                        next( $days_organized );
                    }
                    /* Now show the number of days user desires */
                    $final_days = array();
                    if ( $upcoming_mode ) {
                        $today = DateTime::createFromFormat( 'm-d', $today_key );
                        for ( $i = 0; $i < $consecutive_days; $i++ ) {
                            $today->add( new DateInterval( 'P1D' ) );
                            $tmp_day = $today->format( 'm-d' );
                            if ( ! array_key_exists( $tmp_day, $days_organized ) ) {
                                $days_organized[ $tmp_day ] = array();
                            }
                        }
                        ksort( $days_organized );
                        $offset = array_search( $today_key, array_keys( $days_organized ) );
                        for ( $i = 0; $i < $offset; $i++ ) {
                            next( $days_organized );
                        }
                        $upcoming_days = $consecutive_days;
                    }
                    for ( $i = 0; $i < $upcoming_days; $i++ ) {
                        $final_days[] = current( $days_organized );
                        next( $days_organized );
                    }
                    foreach ( $final_days as $day ) {
                        if ( !$day )
                            continue;
                        //var_dump( $day[ 0 ]->date );
                        $timestamp_date = strtotime( $day[ 0 ]->date );
                        $html_date = date_i18n( 'j F', $timestamp_date );
                        $html .= '<div class="birthday_date" >' . $html_date . '</div>';
                        $flag = false;
                        foreach ( $day as $row ) {
                            //var_dump( $row );
                            $html .= '<div class="birthday_element birthday_name">';
                            if ( $flag && $birthdays_settings[ 'comma' ] ) {
                                $html .= ', ';
                            } else {
                                $flag = true;
                            }
                            $html .= $row->name;
                            $age = date( "Y" ) - date( "Y", strtotime( $row->date ) );
                            $html .= '<a href="' . $row->image . '" target="_blank" ';
                            if( $birthdays_settings[ 'user_age' ] ) {
                                $html .= 'data-age="' . $age . ' ' . __( 'years old', 'birthdays-widget' ) . '" ';
                            }
                            $html .= '></a></div>';
                        }
                    }
                    break;
            }
        $html .= '</div>';
        return $html;
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
        $instance[ 'template' ] = ( $new_instance[ 'template' ] ) ? strip_tags( $new_instance[ 'template' ] ) : 0;
        return $instance;
    }

} // class Birthdays_Widget
