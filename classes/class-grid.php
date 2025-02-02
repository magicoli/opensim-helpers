<?php
/**
 * Grid class for OpenSimulator Helpers
 */
if( ! defined( 'OSHELPERS' ) ) {
    exit;
}

class OpenSim_Grid {
    private static $grid_stats;
    private $grid_info;
    private $grid_info_card;
    private $grid_stats_card;

    public function __construct() {
        // $this->grid_stats = $this->get_grid_stats();
        // $this->grid_info = $this->get_grid_info();
        // $this->grid_info_card = $this->get_grid_info_card();
        // $this->grid_stats_card = $this->get_grid_stats_card();
    }

    public static function get_grid_info( $grid_uri = false, $args = array() ) {
        $info = array();
        $HomeURI = OpenSim::get_option( 'Hypergrid.HomeURI' );
        if( ! $grid_uri || $grid_uri === $HomeURI ) {
            $is_local_grid = true;
            // Default, get login_uri from config, query grid for live grid_info
            $grid_uri = OpenSim::get_option( 'Hypergrid.HomeURI' );
        } else {
            $is_local_grid = false;
            // External grid lookup, not yet implemented
            $info = array(
                'Grid Name' => 'External Grid, not implemented.',
            );
            return false;
        }
        
        // Fetch live info from grid using $login_uri/get_grid_info and parse xml result in array
        // Example xml result:
        $xml_url = $grid_uri . '/get_grid_info';
        try {
            $xml = simplexml_load_file( $xml_url );
        } catch( Exception $e ) {
            $xml = false;
        }
        if( ! $xml ) {
            $info = array(
                'online' => false,
                'login' => $grid_uri,
            );
        } else {
            $info['online'] = true;
            try {
                $array = (array) $xml;
                if( ! $array ) {
                    throw new Exception( 'Error parsing grid info.' );
                }
                $info = array_merge( $info, $array );
            } catch( Exception $e ) {
                return $e;
            }
        }

        if( $is_local_grid ) {
            $config_info = OpenSim::get_option( 'GridInfoService' );
            if( is_array( $config_info ) ) {
                $info = array_merge( $config_info, $info );
            }
        }

        return $info;
    }

    public static function array_to_card( $id, $info, $args = array() ) {
        if( empty( $info ) ) {
            return;
        }
        $hide_first = '';
        if( ! empty( $args['title'] ) && is_string( $args['title'] ) ) {
            $title = $args['title'];
        } else {
            $title = array_values( $info )[0];
            if( is_numeric( array_keys( $info )[0] ) || ( isset($args['hide_first']) && $args['hide_first'] === true ) ) {
                $hide_first = 'hidden d-none';
            }
        }

        $collapse_head = '';
        $collapse_class = '';
        $collapse_data = '';
        $html = sprintf(
            '<div id="card-%1$s" class="accordion flex-fill">
                <div class="accordion-item card card-$1$s bg-primary">
                    <h5 class="card-title p-0 m-0 accordion-header" %4$s>
                        <button class="accordion-button p-3" data-bs-toggle="collapse" href="#card-list-%1$s" aria-expanded="true" aria-controls="collapse-card-list-%1$s">
                            %2$s
                        </button>
                    </h5>
                <ul id="card-list-%s" class="list-group list-group-flush accordion-collapse collapse show" data-bs-parent="#card-%1$s">',
            $id,
            $title,
            $collapse_head,
            $collapse_class,
            $collapse_data
        );

        foreach( $info as $key => $value ) {
            $html .= sprintf(
                '
                <li class="list-group-item %2$s">
                %3$s %4$s
                </li>',
                $id,
                $hide_first,
                is_numeric( $key ) ? '' : $key . ':',
                $value
            );
            $hide_first = '';
            $class="";
        }
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Get grid information as a card
     */
    public static function grid_info_card( $grid_uri = false, $args = array() ) {
        $grid_info = self::get_grid_info( $grid_uri, $args );
        if( ! $grid_info || OpenSim::is_error( $grid_info ) ) {
            return false;
        }

        $info = array(
            _('Grid Name') => $grid_info['gridname'],
            _('Login URI') => OpenSim::hop( $grid_info['login'] ),
        );

        $title = false;
        if( ! empty( $args['title'])) {
            error_log('using args title = ' . print_r($args['title'], true));
            $title = $args['title'] === true ? _( 'Grid Information' ) : $args['title'];
        } else {
            $title = _( 'Grid Information' );
        }
        return self::array_to_card( 'grid-info', $info, array(
            'title' => $title,
        ) );
    }

    public static function grid_stats_card( $args = null ) {
        $grid_stats = self::get_grid_stats( $args );
        error_log( __METHOD__ . ' info = ' . print_r( $grid_stats, true ) );

        if( ! $grid_stats || OpenSim::is_error( $info ) ) {
            error_log( __METHOD__ . ' grid stats empty or error' );
            return false;
        }

        $title = false;
        if( ! empty( $args['title'])) {
            error_log('using args title = ' . print_r($args['title'], true));
            $title = $args['title'] === true ? _( 'Grid Information' ) : $args['title'];
        } else {
            $title = _( 'Grid Status' );
        }

        return self::array_to_card( 'grid-status', $grid_stats, array(
            'title' => $title,
        ) );
    }

    public static function get_grid_stats( $args = null ) {
        $grid_info = self::get_grid_info();
        $grid_uri = $grid_info['login'];

        error_log('grid_uri = ' . print_r($grid_uri, true));

        // DEBUG - Fake data for debugging purpose
        $labels = array(
            'status' => _('Status'),
            'members' => _('Members'),
            'active_members' => _('Active Members (30 days)'),
            'members_in_world' => _('Members in world'),
            'active_users' => _('Active users (30 days)'),
            'total_users' => _('Total users in world'),
            'regions' => _('Regions'),
            'total_area' => _('Total area'),
        );
        $values = array_map( function( $label ) {
            return 'TBD';
        }, $labels );

        $values = array(
            'status' => $grid_info['online'] ? _('Online') : _('Offline'),
        );
        $labels = array_intersect_key( $labels, $values );
        error_log('labels = ' . print_r($labels, true));

        // $values['status'] = $grid_info['online'] ? _('Online') : _('Offline');
        $stats = array_combine( $labels, $values );
        global $OpenSimDB;
        // If db is not yet configured, calls to $OpenSimDB would crash otherwise
        if ( ! $OpenSimDB ) {
            $stats['error'] = _('Database not configured.');
        }

        return $stats;
        // End DEBUG



        // $status = wp_cache_get( 'gridstatus', 'w4os' );
        // if ( false === $status ) {
        // 	// $cached="uncached";
        // 	if ( $OpenSimDB->check_connection() ) {
        // 		$lastmonth = time() - 30 * 86400;
    
        // 		// $urlinfo    = explode( ':', get_option( 'w4os_login_uri' ) );
        // 		// $host       = $urlinfo['0'];
        // 		// $port       = $urlinfo['1'];
        // 		// $fp         = @fsockopen( $host, $port, $errno, $errstr, 1.0 );
        // 		$gridonline = w4os_grid_stats();
    
        // 		// if ($fp) {
        // 		// $gridonline = __("Yes", 'w4os' );
        // 		// } else {
        // 		// $gridonline = __("No", 'w4os' );
        // 		// }
        // 		$filter = '';
        // 		if ( get_option( 'w4os_exclude_models' ) ) {
        // 			$filter .= "u.FirstName != '" . get_option( 'w4os_model_firstname' ) . "'
        // 			AND u.LastName != '" . get_option( 'w4os_model_lastname' ) . "'";
        // 		}
        // 		if ( get_option( 'w4os_exclude_nomail' ) ) {
        // 			$filter .= " AND u.Email != ''";
        // 		}
        // 		if ( ! empty( $filter ) ) {
        // 			$filter = "$filter AND ";
        // 		}
        // 	}
        // 	$status                                     = array(
        // 		__( 'Status', 'w4os' )                   => $gridonline,
        // 		__( 'Members', 'w4os' )                  => number_format_i18n(
        // 			$OpenSimDB->get_var(
        // 				"SELECT COUNT(*)
        // 		FROM UserAccounts as u WHERE $filter active=1"
        // 			)
        // 		),
        // 		__( 'Active members (30 days)', 'w4os' ) => number_format_i18n(
        // 			$OpenSimDB->get_var(
        // 				"SELECT COUNT(*)
        // 		FROM GridUser as g, UserAccounts as u WHERE $filter PrincipalID = UserID AND g.Login > $lastmonth"
        // 			)
        // 		),
        // 	);
        // 	$status[ __( 'Members in world', 'w4os' ) ] = number_format_i18n(
        // 		$OpenSimDB->get_var(
        // 			"SELECT COUNT(*)
        // 	FROM Presence AS p, UserAccounts AS u
        // 	WHERE $filter RegionID != '00000000-0000-0000-0000-000000000000'
        // 	AND p.UserID = u.PrincipalID;"
        // 		)
        // 	);
        // 	// 'Active citizens (30 days)' => number_format_i18n($OpenSimDB->get_var("SELECT COUNT(*)
        // 	// FROM GridUser as g, UserAccounts as u WHERE g.UserID = u.PrincipalID AND Login > $lastmonth" )),
        // 	if ( ! get_option( 'w4os_exclude_hypergrid' ) ) {
        // 		$status[ __( 'Active users (30 days)', 'w4os' ) ] = number_format_i18n(
        // 			$OpenSimDB->get_var(
        // 				"SELECT COUNT(*)
        // 		FROM GridUser WHERE Login > $lastmonth"
        // 			)
        // 		);
        // 		$status[ __( 'Total users in world', 'w4os' ) ]   = number_format_i18n(
        // 			$OpenSimDB->get_var(
        // 				"SELECT COUNT(*)
        // 		FROM Presence
        // 		WHERE RegionID != '00000000-0000-0000-0000-000000000000';	"
        // 			)
        // 		);
        // 	}
        // 	$status[ __( 'Regions', 'w4os' ) ]    = number_format_i18n(
        // 		$OpenSimDB->get_var(
        // 			'SELECT COUNT(*)
        // 	FROM regions'
        // 		)
        // 	);
        // 	$status[ __( 'Total area', 'w4os' ) ] = number_format_i18n(
        // 		$OpenSimDB->get_var(
        // 			'SELECT round(sum(sizex * sizey / 1000000),2)
        // 	FROM regions'
        // 		),
        // 		2
        // 	) . '&nbsp;km²';
        // 	wp_cache_add( 'gridstatus', $status, 'w4os' );
        // }
        // return $status;
    }
}
