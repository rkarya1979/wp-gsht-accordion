<?php
/*
Plugin Name: Google Spreadsheet to Accordion
Description: A wordpress plugin to display google spreadsheet into the accordion format.
Version: 1.0
Author: Rahul Kumar and Gulshan Naz
Author URI: http://www.indianbusybees.com/
License: GPL
Copyright: IndianBusyBees
*/


/* include css and js files*/
function wp_gsht_register_plugin_styles() {
	wp_register_style( 'wp-gsht-style', plugins_url('css/style.css', __FILE__));
	wp_enqueue_style( 'wp-gsht-style' );
}
add_action( 'wp_enqueue_scripts', 'wp_gsht_register_plugin_styles' );

function wp_gsht_register_plugin_js() {
	wp_register_script('wp-gsht-custom-js', plugins_url('js/wp-gsht-accordion-js.js', __FILE__ ), array('jquery-ui-accordion'), '', true);
	wp_enqueue_script('wp-gsht-custom-js');
}
add_action( 'wp_footer', 'wp_gsht_register_plugin_js' );


function wp_gsht_admin_styles() {
     wp_register_script('__gsht_admin__', plugins_url('js/gsht-admin.js', __FILE__), '', '', true);
	 wp_enqueue_script('__gsht_admin__');
	wp_register_style( 'wp-gsht-admin-style', plugins_url('css/style-admin.css', __FILE__));
	wp_enqueue_style( 'wp-gsht-admin-style' );
}

add_action('admin_footer', 'wp_gsht_admin_styles');

/* shortcode function*/
function wp_gsht_create_accordion_shortcode($atts)
{
	extract(shortcode_atts(array(
      'url' => "", // Google Spreadsheet url
      'title' => 'column1', //use + between columns to marge
      'content' => 'column2', //use + between columns to marge
      'start' => '1', // Starting row. sometime first row may be heading so start with 2. 
      'total' => '-1', // -1 for all
	  'location' => 'Sheet1', // location is the bottom tab of sheet, default is Sheet1 
	), $atts));
	
	
	$google_spreadsheet_ID = wp_gsht_get_sheetId($url);
    if($google_spreadsheet_ID=="") {
		$str = "Please provide a valid Google Spreadsheet url.";
	} else {
		//$API = 'AIzaSyAxiEuJVD_F_gAhxQOwjGzifWh7AbU0IvE';
		$API = get_option('wp_gsht_key');
		if($API=="") {
			$str = "Please provide a google api key. Go to Wp admin > Settings > Google keys.";
		} else {
			$api_key = esc_attr( $API);
			$get_cell = new WP_Http();
			
			//$cell_url = "https://sheets.googleapis.com/v4/spreadsheets/$google_spreadsheet_ID/values/$location?&key=$api_key";
			$cell_url = "https://sheets.googleapis.com/v4/spreadsheets/$google_spreadsheet_ID?includeGridData=true&fields=sheets%2Fdata%2FrowData%2Fvalues%2FuserEnteredValue&key=$api_key";
			
			$location = str_replace("Sheet","",$location);
			$location = $location-1;
			$cell_response = $get_cell->get( $cell_url);
			$json_body = json_decode($cell_response['body'],true);	
			
			if (@isset($json_body["sheets"][$location]["data"][0]["rowData"]))
			  $data = $json_body["sheets"][$location]["data"][0]["rowData"];
			else
				$data ="";

			if($data=="") {
			  $str = "No data to display";
			} else {

			$total_rows = count($data);

			$col_for_title = wp_gsht_get_columns($title);
			$col_for_content = wp_gsht_get_columns($content);
			
			if ($total=="-1")
				$total = $total_rows+1;
			//echo $total;		   
			if ($total_rows>0) {
				$str = '<div class="gsht-accordion">';
				foreach($data as $k=>$col_values) {
					if (($k+1)>=$start && (($k+1)<=($start+$total-1))) {
					   $title_data = '';
					   $content_data = '';
					   foreach($col_values["values"] as $key=>$value){
						   if (@isset($value["userEnteredValue"])) {
							   $filter_value = wp_gsht_filter_value($value["userEnteredValue"]);
							   if (count($col_for_title)>0) {
									foreach($col_for_title as $i=>$j) {
										if ($j==$key) {
										  $title_data .= do_shortcode($filter_value) ." ";
										}
									}
							   }
							   if (count($col_for_content)>0) {
									foreach($col_for_content as $i=>$j) {
										if ($j==$key) {
										  $content_data .= do_shortcode($filter_value) ." ";
										}
									}
							   }						   
						   }
					   }
					   $str .= "<h3><a href=''>".$title_data."</a></h3><div>".$content_data."</div>";
					   // end accordion
					}
				}
				$str .= "</div>";
			} else {
				$str = "No data to display.";
			}	
		  }
		}
	}
	return $str;
}
add_shortcode('wp_gsht', 'wp_gsht_create_accordion_shortcode');


function wp_gsht_get_sheetId($url) {
	if($url!="") {
		preg_match('~\/d\/(.*?)\/edit~', $url, $matches);
		if(sizeof($matches)>0)
			return $matches[1];
	}
}

function wp_gsht_get_columns($val) {
  $tcols = @explode("+",$val);
  if(count($tcols)>0) {
	foreach($tcols as $k=>$v) {
		$col[] = str_replace("column","",$v)-1;
	}
  }
  return $col;
}

function wp_gsht_filter_value($value) {
	if (@isset($value["numberValue"])){
		return $value["numberValue"];
    }
	if (@isset($value["stringValue"])) {
		return $value["stringValue"];
	}
	if (@isset($value["formulaValue"])){
		$str = $value["formulaValue"];
		$replaced = preg_replace_callback("~=IMAGE\((.*)\)~", function ($m){return getgshtIMAGE($m[1]);},$str);
		return $replaced;
	}
}

function getgshtIMAGE($url) {
	$str = @explode(",",$url);
	if(!@isset($str[0]))
		return '';

	if(@isset($str[1])&& $str[1]=="4")
		return "<img src='".trim($str[0],'"')."' width='".trim($str[3])."' height='".trim($str[2])."' />";
	else
		return "<img src='".trim($str[0],'"')."' class='img'/>";
}


/*create setting*/
function wp_gsht_register_settings() {
   add_option( 'wp_gsht_key', '');
   register_setting( 'wp_gsht_options_group', 'wp_gsht_key', 'wp_gsht_callback' );
}
add_action( 'admin_init', 'wp_gsht_register_settings' );



function wp_gsht_register_options_page() {
  add_options_page('Google Keys', 'Google Keys', 'manage_options', 'wp_gsht', 'wp_gsht_options_page');
}
add_action('admin_menu', 'wp_gsht_register_options_page');


function wp_gsht_options_page()
{
?>
  <div>
  <?php screen_icon(); ?>
  <h2>Google Keys</h2>
  <form method="post" action="options.php">
  <?php settings_fields( 'wp_gsht_options_group' ); ?>
  <h3>To use the google spreadsheet as the accordion, please provide google api key.</h3>
  <p>You can create api key from <a href="https://console.developers.google.com/" target="_blank">https://console.developers.google.com/</a>. Please make sure you Enable the Sheets API.</p>
  <table>
  <tr valign="top">
  <th scope="row"><label for="wp_gsht_option_name">API Key</label></th>
  <td><input type="text" id="wp_gsht_key" name="wp_gsht_key" value="<?php echo get_option('wp_gsht_key'); ?>" size="50"/></td>
  </tr>
  </table>
  <?php  submit_button(); ?>
  </form>
  <h3>How to use:</h3>
  <p>Use [wp_gsht url="URL of the google spreadsheet"] shortcode.</p>     
  <p>url = url of the google spreadsheet. It is required.</p>
  <p>title = Column number for the accordion title. Use + between columns to marge. Default is column1</p>
  <p>content = Column number for the accordion content. Use + between columns to marge. Default is column2</p>
  <p>start = Starting row. sometime first row may be heading so start with 2. Default is 1.</p>
  <p>total = total number of rows to display as accordion. Default is -1 for all.</p>
  <p>location = Every Google spreadsheet is made up of "sub" sheets. These are tabs at the bottom, and each has a name. By default, they're named "Sheet1", "Sheet2" etc. Default is 'Sheet1'.</p>
  </div>
<?php
}





// Media button

add_action('admin_footer', 'wp_gsht_doin_mce_popup' );
add_action('media_buttons', 'wp_gsht_media_button_wizard', 11);

function wp_gsht_media_button_wizard()
{
	?>
	<style>
		#TB_ajaxContent {width:96%!important;}
        #TB_window #TB_ajaxContent p {margin-top:2px; padding-top:2px;}
		#TB_window #TB_ajaxContent p label{font-weight:bold;}
    </style>
	<a href="#TB_inline?width=680&height=500&inlineId=wp_gsht_doin_div_shortcode" class="thickbox button gsht_media_link" id="wp_gsht_add_div_shortcode" title="Google Spreadsheet to Accordion"><span></span> GSAccordion</a>
   <?php      
}

function wp_gsht_doin_mce_popup() {
  ?>
  <script type="text/javascript">
      function InsertContainerGSHT() {
		var url = jQuery('#acc_url').val();
		var start = jQuery('#acc_start').val();
		var total = jQuery('#acc_total').val();
		var title = jQuery('#acc_title').val();
		var content = jQuery('#acc_content').val();
		var loc = jQuery('#acc_location').val();
		var str = '[wp_gsht url="'+url+'" start="'+start+'" total="'+total+'" title="'+title+'" content="'+content+'" location="'+loc+'"]';
		window.send_to_editor(str);		
    }        
  </script>
  <div id="wp_gsht_doin_div_shortcode" style="display:none;">
  <div class="wrap wp_doin_shortcode" style="width:100%;">
  <form method="post" id="WizardForm" name="WizardForm">
  <h2>Google Spreadsheet to Accordion</h2>
  <p>
	<label for=""><?php _e( 'Google Spreadsheet Url:' ); ?></label> 
	<input class="widefat" id="acc_url" name="acc_url" type="text" value="" />
	</p>
	<p>
	<label for=""><?php _e( 'Sheet column for accordion title:' ); ?></label>
	<input class="widefat" id="acc_title" name="acc_title" type="text" value="column1" /> <small>use + between to marge (column1+column2)</small>
	</p>
	<p>
	<label for=""><?php _e( 'Sheet column for accordion content:' ); ?></label>
	<input class="widefat" id="acc_content" name="acc_content" type="text" value="column2" /> <small>use + between to marge (column1+column2)</small>
	</p>
	<p>
	<label for=""><?php _e( 'Sheet row to start data:' ); ?></label>
	<input class="widefat" id="acc_start" name="acc_start" type="text" value="1" /> <small>some time first row have titles</small>
	</p>
	<p>
	<label for=""><?php _e( 'Sheet total rows to get:' ); ?></label>
	<input class="widefat" id="acc_total" name="acc_total" type="text" value="-1" /> <small>total rows to display. -1 for all.</small>
	</p>
	<p>
	<label for=""><?php _e( 'Sheet location:' ); ?></label>
	<input class="widefat" id="acc_location" name="acc_location" type="text" value="Sheet1" /> <small>Default is 'Sheet1'</small>
	</p>
	<div style="padding:15px;">
		<input type="button" class="button-primary" value="Submit" onclick="InsertContainerGSHT();"/>&nbsp;&nbsp;&nbsp;
		<a class="button" href="#" onclick="tb_remove();return false;">Cancel</a>
	</div>
  </form>
  </div>
  </div>
  <?php
}


function gsht_block_init() {
	 if ( ! function_exists( 'register_block_type' ) ) {
		  return;
	 }
	 $dir = dirname( __FILE__ );
	 $index_js = 'gsht-block-js.js';
	 wp_register_script('gsht-block-editor',plugins_url( "js/".$index_js, __FILE__ ),array(		   'wp-blocks', 'wp-i18n', 'wp-element'), '');
	 wp_localize_script('gsht-block-editor', 'Gsht', array('pluginsUrl' => plugins_url()."/".basename($dir)));

	 register_block_type( 'google-sheet/accordion', array(
		  'editor_script' => 'gsht-block-editor',
		  'attributes' => array(
		   'acc_url' => array('type' => 'string'),
		   'acc_title' => array('type' => 'string'),
		   'acc_content' => array('type' => 'string'),
		   'acc_start' => array('type' => 'string'),
		   'acc_total' => array('type' => 'string'),
		   'acc_location' => array('type' => 'string')
		  ),
		  'render_callback' => 'gsht_render_block_callback'
		 ) 
	  );
}
add_action( 'init', 'gsht_block_init' );


function gsht_render_block_callback($attributes, $content)
{
	if(isset($attributes['acc_url']))
		$acc_url = $attributes['acc_url'];
	else
		$acc_url = "";
	if(isset($attributes['acc_title']))
		$acc_title = $attributes['acc_title'];
	else
		$acc_title = "column1";
	if(isset($attributes['acc_content']))
		$acc_content = $attributes['acc_content'];
	else
		$acc_content = "column2";
	if(isset($attributes['acc_start']))
		$acc_start = $attributes['acc_start'];
	else
		$acc_start = "1";
	if(isset($attributes['acc_total']))
		$acc_total = $attributes['acc_total'];
	else
		$acc_total = "-1";
	if(isset($attributes['acc_location']))
		$acc_loction = $attributes['acc_location'];
	else
		$acc_location = "Sheet1";
	
	$code = '[wp_gsht url="'.$acc_url.'" start="'.$acc_start.'" total="'.$acc_total.'" title="'.$acc_title.'" content="'.$acc_content.'" location="'.$acc_location.'"]';

	if(isset($attributes['acc_url'])) {
		if ($attributes && $attributes['acc_url'] && strpos($code, '[') === 0)
		{
			 return do_shortcode($code);
		}
	}
    return $code;
}


// Register and load the widget
function wp_gsht_load_widget() {
    register_widget( 'wp_gsht_widget' );
}
add_action( 'widgets_init', 'wp_gsht_load_widget' );
 
// Creating the widget 
class wp_gsht_widget extends WP_Widget {
 
	function __construct() {
		parent::__construct(
		// Base ID of your widget
		'wp_gsht_widget', __('GoogleSheet to Accordion Widget'), array( 'description' => __( 'A wordpress widget to display google Spreadsheet to accordion.'), ) 
		);
	}
	 

	// Creating widget front-end
	public function widget( $args, $instance ) {
		
		$title = "";
		$acc_url = "";
		$acc_title = "colum1";
		$acc_content = "colum2";;
		$acc_start = "1";;
		$acc_total = "-1";;
		$acc_location = "Sheet1";;
		 
		if(isset($instance['title']))
		$title = apply_filters( 'widget_title', $instance['title'] );
		if(isset($instance['acc_url']))
		$acc_url = apply_filters( 'widget_title', $instance['acc_url'] );
		if(isset($instance['acc_title']))
		$acc_title = apply_filters( 'widget_title', $instance['acc_title'] );
		if(isset($instance['acc_content']))
		$acc_content = apply_filters( 'widget_title', $instance['acc_content'] );
		if(isset($instance['acc_start']))
		$acc_start = apply_filters( 'widget_title', $instance['acc_start'] );
		if(isset($instance['acc_total']))
		$acc_total = apply_filters( 'widget_title', $instance['acc_total'] );
		if(isset($instance['acc_location']))
		$acc_location = apply_filters( 'widget_title', $instance['acc_location'] );
		 
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];
		
		$code = '[wp_gsht url="'.$acc_url.'" start="'.$acc_start.'" total="'.$acc_total.'" title="'.$acc_title.'" content="'.$acc_content.'" location="'.$acc_location.'"]'; 
		
		echo do_shortcode($code);
		
		echo $args['after_widget'];
	}
	 
	// Widget Backend 
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		if ( isset( $instance[ 'acc_url' ] ) ) {
			$acc_url = $instance[ 'acc_url' ];
		}
		if ( isset( $instance[ 'acc_title' ] ) ) {
			$acc_title = $instance[ 'acc_title' ];
		} else {
			$acc_title = 'column1';
		}
		if ( isset( $instance[ 'acc_content' ] ) ) {
			$acc_content = $instance[ 'acc_content' ];
		} else {
			$acc_content = 'column2';
		}
		if ( isset( $instance[ 'acc_start' ] ) ) {
			$acc_start = $instance[ 'acc_start' ];
		} else {
			$acc_start = '1';
		}
		if ( isset( $instance[ 'acc_total' ] ) ) {
			$acc_total = $instance[ 'acc_total' ];
		} else {
			$acc_total = '-1';
		}
		if ( isset( $instance[ 'acc_location' ] ) ) {
			$acc_location = $instance[ 'acc_location' ];
		} else {
			$acc_location = 'Sheet1';
		}
		// Widget admin form

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'acc_url' ); ?>"><?php _e( 'Google Spreadsheet Url:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'acc_url' ); ?>" name="<?php echo $this->get_field_name( 'acc_url' ); ?>" type="text" value="<?php echo esc_attr( $acc_url ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'acc_title' ); ?>"><?php _e( 'Sheet column for accordion title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'acc_title' ); ?>" name="<?php echo $this->get_field_name( 'acc_title' ); ?>" type="text" value="<?php echo esc_attr( $acc_title ); ?>" /> <small>use + between to marge (column1+column2)</small>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'acc_content' ); ?>"><?php _e( 'Sheet column for accordion content:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'acc_content' ); ?>" name="<?php echo $this->get_field_name( 'acc_content' ); ?>" type="text" value="<?php echo esc_attr( $acc_content ); ?>" /> <small>use + between to marge (column1+column2)</small>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'acc_start' ); ?>"><?php _e( 'Sheet row to start data:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'acc_start' ); ?>" name="<?php echo $this->get_field_name( 'acc_start' ); ?>" type="text" value="<?php echo esc_attr( $acc_start ); ?>" /> <small>some time first row have titles</small>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'acc_total' ); ?>"><?php _e( 'Sheet total rows to get:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'acc_total' ); ?>" name="<?php echo $this->get_field_name( 'acc_total' ); ?>" type="text" value="<?php echo esc_attr( $acc_total ); ?>" /> <small>total rows to display. -1 for all.</small>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'acc_location' ); ?>"><?php _e( 'Sheet location:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'acc_location' ); ?>" name="<?php echo $this->get_field_name( 'acc_location' ); ?>" type="text" value="<?php echo esc_attr( $acc_location ); ?>" /> <small>Default is 'Sheet1'</small>
		</p>
		<?php 
	}
	
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['acc_url'] = ( ! empty( $new_instance['acc_url'] ) ) ? strip_tags( $new_instance['acc_url'] ) : '';
		$instance['acc_title'] = ( ! empty( $new_instance['acc_title'] ) ) ? strip_tags( $new_instance['acc_title'] ) : '';
		$instance['acc_content'] = ( ! empty( $new_instance['acc_content'] ) ) ? strip_tags( $new_instance['acc_content'] ) : '';
		$instance['acc_start'] = ( ! empty( $new_instance['acc_start'] ) ) ? strip_tags( $new_instance['acc_start'] ) : '';
		$instance['acc_total'] = ( ! empty( $new_instance['acc_total'] ) ) ? strip_tags( $new_instance['acc_total'] ) : '';
		$instance['acc_location'] = ( ! empty( $new_instance['acc_location'] ) ) ? strip_tags( $new_instance['acc_location'] ) : '';
		
		return $instance;
	}
} // Class wpb_widget ends here

?>