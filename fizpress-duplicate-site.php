<?php
/*
Plugin Name: Fizpress Duplicate Site
Description: Create a duplicate site upon submission of the user registration gravity form.
Version:     1.0
Author:      Kanikannan M
*/

require_once plugin_dir_path( __DIR__ ) . 'multisite-clone-duplicator/include/config.php';

if(!class_exists('MUCD_Duplicate')) {
    require_once plugin_dir_path( __DIR__ ) . 'multisite-clone-duplicator/lib/duplicate.php';
}
if(!class_exists('MUCD_Functions')) {
    require_once plugin_dir_path( __DIR__ ) . 'multisite-clone-duplicator/lib/functions.php';
}
if(!class_exists('MUCD_Option')) {
    require_once plugin_dir_path( __DIR__ ) . 'multisite-clone-duplicator/include/option.php';
}

if ( is_plugin_active( 'gravityformsuserregistration/userregistration.php' )
     && is_plugin_active( 'multisite-clone-duplicator/multisite-clone-duplicator.php' )
) {
    add_filter( 'gform_userregistration_feed_settings_fields', 'fizpress_add_custom_user_registration_setting', 10, 2 );
    add_action( 'gform_user_registered', 'fizpress_create_duplicate_site', 10, 4 );
}


function fizpress_create_duplicate_site( $user_id, $feed, $entry, $user_pass ) {

  $newsiteaddress = strtolower(rgar( $entry, $feed['meta']['dupicateSiteAddress'] ));
  $current_network = get_network();
  $network_site = $current_network->domain;
  $network_site_id = $current_network->id;

  $newdomain = $newsiteaddress . '.' . $network_site;

  $network_blogs = MUCD_Functions::get_sites();
  foreach( $network_blogs as $blog ) {
    if ($blog['domain'] == $newdomain) {
      return;
    }
  }

  $data = array(
      'source' => $feed['meta']['dupicateOrginalSite'],
      'domain' => $newsiteaddress,
      'title' => rgar( $entry, $feed['meta']['dupicateSiteTitle'] ),
      'email' => rgar( $entry, $feed['meta']['dupicateSiteEmail'] ),
      'copy_files' => isset($feed['meta']['dupicateSiteFiles']) ? 'yes' : 'no',
      'keep_users' => isset($feed['meta']['dupicateSiteUsers']) ? 'yes' : 'no',
      'log' => isset($feed['meta']['dupicateSiteLog']) ? 'yes' : 'no',
      'log-path' => isset($feed['meta']['dupicateSiteLog']) ? $feed['meta']['dupicateSiteLogPath'] : '',
      'advanced' => 'hide-advanced-options',
      'from_site_id' => $feed['meta']['dupicateOrginalSite'],
      'newdomain' => $newdomain,
      'path' => '/',
      'public' => 1,
      'network_id' => $network_site_id,
  );

  MUCD_Duplicate::duplicate_site($data);

}

function fizpress_add_custom_user_registration_setting( $fields, $form ) {

    $log_path = MUCD_Option::get_option_log_directory();

    $network_blogs = MUCD_Functions::get_site_list();
    $network_blogs_choices = array();
    foreach( $network_blogs as $blog ) {
      $network_blogs_choices[] = array(
        'label' => $blog['domain'],
        'value' => $blog['blog_id']
      );
    }

    $fields['network_settings']['fields'][] = array(
        'name'      => 'dupicateSite',
        'label'     => __( 'Duplicate Site', 'my-text-domain' ),
        'type'      => 'checkbox',
        'choices'   => array(
            array(
                'label'         => __( 'Duplicate a site when a user registers.', 'my-text-domain' ),
                'value'         => 1,
                'name'          => 'dupicateSite',
                'onclick' => 'jQuery( this ).parents( "form" ).attr( "action", "#gaddon-setting-row-createSite" ).submit();'
            )
        ),
    );
    $fields['network_settings']['fields'][] = array(
        'label'    => esc_html__( 'Original site to copy', 'gravityformsuserregistration' ),
        'name'     => 'dupicateOrginalSite',
        'required' => true,
        'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Original site to copy', 'gravityformsuserregistration' ), esc_html__( 'Select the site to duplicate.', 'gravityformsuserregistration' ) ),
        'type'     => 'select',
        'class'    => 'medium',
        'choices'   => $network_blogs_choices,
        'dependency'  => array(
          'field'   => 'dupicateSite',
          'values'  => 1
        )
    );
    $fields['network_settings']['fields'][] = array(
    		'label'    => esc_html__( 'Site Address', 'gravityformsuserregistration' ),
    		'name'     => 'dupicateSiteAddress',
    		'required' => true,
    		'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Site Address', 'gravityformsuserregistration' ), esc_html__( 'Select the form field that should be used for the site address.', 'gravityformsuserregistration' ) ),
    		'type'     => 'field_select',
    		'class'    => 'medium',
    		'dependency'  => array(
    			'field'   => 'dupicateSite',
    			'values'  => 1
    		)
    );
    $fields['network_settings']['fields'][] = array(
      'label'    => esc_html__( 'Site Title', 'gravityformsuserregistration' ),
      'name'     => 'dupicateSiteTitle',
      'required' => true,
      'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Site Title', 'gravityformsuserregistration' ), esc_html__( 'Select the form field that should be used for the site title.', 'gravityformsuserregistration' ) ),
      'type'     => 'field_select',
      'class'    => 'medium',
      'dependency'  => array(
        'field'   => 'dupicateSite',
        'values'  => 1
      )
    );
    $fields['network_settings']['fields'][] = array(
      'label'    => esc_html__( 'Site Admin Email', 'gravityformsuserregistration' ),
      'name'     => 'dupicateSiteEmail',
      'required' => true,
      'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Site Admin Email', 'gravityformsuserregistration' ), esc_html__( 'Select the form field that should be used for the site admin email.', 'gravityformsuserregistration' ) ),
      'type'     => 'field_select',
      'args'     => array(
          'input_types' => array( 'email' )
      ),
      'class'    => 'medium',
      'dependency'  => array(
        'field'   => 'dupicateSite',
        'values'  => 1
      )
    );
    $fields['network_settings']['fields'][] = array(
        'name'      => 'dupicateSiteFiles',
        'label'     => __( 'Copy Files', 'my-text-domain' ),
        'type'      => 'checkbox',
        'choices'   => array(
            array(
                'label'         => __( 'Duplicate files from duplicated site upload directory', 'my-text-domain' ),
                'value'         => 1,
                'name'          => 'dupicateSiteFiles',
                'default_value' => 1
            )
        ),
        'dependency'  => array(
          'field'   => 'dupicateSite',
          'values'  => 1
        )
    );
    $fields['network_settings']['fields'][] = array(
        'name'      => 'dupicateSiteUsers',
        'label'     => __( 'Copy Users', 'my-text-domain' ),
        'type'      => 'checkbox',
        'choices'   => array(
            array(
                'label'         => __( 'Keep users and roles from duplicated site', 'my-text-domain' ),
                'value'         => 1,
                'name'          => 'dupicateSiteUsers',
                'default_value' => 1
            )
        ),
        'dependency'  => array(
          'field'   => 'dupicateSite',
          'values'  => 1
        )
    );
    $fields['network_settings']['fields'][] = array(
        'name'      => 'dupicateSiteLog',
        'label'     => __( 'Log', 'my-text-domain' ),
        'type'      => 'checkbox',
        'choices'   => array(
            array(
                'label'         => __( 'Generate log file', 'my-text-domain' ),
                'value'         => 1,
                'name'          => 'dupicateSiteLog'
            )
        ),
        'dependency'  => array(
          'field'   => 'dupicateSite',
          'values'  => 1
        )
    );
    $fields['network_settings']['fields'][] = array(
      'label'    => esc_html__( 'Log directory', 'gravityformsuserregistration' ),
      'name'     => 'dupicateSiteLogPath',
      'type'     => 'text',
      'class'    => 'medium',
      'default_value' => $log_path,
      'dependency'  => array(
        'field'   => 'dupicateSite',
        'values'  => 1
      )
    );

    return $fields;
}
