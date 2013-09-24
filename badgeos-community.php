<?php
/**
 * Plugin Name: BadgeOS Community Add-On
 * Plugin URI: http://www.learningtimes.com/
 * Description: This BadgeOS add-on integrates BadgeOS features with BuddyPress and bbPress.
 * Tags: buddypress
 * Author: Credly
 * Version: 1.1.0
 * Author URI: https://credly.com/
 * License: GNU AGPL
 */

/*
 * Copyright © 2012-2013 Credly, LLC
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

class BadgeOS_Community {

	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( 'badgeos-community/' );

		// Load translations
		load_plugin_textdomain( 'badgeos-community', false, 'badgeos-community/languages' );

		// Run our activation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'bp_include', array( $this, 'bp_include' ) );
		add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ) );

		// BuddyPress Action Hooks
		$this->community_triggers = array(
			__( 'Profile/Independent Actions', 'badgeos-community' ) => array(
				'bp_core_activated_user'           => __( 'Activated Account', 'badgeos-community' ),
				'xprofile_avatar_uploaded'         => __( 'Change Profile Avatar', 'badgeos-community' ),
				'xprofile_updated_profile'         => __( 'Update Profile Information', 'badgeos-community' ),
			),
			__( 'Social Actions', 'badgeos-community' ) => array(
				'bp_activity_posted_update'        => __( 'Write an Activity Stream message', 'badgeos-community' ),
				'bp_groups_posted_update'          => __( 'Write a Group Activity Stream message', 'badgeos-community' ),
				'bp_activity_comment_posted'       => __( 'Reply to an item in an Activity Stream', 'badgeos-community' ),
				'bp_activity_add_user_favorite'    => __( 'Favorite an Activity Stream item', 'badgeos-community' ),
				'friends_friendship_requested'     => __( 'Send a Friendship Request', 'badgeos-community' ),
				'friends_friendship_accepted'      => __( 'Accept a Friendship Request', 'badgeos-community' ),
				'messages_message_sent'            => __( 'Send/Reply to a Private Message', 'badgeos-community' ),
			),
			__( 'Group Actions', 'badgeos-community' ) => array(
				'groups_group_create_complete'     => __( 'Create a Group', 'badgeos-community' ),
				'groups_join_group'                => __( 'Join a Group', 'badgeos-community' ),
				'groups_join_specific_group'       => __( 'Join a Specific Group', 'badgeos-community' ),
				'groups_invite_user'               => __( 'Invite someone to Join a Group', 'badgeos-community' ),
				'groups_promote_member'            => __( 'Promoted to Group Moderator/Administrator', 'badgeos-community' ),
				'groups_promoted_member'           => __( 'Promote another Group Member to Moderator/Administrator', 'badgeos-community' ),
			),
			__( 'Discussion Forum Actions', 'badgeos-community' ) => array(
				'bbp_new_topic'                    => __( 'Create a Forum Topic', 'badgeos-community' ),
				'bbp_new_reply'                    => __( 'Reply to a Forum Topic', 'badgeos-community' ),
			)
		);
	}

	/**
	 * Files to include with BuddyPress
	 *
	 * @since 1.0.0
	 */
	public function bp_include() {

		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/rules-engine.php' );
			require_once( $this->directory_path . '/includes/steps-ui.php' );
			if ( bp_is_active( 'xprofile' ) )
				require_once( $this->directory_path . '/includes/bp-members.php' );
			if ( bp_is_active( 'activity' ) )
				require_once( $this->directory_path . '/includes/bp-activity.php' );
		}
	}

	/**
	 * Enqueue custom scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		// Grab the global BuddyPress object
		global $bp;

		// If we're on a BP activity page
		if ( isset( $bp->current_component ) && 'activity' == $bp->current_component ) {
			wp_enqueue_style( 'badgeos-front' );
		}
	}

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// If BadgeOS is available, run our activation functions
		if ( $this->meets_requirements() ) {

			//Add default BuddPress settings to each achievement type that may already exist.
			$args=array(
				'post_type' => 'achievement-type',
			  	'post_status' => 'publish',
			  	'posts_per_page' => -1
			);
			$query = new WP_Query($args);
			if( $query->have_posts() ) {
  				while ($query->have_posts()) : $query->the_post();
 	 				update_post_meta( get_the_ID(), '_badgeos_create_bp_activty', 'on' );
 	 				update_post_meta( get_the_ID(), '_badgeos_show_bp_member_menu', 'on' );
 	 			endwhile;
			}

		}

	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') && function_exists('badgeos_get_user_earned_achievement_types') && class_exists('BuddyPress') )
			return true;
		else
			return false;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
				if ( !class_exists('BadgeOS') || !function_exists('badgeos_get_user_earned_achievement_types') )
					echo '<p>' . sprintf( __( 'BadgeOS Community Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-community' ), admin_url( 'plugins.php' ) ) . '</p>';
				elseif ( !class_exists('BuddyPress') )
					echo '<p>' . sprintf( __( 'BadgeOS Community Add-On requires BuddyPress and has been <a href="%s">deactivated</a>. Please install and activate BuddyPress and then reactivate this plugin.', 'badgeos-community' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

}
$GLOBALS['badgeos_community'] = new BadgeOS_Community();
