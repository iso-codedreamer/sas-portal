<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class SAS_Portal_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $table_prefix, $wpdb;

		$sql = <<<SQL
CREATE TABLE `sas_files` (
  `file_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` text,
  `filemime` text,
  `filecontent` longblob,
  `notes` text,
  `subject` varchar(50) DEFAULT NULL,
  `upload_date` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sas_files_classes` (
  `file_id` int(10) UNSIGNED NOT NULL,
  `class` varchar(15) NOT NULL,
  PRIMARY KEY (`file_id`,`class`) USING BTREE,
  FOREIGN KEY (`file_id`) REFERENCES `sas_files` (`file_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sas_issued_passwords` (
  `phone` varchar(16) NOT NULL,
  `issued_time` int(10) UNSIGNED DEFAULT NULL,
  `hash` text,
  PRIMARY KEY (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sas_student_data` (
  `reg_num` varchar(20) NOT NULL,
  `names` varchar(100) NOT NULL,
  `class` varchar(15) DEFAULT NULL,
  `data` mediumtext,
  PRIMARY KEY (`reg_num`),
  KEY `class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sas_phones` (
  `phone` varchar(16) NOT NULL,
  `reg_num` varchar(20) NOT NULL,
  PRIMARY KEY (`reg_num`,`phone`),
  FOREIGN KEY (`reg_num`) REFERENCES `sas_student_data` (`reg_num`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sas_playlists` (
  `class` varchar(15) NOT NULL,
  `playlist_id` varchar(255) NOT NULL,
  PRIMARY KEY (`class`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `sas_files_downloads` (
  `download_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(10) UNSIGNED DEFAULT NULL,
  `filename` text,
  `phone` varchar(16) DEFAULT NULL,
  `download_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`download_id`),
  FOREIGN KEY (`file_id`) REFERENCES `sas_files` (`file_id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SQL;


		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}

}
