<?php
	@ session_start(); 

	// let display a loading message. should be better than a white screen
	if( isset( $_REQUEST["provider"] ) && ! isset( $_REQUEST["redirect_to_provider"] )){
		// selected provider 
		$provider = @ trim( strip_tags( $_REQUEST["provider"] ) ); 

		if( isset( $_REQUEST["link"] ) && (int) $_REQUEST["link"] ){
			// todo
		}
		else{
			$_SESSION["HA::STORE"] = ARRAY();
		}
?>
<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Redirecting...</title>
<head> 
<script>
function init() {
	setTimeout( function(){window.location.href = window.location.href + "&redirect_to_provider=true"}, 750 );
}
</script>
<style>
html {
    background: #f9f9f9;
}
#wsl {
	background: #fff;
	color: #333;
	font-family: sans-serif;
	margin: 2em auto;
	padding: 1em 2em;
	-webkit-border-radius: 3px;
	border-radius: 3px;
	border: 1px solid #dfdfdf;
	max-width: 700px;
	font-size: 14px;
}  
</style>
</head>
<body onload="init();">
<div id="wsl">
<table width="100%" border="0">
  <tr>
    <td align="center" height="40px"><br /><br />Contacting <b><?php echo ucfirst( $provider ) ; ?></b>, please wait...</td> 
  </tr> 
  <tr>
    <td align="center" height="80px" valign="middle"><img src="../assets/img/loading.gif" /></td>
  </tr> 
</table> 
</div> 
</body>
</html> 
<?php
		die();
	} // end display loading 

	// if user select a provider to login with 
	// and redirect_to_provider eq ture
	if( isset( $_REQUEST["provider"] ) && isset( $_REQUEST["redirect_to_provider"] )){ 
		try{ 
			// load wp-load.php
			require_once( dirname( dirname( dirname( dirname( __FILE__ )))) . '/../wp-load.php' );

			// Bouncer :: Accounts Linking is enabled
			if( get_option( 'wsl_settings_bouncer_linking_accounts_enabled' ) != 1 && isset( $_REQUEST["link"] ) ){
				wp_die( "Bouncer say you are doin it wrong." );
			}

			if( ! isset( $_REQUEST["link"] ) && is_user_logged_in() ){
				global $current_user;
				get_currentuserinfo(); 

				wp_die( "You are already logged in as <b>{$current_user->display_name}</b>." );
			}

			# Hybrid_Auth already used?
			if ( class_exists('Hybrid_Auth', false) ) {
				wp_die( "Error! Another plugin seems to be using HybridAuth Library and made WordPress Social Login unusable. We recommand to find this plugin and to kill it with fire!" ); 
			}

			// load hybridauth
			require_once( dirname(__FILE__) . "/../hybridauth/Hybrid/Auth.php" );

			// selected provider name 
			$provider = @ trim( strip_tags( $_REQUEST["provider"] ) );

			// build required configuratoin for this provider
			if( ! get_option( 'wsl_settings_' . $provider . '_enabled' ) ){
				throw new Exception( 'Unknown or disabled provider' );
			}

			$config = array();
			$config["base_url"]  = strtolower( plugins_url() ) . '/wordpress-social-login/hybridauth/'; 
			$config["providers"] = array();
			$config["providers"][$provider] = array();
			$config["providers"][$provider]["enabled"] = true;

			// check base_url
			if( ! strstr( $config["base_url"], "http://" ) && ! strstr( $config["base_url"], "https://" ) ){
				throw new Exception( 'Invalid base_url: ' . plugins_url(), 9 );
			}

			// provider application id ?
			if( get_option( 'wsl_settings_' . $provider . '_app_id' ) ){
				$config["providers"][$provider]["keys"]["id"] = get_option( 'wsl_settings_' . $provider . '_app_id' );
			}

			// provider application key ?
			if( get_option( 'wsl_settings_' . $provider . '_app_key' ) ){
				$config["providers"][$provider]["keys"]["key"] = get_option( 'wsl_settings_' . $provider . '_app_key' );
			}

			// provider application secret ?
			if( get_option( 'wsl_settings_' . $provider . '_app_secret' ) ){
				$config["providers"][$provider]["keys"]["secret"] = get_option( 'wsl_settings_' . $provider . '_app_secret' );
			}

			// reset scope for if facebook
			if( strtolower( $provider ) == "facebook" ){
				$config["providers"][$provider]["scope"]   = "email, user_about_me, user_birthday, user_hometown, user_website"; 
				$config["providers"][$provider]["display"] = "popup";
			}

			// reset scope for if google
			if( strtolower( $provider ) == "google" ){
				$config["providers"][$provider]["scope"]   = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email";  
			}

			// Contacts import
			if( get_option( 'wsl_settings_contacts_import_facebook' ) == 1 && strtolower( $provider ) == "facebook" ){
				$config["providers"][$provider]["scope"]   = "email, user_about_me, user_birthday, user_hometown, user_website, read_friendlists";
			}

			if( get_option( 'wsl_settings_contacts_import_google' ) == 1 && strtolower( $provider ) == "google" ){
				$config["providers"][$provider]["scope"]   = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.google.com/m8/feeds/";
			}

			// create an instance for Hybridauth
			$hybridauth = new Hybrid_Auth( $config );

			// try to authenticate the selected $provider
			$adapter = $hybridauth->authenticate( $provider );

			// further testing
			if( get_option( 'wsl_settings_development_mode_enabled' ) ){
				$profile = $adapter->getUserProfile( $provider );
			}

			if( get_option( 'wsl_settings_use_popup' ) == 1 || ! get_option( 'wsl_settings_use_popup' ) ){
?>
<html>
<head>
<script>
function init() {
	window.opener.wsl_wordpress_social_login({
		'action'   : 'wordpress_social_login',
		'provider' : '<?php echo $provider ?>'
	});

	window.close();
}
</script>
</head>
<body onload="init();">
</body>
</html>
<?php
			}
			elseif( get_option( 'wsl_settings_use_popup' ) == 2 ){
				$redirect_to = site_url();

				if( isset( $_REQUEST[ 'redirect_to' ] ) ){
					$redirect_to = urldecode( $_REQUEST[ 'redirect_to' ] );
				}
?>
<html>
<head>
<script>
function init() {
	document.loginform.submit();
}
</script>
</head>
<body onload="init();"> 
<form name="loginform" method="post" action="<?php echo site_url( 'wp-login.php', 'login_post' ); ?>">
	<input type="hidden" id="redirect_to" name="redirect_to" value="<?php echo $redirect_to ?>"> 
	<input type="hidden" id="provider" name="provider" value="<?php echo $provider ?>"> 
<?php
	if( isset( $_REQUEST["link"] ) && (int) $_REQUEST["link"] ){
?>
	<input type="hidden" id="action" name="action" value="wordpress_social_link">
<?php
	} else {
?>
	<input type="hidden" id="action" name="action" value="wordpress_social_login">
<?php
	}
?>
</form>
</body>
</html> 
<?php
			}
		}
		catch( Exception $e ){
			$message = "Unspecified error!"; 
			$hint    = ""; 

			switch( $e->getCode() ){
				case 0 : $message = "Unspecified error."; break;
				case 1 : $message = "Hybriauth configuration error."; break;
				case 2 : $message = "Provider not properly configured."; break;
				case 3 : $message = "Unknown or disabled provider."; break;
				case 4 : $message = "Missing provider application credentials."; 
						 $hint    = "<b>What does this error mean ?</b>";
						 $hint   .= "<br />Most likely, you didn't setup the correct application credentials for this provider. These credentials are required in order for <b>$provider</b> users to access your website and for WordPress Social Login to work.";
						 $hint   .= '<br />Instructions for use can be found in the <a href="http://hybridauth.sourceforge.net/wsl/configure.html" target="_blank">User Manual</a>.';
				         break;
				case 5 : $message = "Authentification failed. The user has canceled the authentication or the provider refused the connection."; break; 
				case 6 : $message = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again."; 
					     if( is_object( $adapter ) ) $adapter->logout();
					     break;
				case 7 : $message = "User not connected to the provider."; 
					     if( is_object( $adapter ) ) $adapter->logout();
					     break;
				case 8 : $message = "Provider does not support this feature."; break;
				
				case 9 : $message = $e->getMessage(); break;
			}

			@ session_destroy();
?>
<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>-</title>
<style> 
HR {
	width:100%;
	border: 0;
	border-bottom: 1px solid #ccc; 
	padding: 50px;
}
html {
    background: #f9f9f9;
}
#wsl {
	background: #fff;
	color: #333;
	font-family: sans-serif;
	margin: 2em auto;
	padding: 1em 2em;
	-webkit-border-radius: 3px;
	border-radius: 3px;
	border: 1px solid #dfdfdf;
	max-width: 700px;
	font-size: 14px;
}  
</style>
<head>  
<body>
<div id="wsl">
<table width="100%" border="0">
  <tr>
    <td align="center"><br /><img src="../assets/img/alert.png" /></td>
  </tr>
  <tr>
    <td align="center"><br /><h4>Something bad happen!</h4><br /></td> 
  </tr>
  <tr>
    <td align="center">
		<p style="line-height: 20px;padding: 8px;background-color: #FFEBE8;border:1px solid #CC0000;border-radius: 3px;padding: 10px;text-align:center;">
			<?php echo $message ; ?> 
		</p>
	</td> 
  </tr> 
  <?php if( $hint ) { ?>
  <tr>
    <td align="center">
		<p style="line-height: 25px;padding: 8px;border-top:1px solid #ccc;padding: 10px;text-align:left;"> 
			<?php echo $hint ; ?>
		</p>
	</td> 
  </tr> 
  <?php } ?>
  
<?php 
	// Development mode on?
	if( get_option( 'wsl_settings_development_mode_enabled' ) ){
?>
  <tr>
    <td align="center"> 
		<div style="padding: 5px;margin: 5px;background: none repeat scroll 0 0 #F5F5F5;border-radius:3px;">
			<div id="bug_report">
				<form method="post" action="http://hybridauth.sourceforge.net/reports/index.php?product=wp-plugin&v=<?php echo $_SESSION["wsl::plugin"] ?>">
					<table width="90%" border="0">
						<tr>
							<td align="left" valign="top"> 
								<h3>Expection</h3>
								<pre style="width:800px;"><?php print_r( $e ) ?></pre> 

								<hr />

								<h3>HybridAuth</h3>
								<pre style="width:800px;"><?php print_r( array( $config, $hybridauth, $adapter, $profile ) ) ?></pre>  
							</td> 
						</tr> 
						<tr>
							<td align="center" valign="top"> 
								<hr /> 
								&nbsp;<b>This plugin is still on beta</b><br /><br /><b style="color:#cc0000;">But you can make it better by sending the generated error report to the developer!</b>
								<br />
								<br />
								<input type="submit" style="width: 300px;height: 33px;" value="Send the error report" /> 
							</td> 
						</tr>
					</table> 

					<textarea name="report" style="display:none;"><?php echo base64_encode( print_r( array( $e, $config, $hybridauth, $adapter, $profile, $_SERVER ), TRUE ) ) ?></textarea>
				</form> 
				<small>
					Note: This message can be disabled from the plugin settings by setting <b>Development mode</b> to <b>Disabled</b>.
				</small>
			</div>
		</div>
	</td> 
  </tr>
<?php
	} // end Development mode
?>
  
</table> 
</div> 
</body>
</html> 
<?php 
			// diplay error and RIP
			die();
		}
    }
?>