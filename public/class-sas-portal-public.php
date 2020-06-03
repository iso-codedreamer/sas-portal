<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 * @author     Your Name <email@example.com>
 */
class SAS_Portal_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private static $PASSWORD_EXPIRY_SECONDS = 600;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$options = get_option("sasportal_options");
		if(isset($options['expiry']) && is_numeric($options['expiry'])) {
			self::$PASSWORD_EXPIRY_SECONDS = intval(floatval($options['expiry']) * 60.);
			if(self::$PASSWORD_EXPIRY_SECONDS < 300) self::$PASSWORD_EXPIRY_SECONDS = 600;
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sas-portal-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sas-portal-public.js', array( 'jquery' ), $this->version, false );

	}

	public static function run_shortcode() {
	    ob_start();
	    if($_POST['action'] === 'logout') self::handleLogout();
	    if($_SESSION['verified']) {
	    	self::portalGenerate();
	    } else {
            if ($_POST['action'] === 'start_login') {
                self::handleStartLogin();
            } elseif ($_POST['action'] === 'verify_login') {
                self::handleVerifyLogin();
            } else {
                self::formRequestPhoneNumber();
            }
        }

        $content = do_shortcode(ob_get_clean());
        return $content;
    }

    private static function handleStartLogin() {
		global $wpdb;
		if(empty($_POST['phone'])) {
			self::formRequestPhoneNumber();
			self::alertError(__("Please enter registered parent's phone number"));
			return;
		}
		$phoneNumber = self::formatPhoneNumber($_POST['phone']);
        if(!$phoneNumber) {
            self::formRequestPhoneNumber();
            self::alertError(__("Invalid phone number"));
            return;
        }
        $exists = !is_null($wpdb->get_row("SELECT phone FROM sas_phones WHERE phone='$phoneNumber' LIMIT 1"));
        if(!$exists) {
        	self::formRequestPhoneNumber();
        	self::alertError(__("Unknown number. Please enter phone number registered at school."));
        	return;
        }
        self::sendPassword($phoneNumber);
    }

    private static function sendPassword($phone) {
		global $wpdb;
		$passwordRow = $wpdb->get_row("SELECT * FROM sas_issued_passwords WHERE phone='$phone' ");
		if(!$passwordRow) {
			$password = self::generateAndStorePassword($phone);
			if(!$password) {
				self::alertError(__("Could not generate password. Please try again"));
				return;
			}
			self::sendPasswordBySMS($phone, $password);
		} else {
			$passwordExpireTime = $passwordRow->issued_time;
        }
        $currentTime = time();
        if(empty($passwordExpireTime)) $passwordExpireTime = $currentTime;
		$passwordExpireTime += self::$PASSWORD_EXPIRY_SECONDS;
		if($passwordExpireTime < $currentTime) {
			if(!self::deletePassword($phone)) {
				self::alertError(__("Failure generating new password. Try again"));
				return;
			}
			self::sendPassword($phone);
			return;
		}
		self::formRequestPassword($phone, $passwordExpireTime);
    }

    private static function deletePassword($phone) {
		global $wpdb;
		$success = $wpdb->delete('sas_issued_passwords', array('phone'=>$phone));
		return $success !== false;
    }

    private static function generateAndStorePassword($phone) {
        global $wpdb;
		$characters = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < 6; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
		$success = $wpdb->replace('sas_issued_passwords', array(
				'phone' => $phone,
				'hash' => password_hash($password, PASSWORD_DEFAULT),
				'issued_time' => time(),
			)
		);
        if($success !== false) return $password;
        return false;
    }

	private static function sendPasswordBySMS($phone, $password) {
		$curl = curl_init();
		$options = get_option('sasportal_options');
		if(!is_array($options) || empty($options)) {
			self::alertError(__("System configuration error. Contact school"));
			return;
		}

		$text = str_replace("#pass", $password, $options['template']);
		$params = array(
			'from' => $options['senderid'],
			'to' => $phone,
			'text' => $text,
			'transliteration' => "NON_UNICODE"
		);

		curl_setopt_array($curl, array(
			CURLOPT_URL => $options['apiurl']."/sms/2/text/single",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($params),
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"authorization: {$options['auth']}",
				"content-type: application/json"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if(!empty($err)) {
			self::alertError(__("We could not send you an SMS. Please contact school"));
			return;
		}
		$status = json_decode($response, true)['messages'][0]['status']['groupName'];
		if($status != "PENDING") {
			self::alertError(__("Message could not be sent to your phone. Please check with the school"));
			return;
		}
		return;
	}

    private static function handleVerifyLogin() {
		global $wpdb;
		$phone = self::formatPhoneNumber(sanitize_text_field($_POST['phone']));
		$passwordRow = $wpdb->get_row("SELECT * FROM sas_issued_passwords where phone='$phone'");
		if(!password_verify(strtoupper($_POST['password']), $passwordRow->hash)) {
			sleep(1);
            self::formRequestPhoneNumber();
            self::alertError(__("Invalid password"));
            return;
		}
        $passwordExpireTime = $passwordRow->issued_time + self::$PASSWORD_EXPIRY_SECONDS;
		if(time() > $passwordExpireTime) {
			self::formRequestPhoneNumber();
			self::alertError(__("Password expired"));
			return;
		}
		self::authorizeLogin($phone);
		self::portalGenerate();
    }

    private static function authorizeLogin($phone) {
		$_SESSION['verified'] = true;
		$_SESSION['phone'] = $phone;
    }

    private static function handleLogout() {
		$_SESSION = array();
		session_destroy();
    }

    private static function formRequestPhoneNumber() {
	    ?>
<form method="post">
    <label><?php _e("Phone number:"); ?></label>
    <input type="text" name="phone" value="<?php echo esc_html($_POST['phone']) ?> "/>
    <button name="action" value="start_login" class="btn btn-primary"><?php _e("Next"); ?></button>
</form>
        <?php
    }

    private static function formRequestPassword($phone, $expiry) {
		$difference = $expiry - time();
		if($difference < 0) $difference = 1;
		?>
<form method="post">
	<input type="hidden" name="phone" value="<?php echo $phone; ?>"/>
	<p><?php printf(__("We have sent an SMS with your password to the number %s"), $phone); ?> </p>
	<label><?php _e("Password:"); ?></label>
	<input type="text" name="password" autocomplete="off" />
	<button name="action" value="verify_login" class="btn btn-primary"><?php _e("Next"); ?></button>
	<button id="resendBtn" name="action" value="start_login" disabled class="btn btn-warning"><?php _e("SMS not received"); ?></button>
</form>
<script>
	(function() {
        setTimeout(function() {
            document.getElementById('resendBtn').removeAttribute('disabled');
        }, <?php echo $difference*1000; ?>);
	})();
</script>
	    <?php
    }

    private static function alertError($text) {
		?>
<div class="alert alert-danger"><?php echo $text; ?></div>
<?php
    }

    private static function formatPhoneNumber($number) {
        $LOCAL_CODE = "255";
        $MOBILE_NUMBER_LENGTH = 12;
        $local = FALSE;
        $number = sanitize_text_field($number);
        //remove all spaces and first plus sign and parantheses
        $number = preg_replace('/^\+|\s|\(|\)/', "", $number);
        //check if there are any non-numeric characters..exit if so
        if(preg_match('/\D/', $number)) return FALSE;
        //if the first digit is a 0 then replace with local country code,
        //also if it already has the local code then set the local flag
        if(preg_match('/^0/', $number) || preg_match("/^$LOCAL_CODE/", $number))
        {
            $number = preg_replace('/^0/', "$LOCAL_CODE", $number);
            $local = TRUE;
        }
        //check for number of digits...should be betrween 10 and 16
        $x = strlen($number);
        if($x < 10 || $x > 16) return FALSE;
        //ensure the number of digits matches the country's format
        if($local && ($x != $MOBILE_NUMBER_LENGTH)) return FALSE;
        //we are done...by this point the number is cleaned and formatted
        return $number;
    }

	private static function portalGenerate() {
		global $wpdb;
		$students = $wpdb->get_results(<<<SQL
SELECT * FROM sas_phones INNER JOIN sas_student_data USING (reg_num)
WHERE phone='{$_SESSION['phone']}'
SQL
);
		if(empty($students)) {
			self::alertError(__("No students found. Please contact school"));
			return;
		}
		?>
<style>
	.bold{
		font-weight: bold;
	}
	.subject-name {
		white-space: nowrap;
	}
	.file-notes {
		font-size: 75%;
		line-height: 1.2;
		margin-top: 10px;
		font-weight: bold;
		white-space: normal;
	}
	.downloadsTable {
		display: block !important;
		overflow-x: auto !important;
		white-space: nowrap !important;
	}
	.new-file {
		border: 1px solid;
		border-radius: 2px;
		font-weight: bold;
		padding: 0 2px;
	}
	.file-time {
		float: right;
		margin: 0;
		padding: 0;
		line-height: 0;
		font-size: 70%;
	}
</style>
<div>
	<form method="post" class="form-inline">
		<label>Number: <?php echo $_SESSION['phone']; ?> </label>
		<button class="btn btn-xs btn-xs" name="action" value="logout">Log out</button>
	</form>
	<?php if(count($students) > 1) { ?>
	<label>Choose between your <?php echo count($students) ?> students here: </label>
	<select class="input-sm" id="studentSelect">
		<?php
		foreach($students as $student) {
			$student->id = preg_replace('/[^a-zA-Z]/', '', $student->names);
			echo "<option value='{$student->id}'>".strtoupper($student->names)."</option>";
		}
		?>
	</select>
	<?php } ?>
</div>
	<?php
	foreach($students as $student) self::generateStudentPage($student);
?>
<script>
(function () {
    jQuery("#studentSelect").change(function() {
        jQuery(".studentPage").css('display','none');
        var id = jQuery(this).val();
        jQuery("#"+id).css('display', '');
    });

    jQuery("#studentSelect").change();
})();
</script>
<?php
	}

	private static function generateStudentPage($student) {
		global $wpdb;
		$studentData = json_decode($student->data, false);
		$files = $wpdb->get_results(<<<SQL
select sf.file_id, sf.filename, notes, subject, upload_date, download_id 
from sas_files sf inner join sas_files_classes sfc on sf.file_id = sfc.file_id
left join sas_files_downloads sfd on sf.file_id = sfd.file_id and phone = '{$_SESSION["phone"]}'
where class='$student->class'
order by download_id, upload_date desc
SQL
		);
		$playListRow = $wpdb->get_row("select * from sas_playlists where class = '$student->class'");
?>
<div class="studentPage" style="display: none" id="<?php echo $student->id; ?>">
	<h4><?php echo $student->names. " : ". $studentData->class; ?></h4>
	<?php
	self::generateDownloadsSection($files);
	self::generateVideosSection($playListRow);
	self::generateResultsSection((array) $studentData->performance);
	?>
</div>
<?php
	}

	private static function generateDownloadsSection($files) {
		?>
	<strong>Downloads</strong>
	<?php
	if(empty($files)) {
		echo "<p>There are no files to download at the moment. Check in later</p>";
		return;
	}
	?>
	<table class="downloadsTable">
		<thead>
		<tr>
			<th>S/N</th>
			<th>Details</th>
			<th>Subject</th>
			<th></th>
		</tr>
		<?php
		$sn = 0;
		foreach($files as $num => $file) {
			$new_lbl = '';
			if(empty($file->download_id)) $new_lbl = '<sup class="new-file">NEW</sup>';
			$file->time = date('d M Y H:i', $file->upload_date);
			$sn++;
			echo <<<HTML
<tr>
	<td>$sn</td>
	<td>
		<form method="post" style="display: inline; padding: 0; margin: 0;">
		<input type="hidden" name="file_id" value="$file->file_id"/>
		<input type="hidden" name="action" value="download"/>
		<button type="submit" style="padding:0; background: none; border: none;">$new_lbl $file->filename </button>
		</form>
		<p class="file-notes">$file->notes</p>
		<p class="file-time">$file->time</p>
	</td>
	<td>$file->subject</td>
	<td>
	<form method="post" class="pull-right">
	<input type="hidden" name="file_id" value="$file->file_id"/>
	<input type="hidden" name="action" value="download"/>
	<button class="btn btn-primary btn-sm">Download</button>
	</form>
	</td>
</td>
</tr>
HTML;

		}
		?>
		</thead>
	</table>
	<?php
	}

	private static function generateVideosSection($playlistRow) {
		?>
		<strong>Videos</strong>
		<?php
		if(is_null($playlistRow)) {
			echo "<p>There are no videos for this class at the moment. Check in later</p>";
			return;
		}
		$playlistID = trim($playlistRow->playlist_id);
		echo "[embedyt]https://www.youtube.com/embed?listType=playlist&list=$playlistID&layout=gallery[/embedyt]";
	}

	private static function generateResultsSection($results) {
		echo "<strong>Exam results</strong>";
		if(!is_array($results) || empty($results)) {
			echo "<p>There are no results to show</p>";
			return;
		}
		foreach($results as $year => $resultsHtml) {
			$resultsHtml = base64_decode($resultsHtml);
			echo $resultsHtml;
		}
	}

	public static function checkForFileDownload() {
		global $wpdb;
		if(empty($_SESSION['verified'])) return null;
		if($_POST['action'] === "download") {
			$fileId = intval($_POST['file_id']);
			if(empty($fileId)) return null;
			$results = $wpdb->get_results(<<<SQL
select * from sas_files sf 
	inner join sas_files_classes sfc on sf.file_id = sfc.file_id
	inner join sas_student_data sd on sd.class = sfc.class
	inner join sas_phones sp on sp.reg_num = sd.reg_num
where phone='{$_SESSION['phone']}' and sf.file_id=$fileId
limit 1
SQL
);
			if(count($results) != 1) return;
			$file = $results[0];
			header('Content-Description: File Transfer');
			header("Content-Type: {$file->filemime}");
			header('Content-Disposition: attachment; filename="'.$file->filename.'"');
			header('Content-Transfer-Encoding: binary');
			header('Connection: Keep-Alive');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . strlen($file->filecontent));
			echo $file->filecontent;
			$wpdb->query("insert into sas_files_downloads (file_id, filename, phone) values ($fileId, '{$file->filename}', '{$_SESSION['phone']}')");
			exit;
		}
		return null;
	}

}
