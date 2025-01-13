<?php
/**
 * Grid class for OpenSimulator Helpers
 */
if( ! defined( 'OSHELPERS' ) ) {
    exit;
}

class OpenSim_Grid {
    private $grid_status;
    private $grid_info;
    private $grid_info_card;
    private $grid_status_card;

    public function __construct() {
        // $this->grid_status = $this->get_grid_status();
        // $this->grid_info = $this->get_grid_info();
        // $this->grid_info_card = $this->get_grid_info_card();
        // $this->grid_status_card = $this->get_grid_status_card();
    }

    public static function get_grid_info( $grid_uri = false, $args = array() ) {
        $info = array();
        
        if( ! $grid_uri ) {
            $info = array_filter( array_merge(
                $info,
                array(
                    OpenSim::get_option( 'GridInfoService.gridname' ),
                    'Login URI' => OpenSim::hop( OpenSim::get_option( 'Hypergrid.HomeURI' ) ),
                )
            ) );
            // $info = OpenSim::get_option( 'GridInfoService' );
            // $login_uri = OpenSim::get_option( 'Hypergrid.HomeURI' );
        } else {
            $info = array(
                'Grid Name' => 'External Grid, not implemented.',
            );
        }
        
        $info = array_filter( $info );
        try {
            if( empty( $info ) ) {
                throw new Exception( 'No grid info found.' );
            }
        } catch( Exception $e ) {
            return $e;
        }

        return $info;
    }

    public static function array_to_card( $id, $info, $args = array() ) {
        if( empty( $info ) ) {
            return;
        }
        if( ! empty( $args['title'] ) && is_string( $args['title'] ) ) {
            $title = $args['title'];
        } else {
            $title = array_shift( $info );
        }

        $html = sprintf(
            '<div class="flex-fill">
                <div id="card-%1$s" class="card card-$1$s">   
                    <ul class="list-group list-group-flush ">',
            $id,
        );

        $html .= sprintf(
            '<li class="list-group-item active">
                <h5 class="card-title p-0 m-0">%s</h5>
            </li>',
            $title,
        );
        foreach( $info as $key => $value ) {
            $html .= sprintf(
                '<li class="list-group-item">%s %s</li>',
                is_numeric( $key ) ? '' : $key . ':',
                $value
            );
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
        $info = self::get_grid_info( $grid_uri, $args );
        if( ! $info || OpenSim::is_error( $info ) ) {
            return false;
        }
        $title = false;
        if( ! empty( $args['title'])) {
            $title = $args['title'] === true ? _( 'Grid Information' ) : $args['title'];
        }

        return self::array_to_card( 'grid-info', $info, $title );
    }

    // public function get_grid_status() {
    //     $status = OpenSim::get_grid_status();
    //     return $status;
    // }

    // public function get_grid_info() {
    //     $info = OpenSim::get_grid_info();
    //     return $info;
    // }

    // public function get_grid_info_card() {
    //     $info = $this->get_grid_info();
    //     $html = '';
    //     if( ! empty( $info ) ) {
    //         $html .= '<div class="card">';
    //         $html .= '<div class="card-header">';
    //         $html .= '<h5 class="card-title">' . _('Grid Info') . '</h5>';
    //         $html .= '</div>';
    //         $html .= '<div class="card-body">';
    //         $html .= OpenSim::array2table( $info, 'gridinfo' );
    //         $html .= '</div>';
    //         $html .= '</div>';
    //     }
    //     return $html;
    // }

    public static function grid_status_card( $grid_uri = null, $args = null ) {
        $info = self::grid_status( $grid_uri, $args );
        error_log( __FUNCTION__ . ' info: ' . print_r($info, true) );


        return self::array_to_card( 'grid-status', $info );

        if( ! $info || OpenSim::is_error( $info ) ) {
            return false;
        }
        $title = false;
        if( ! empty( $args['title'])) {
            $title = $args['title'] === true ? _( 'Grid Information' ) : $args['title'];
        }

        $html = self::array_to_card( 'grid-status', $info, $title );
        return $html;
    }

    public static function grid_status( $grid_url = null ) {
        // Fake data for debugging purpose
        $info = array(
            _('Status') => _('Grid Online'),
            _('Members') => 113,
            _('Active Members (30 days)') => 6,
            _('Members in world') => 0,
            _('Active users (30 days)') =>	33,
            _('Total users in world') => 0,
            _('Regions') => 22,
            _('Total area') => '1.44 km²',
        );
        return $info;
        // End debugging

        global $OpenSimDB;
        // If db is not yet configured, calls to $OpenSimDB would crash otherwise
        if ( ! $OpenSimDB ) {
            return false;
        }


        // $status = wp_cache_get( 'gridstatus', 'w4os' );
        // if ( false === $status ) {
        // 	// $cached="uncached";
        // 	if ( $OpenSimDB->check_connection() ) {
        // 		$lastmonth = time() - 30 * 86400;
    
        // 		// $urlinfo    = explode( ':', get_option( 'w4os_login_uri' ) );
        // 		// $host       = $urlinfo['0'];
        // 		// $port       = $urlinfo['1'];
        // 		// $fp         = @fsockopen( $host, $port, $errno, $errstr, 1.0 );
        // 		$gridonline = w4os_grid_status();
    
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
