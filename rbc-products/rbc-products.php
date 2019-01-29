<?php

/*
Plugin Name:  RBC Products
Description:  A plugin developed for RBC Group BV for displaying and managing products.
Version:      1.0
Author:       Tom van den Broecke
*/

// Plugin activation hook
register_activation_hook( __FILE__, 'rbcp_activation' );

// Plugin activation function (What to do on activation of plugin)
function rbcp_activation() {
	// Create database table
	jal_install();

	// Create documentation folders
	$upload_dir = wp_upload_dir();
	if (!file_exists($upload_dir['basedir'] . '/docs_en/')) {
		mkdir($upload_dir['basedir'] . '/docs_en/', 0777);
	}
	if (!file_exists($upload_dir['basedir'] . '/docs_nl/')) {
		mkdir($upload_dir['basedir'] . '/docs_nl/', 0777);
	}
}

// Create SQL Table for plugin (storing product information and files)
function jal_install () {
	// Grab WPDB info
	global $wpdb;

	// Set name and charset
	$table_name = $wpdb->prefix . "rbcproducts";
	$charset_collate = $wpdb->get_charset_collate();

	// SQL for creating table with structure
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		product_name VARCHAR(55),
		product_shortcode VARCHAR(55),
		documentation_nl VARCHAR(55),
		documentation_en VARCHAR(55),
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Execute SQL
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

// Register menu function as menu action
add_action('admin_menu', 'rbcproducts_menu');

// Plugin menu function
function rbcproducts_menu() {
	$page_title = "RBC Products";
	$menu_title = "RBC Products";
    // BE AWARE: The "edit_products" capability must be manually added to user roles in wordpress before this can be used
	$capability = "edit_products";
	$menu_slug = "menu_rbcproducts";
	$function = "rbcproducts_options";
	$icon_url = "dashicons-book-alt";
	$position = 1;
	add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
}

// Plugin options HTML
function rbcproducts_options() {
	// Grab WPDB info
	global $wpdb;

	// Set alert array
	$alerts = array();

	// Set search variable
	$current_search = "";

	// Permission check
	if ( !current_user_can( 'edit_products' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Options HTML
	echo '<div class="wrap">';
	echo '<h1>Products</h1>';

	// ------- FORM HANDLING
	// Add product
	if (isset($_POST['product_add'])) {
		$table_name = $wpdb->prefix . "rbcproducts";
		$insert_data = array('product_name'=>$_POST['add_p_name'], 'product_shortcode'=>$_POST['add_p_shortcode']);

		// Error check var
		$upload = true;

		if (isset($_FILES['add_p_doc_nl']) && $_FILES['add_p_doc_nl']['size'] > 0){
			// Set starting data
			$language = "nl";
			$file = $_FILES['add_p_doc_nl'];
			$target_dir = "";
			$target_file = "";
			$uploadOk = 1;
			if ($language == "en") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_en/';
				$target_file = $target_dir . basename($file["name"]);
			} else if ($language == "nl") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_nl/';
				$target_file = $target_dir . basename($file["name"]);
			}
			$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

			// Check if file already exists
			if (file_exists($target_file)) {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file is already used with another product. (Does this product already exist?)", "error"));
				$uploadOk = 0;
			}

			// Check file size
			if ($file["size"] > 50000000) {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file too big, file was not uploaded.", "error"));
				$uploadOk = 0;
			}

			// Allow certain file formats
			if ($fileType != "pdf") {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not a PDF, please only upload PDF files.", "error"));
				$uploadOk = 0;
			}

			// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
				$upload = false;
			// if everything is ok, try to upload file
			} else {
				if (move_uploaded_file($file["tmp_name"], $target_file)) {
					array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was successfully uploaded.", "success"));
					$insert_data['documentation_' . $language] = basename($file["name"]);
				} else {
					array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not uploaded, there has been an error.", "error"));
				}
			}
		}

		if (isset($_FILES['add_p_doc_en']) && $_FILES['add_p_doc_en']['size'] > 0){
			// Set starting data
			$language = "en";
			$file = $_FILES['add_p_doc_en'];
			$target_dir = "";
			$target_file = "";
			$uploadOk = 1;
			if ($language == "en") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_en/';
				$target_file = $target_dir . basename($file["name"]);
			} else if ($language == "nl") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_nl/';
				$target_file = $target_dir . basename($file["name"]);
			}
			$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

			// Check if file already exists
			if (file_exists($target_file)) {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file is already used with another product. (Does this product already exist?)", "error"));
				$uploadOk = 0;
			}

			// Check file size
			if ($file["size"] > 50000000) {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file too big, file was not uploaded.", "error"));
				$uploadOk = 0;
			}

			// Allow certain file formats
			if ($fileType != "pdf") {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not a PDF, please only upload PDF files.", "error"));
				$uploadOk = 0;
			}

			// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
				$upload = false;
			// if everything is ok, try to upload file
			} else {
				if (move_uploaded_file($file["tmp_name"], $target_file)) {
					array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was successfully uploaded.", "success"));
					$insert_data['documentation_' . $language] = basename($file["name"]);
				} else {
					array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not uploaded, there has been an error.", "error"));
				}
			}
		}

		if ($upload == true) {
			$db_upload_result = $wpdb->insert($table_name, $insert_data);
			if ($db_upload_result == false) {
				array_push($alerts, new Alert("Product could not be uploaded to the database.", "error"));
			} else {
				array_push($alerts, new Alert("Product was successfully uploaded to the database.", "success"));
			}
		}
	}

	// Edit product
	if (isset($_POST['product_edit_apply'])) {
		$table_name = $wpdb->prefix . "rbcproducts";
		$insert_data = array();

		// Grab current database data
		$db_results = $wpdb->get_results( "SELECT * FROM $table_name WHERE id = " . $_POST['hidden_product_id_apply'] . "" );
		// Check for name change
		if ($db_results[0]->product_name != $_POST['p_name']) {
			$insert_data['product_name'] = $_POST['p_name'];
		}
		// Check for shortcode change
		if ($db_results[0]->product_name != $_POST['p_shortcode']) {
			$insert_data['product_shortcode'] = $_POST['p_shortcode'];
		}
		// Check for NL doc change
		$nl_doc_overwritten = false;
		if (isset($_FILES['p_doc_nl']) && $_FILES['p_doc_nl']['size'] > 0) {
			// Set starting data
			$language = "nl";
			$file = $_FILES['p_doc_nl'];
			$target_dir = "";
			$target_file = "";
			$uploadOk = 1;
			if ($language == "en") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_en/';
				$target_file = $target_dir . basename($file["name"]);
			} else if ($language == "nl") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_nl/';
				$target_file = $target_dir . basename($file["name"]);
			}
			$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

			// Check file size
			if ($file["size"] > 50000000) {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file too big, file was not uploaded.", "error"));
				$uploadOk = 0;
			}

			// Allow certain file formats
			if ($fileType != "pdf") {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not a PDF, please only upload PDF files.", "error"));
				$uploadOk = 0;
			}

			// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
				$upload = false;
			// if everything is ok, try to upload file
			} else {
				$old_file_handled = false;
				if ($db_results[0]->documentation_nl != NULL) {
					$old_file = $target_dir . $db_results[0]->documentation_nl;
					if (file_exists($old_file)) {
						if (unlink($old_file)) {
							$old_file_handled = true;
						} else {
						array_push($alerts, new Alert("The old " . strtoupper($language) . " documentation file could not be removed. Cannot delete product.", "error"));
						}
					} else {
						$old_file_handled = true;
					}
				} else {
					$old_file_handled = true;
				}

				if ($old_file_handled == true) {
					if (move_uploaded_file($file["tmp_name"], $target_file)) {
						array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was successfully uploaded.", "success"));
						$insert_data['documentation_' . $language] = basename($file["name"]);
						$nl_doc_overwritten = true;
					} else {
						array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not uploaded, there has been an error.", "error"));
					}
				}
			}
		} else {
			$nl_doc_overwritten = true;
		}
		// Check for EN doc change
		$en_doc_overwritten = false;
		if (isset($_FILES['p_doc_en']) && $_FILES['p_doc_en']['size'] > 0) {
			// Set starting data
			$language = "en";
			$file = $_FILES['p_doc_en'];
			$target_dir = "";
			$target_file = "";
			$uploadOk = 1;
			if ($language == "en") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_en/';
				$target_file = $target_dir . basename($file["name"]);
			} else if ($language == "nl") {
				$upload_dir = wp_upload_dir();
				$target_dir = $upload_dir['basedir'] . '/docs_nl/';
				$target_file = $target_dir . basename($file["name"]);
			}
			$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

			// Check file size
			if ($file["size"] > 50000000) {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file too big, file was not uploaded.", "error"));
				$uploadOk = 0;
			}

			// Allow certain file formats
			if ($fileType != "pdf") {
				array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not a PDF, please only upload PDF files.", "error"));
				$uploadOk = 0;
			}

			// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
				$upload = false;
			// if everything is ok, try to upload file
			} else {
				$old_file_handled = false;
				if ($db_results[0]->documentation_en != NULL) {
					$old_file = $target_dir . $db_results[0]->documentation_en;
					if (file_exists($old_file)) {
						if (unlink($old_file)) {
							$old_file_handled = true;
						} else {
						array_push($alerts, new Alert("The old " . strtoupper($language) . " documentation file could not be removed. Cannot delete product.", "error"));
						}
					} else {
						$old_file_handled = true;
					}
				} else {
					$old_file_handled = true;
				}

				if ($old_file_handled == true) {
					if (move_uploaded_file($file["tmp_name"], $target_file)) {
						array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was successfully uploaded.", "success"));
						$insert_data['documentation_' . $language] = basename($file["name"]);
						$en_doc_overwritten = true;
					} else {
						array_push($alerts, new Alert("The " . strtoupper($language) . " documentation file was not uploaded, there has been an error.", "error"));
					}
				}
			}
		} else {
			$en_doc_overwritten = true;
		}

		if ($nl_doc_overwritten AND $en_doc_overwritten) {
			$update_result = $wpdb->update($table_name, $insert_data, array( 'id' => $_POST['hidden_product_id_apply'] ));
			if ($update_result == false) {
				array_push($alerts, new Alert("Could not update product.", "error"));
			} else {
				array_push($alerts, new Alert("Product updated successfully.", "success"));
			}
		}
	}

	// Remove product
	if (isset($_POST['hidden_product_id_remove'])) {
		$table_name = $wpdb->prefix . "rbcproducts";

		$db_results = $wpdb->get_results( "SELECT * FROM $table_name WHERE id = " . $_POST['hidden_product_id_remove'] . "" );
		$remove_from_db = true;
		if ($db_results[0]->documentation_en != NULL) {
			$upload_dir = wp_upload_dir();
			$target_dir = $upload_dir['basedir'] . '/docs_en/';
			$target_file = $target_dir . $db_results[0]->documentation_en;
			if (!unlink($target_file)) {
				$remove_from_db = false;
			}
		}
		if ($db_results[0]->documentation_nl != NULL) {
			$upload_dir = wp_upload_dir();
			$target_dir = $upload_dir['basedir'] . '/docs_nl/';
			$target_file = $target_dir . $db_results[0]->documentation_nl;
			if (!unlink($target_file)) {
				$remove_from_db = false;
			}
		}

		if ($remove_from_db) {
			$db_remove = $wpdb->delete($table_name, array('id' => $_POST['hidden_product_id_remove']));

			// Alert messages
			if ($db_remove == false) {
				// COULD NOT BE REMOVED
				array_push($alerts, new Alert("Error: Product could not be removed.", "error"));
			} elseif ($db_remove == 0) {
				// 0 PRODUCTS HAVE BEEN REMOVED
				array_push($alerts, new Alert("0 Products have been removed.", "warning"));
			} elseif ($db_remove == 1) {
				// 1 PRODUCT HAS BEEN REMOVED
				array_push($alerts, new Alert("$db_remove Product has been removed.", "success"));
			} else {
				// X PRODUCTS HAVE BEEN REMOVED
				array_push($alerts, new Alert("$db_remove Products have been removed.", "success"));
			}
		} else {
			array_push($alerts, new Alert("Product documentation file(s) could not be removed from the server.", "error"));
		}
	}

	// Search product
	if (isset($_POST['search_filter'])) {
		$current_search = $_POST['search_input'];
	}

	// Clear search
	if (isset($_POST['clear_search'])) {
		$current_search = "";
	}

	// ------- DISPLAY PRODUCTS
	// Alert messages
	foreach ($alerts as $alert) {
		createAlert($alert);
	}

	// Top control bar
	echo '<div style="margin-bottom: 5px; padding: 5px; background: white; box-shadow: 3px 3px 5px 0px rgba(140,140,140,1); border-radius: 5px; overflow: auto;">';
	echo '<div style="overflow: hidden;">';
	echo '<div style="float: left;">';
	echo '<form style="display: inline-block; float: left;" method="post" action="">';
	if ($current_search == "") {
		echo '<input style="margin: 5px;" class="regular-text" type="search" placeholder="Search..." name="search_input" required>';
	} else {
		echo '<input style="margin: 5px;" class="regular-text" type="search" placeholder="Search..." value="' . $current_search . '" name="search_input" required>';
	}
	echo '<input style="margin: 5px;" class="button button-primary" type="submit" name="search_filter" value="Search">';
	echo '</form>';
	echo '<form style="display: inline-block; float: left;" method="post" action="">';
	echo '<input style="margin: 5px; float: left;" class="button" type="submit" name="clear_search" value="Clear">';
	echo '</form>';
	echo '</div>';
	echo '<button style="float: right; margin: 5px;" class="button button-primary" onclick="toggle_add_product()">Add Product</button>';
	echo '</div>';

	// Add product
	echo '<div id="add_product" style="clear: both; display: none; margin: 5px; border-top: 1px solid black; margin-top: 10px; overflow: auto;">';
	echo '<table style="width: 99%; text-align: center; ">';
	echo '<thead>';
	echo '<tr>';
		echo '<th><p style="margin-bottom: -7px; margin-top: 13px;">Product Name</p></th>';
		echo '<th><p style="margin-bottom: -7px; margin-top: 13px;">Product Shortcode</p></th>';
		echo '<th><p style="margin-bottom: -7px; margin-top: 13px;">Documentation EN</p></th>';
		echo '<th><p style="margin-bottom: -7px; margin-top: 13px;">Documentation NL</p></th>';
		echo '<th></th>';
	echo'</tr>';
	echo '</thead>';

	echo '<tr>';
	echo '<form name="product_add" method="post" action="" enctype="multipart/form-data">';
	echo '<td style="width=23%; padding: 0px;"><input class="regular-text" style="max-width: 250px;" placeholder="Product Name" type="text" name="add_p_name" required></td>';
	echo '<td style="width=23%; padding: 0px;"><input class="regular-text" style="max-width: 250px;" placeholder="Product Shortcode" type="text" name="add_p_shortcode" required></td>';
	echo '<td style="width=23%; padding: 0px;"><input class="regular-text" type="file" name="add_p_doc_en" accept="file_extension"></td>';
	echo '<td style="width=23%; padding: 0px;"><input class="regular-text" type="file" name="add_p_doc_nl" accept="file_extension"></td>';
	echo '<td style="width:8%; padding: 0px;"><input style="margin: 5px; width: 100%;" class="button button-primary" type="submit" name="product_add" value="ADD"></input></form>';
	echo '</table>';
	echo'</tr>';

	echo '</div>';
	echo '</div>';

	// Javascript
	echo '<script>
		function toggle_add_product() {
			if (document.getElementById("add_product").style.display == "none") {
				document.getElementById("add_product").style.display = "block";
			} else if (document.getElementById("add_product").style.display == "block") {
				document.getElementById("add_product").style.display = "none";
			}
		} 
	</script>';

	// Products database table
	echo '<div style="overflow: auto; border-radius: 5px; box-shadow: 3px 3px 5px 0px rgba(140,140,140,1);">';
	echo '<table style="width:100%; background-color: white; padding: 10px; text-align: center;">';

	// Head
	echo '<thead>';
	echo '<tr>';
		echo '<th style="border-bottom: 1px solid rgba(60,60,60,1); padding-bottom: 10px;">Product Name</th>';
		echo '<th style="border-bottom: 1px solid rgba(60,60,60,1); padding-bottom: 10px;">Product Shortcode</th>';
		echo '<th style="border-bottom: 1px solid rgba(60,60,60,1); padding-bottom: 10px;">Documentation EN</th>';
		echo '<th style="border-bottom: 1px solid rgba(60,60,60,1); padding-bottom: 10px;">Documentation NL</th>';
		echo '<th style="border-bottom: 1px solid rgba(60,60,60,1); padding-bottom: 10px;"></th>';
	echo'</tr>';
	echo '</thead>';

	// Body
	echo '<tbody style="padding-top: 200px;">';

	// Grab database results
	$table_name = $wpdb->prefix . "rbcproducts";
	if ($current_search == "") {
		$db_results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY product_name ASC" );
	} else {
		$db_results = $wpdb->get_results( "SELECT * FROM $table_name WHERE product_name LIKE '%$current_search%' ORDER BY product_name ASC" );
	}

	// Populate table with results
	if (count($db_results) == 0) {
		echo '<tr><td colspan="5"><p style="background: lightgray; padding-top: 20px; padding-bottom: 20px; width: 100%; text-align: center;">No products found.</p></td></tr>';
	}
	foreach ( $db_results as $product ) {
		// -------- VIEW ROW
		echo '<tr id="table_view_product_' . $product->id . '" style="background-color: rgba(180, 180, 180, 0.6);">';
		echo '<td style="width=23%; height: 58px;">' . $product->product_name . '</td>';
		echo '<td style="width=23%; height: 58px;">' . $product->product_shortcode . '</td>';

		if ($product->documentation_en != NULL) {
			echo '<td style="width=23%; height: 58px;"><a title="EN PDF" download="' . $product->product_name . '.pdf" href="' . wp_upload_dir()['baseurl'] . '/docs_en/' . $product->documentation_en . '" target="_blank">EN PDF</a></td>';
		} else {
			echo '<td style="width=23%; height: 58px;">No Documentation</td>';
		}

		if ($product->documentation_nl != NULL) {
			echo '<td style="width=23%; height: 58px;"><a title="NL PDF" download="' . $product->product_name . '.pdf" href="' . wp_upload_dir()['baseurl'] . '/docs_nl/' . $product->documentation_nl . '" target="_blank">NL PDF</a></td>';
		} else {
			echo '<td style="width=23%; height: 58px;">No Documentation</td>';
		}

		echo '<td style="width:8%; height: 58px;"><button style="margin: 5px;" class="button edit_btn" onclick="edit_entry_' . $product->id . '()">EDIT</button></td>';

		// Javascript
		echo '<script>
			function edit_entry_' . $product->id . '() {
				var buttons = document.getElementsByClassName("edit_btn");
				for (var i = 0; i < buttons.length; ++i) {
					var item = buttons[i];
					item.disabled = true;
				}

				document.getElementById("table_view_product_' . $product->id . '").style.display = "none";
				document.getElementById("table_edit_product_' . $product->id . '").style.display = "table-row";
			} 
		</script>';

		echo '</tr>';

		// -------- EDIT ROW
		echo '<tr style="background-color: rgba(0, 148, 255, 0.5); display: none;" id="table_edit_product_' . $product->id . '">';

		echo '<form name="product_edit" method="post" action="" enctype="multipart/form-data">';
		echo '<td style="width=23%; height: 58px;"><input class="regular-text" style="max-width: 250px;" type="text" name="p_name" value="' . $product->product_name . '"></td>';
		echo '<td style="width=23%; height: 58px;"><input class="regular-text" style="max-width: 250px;" type="text" name="p_shortcode" value="' . $product->product_shortcode . '"></td>';
		echo '<td style="width=23%; height: 58px;"><input class="regular-text" type="file" name="p_doc_en" accept="file_extension"></td>';
		echo '<td style="width=23%; height: 58px;"><input class="regular-text" type="file" name="p_doc_nl" accept="file_extension"></td>';
		echo '<input type="hidden" name="hidden_product_id_apply" value="' . $product->id . '">';
		echo '<td style="width:8%; height: 58px;"><input style="margin: 5px;" class="button button-primary" type="submit" name="product_edit_apply" value="APPLY"></input></form>
			<form id="product_remove_form" name="product_remove" method="post" action="">
				<button style="margin: 5px; background: red; color: white; border-color: red;" class="button" onclick="return remove_product_prompt' . $product->id . '()">REMOVE</button> <input type="hidden" name="hidden_product_id_remove" value="' . $product->id . '"> </form>
			<button style="margin: 5px;" class="button" style="height: 29px;" onclick="cancel_edit_' . $product->id . '()">CANCEL</button></td>';

		// Javascript
		echo '<script>
			function remove_product_prompt' . $product->id . '() {
				if (confirm("Are you sure you want to remove this product: ' . $product->product_name . '?")) {
					document.getElementById("product_remove_form").submit();
				} else {
					return false;
				}
			}

			function cancel_edit_' . $product->id . '() {
				var buttons = document.getElementsByClassName("edit_btn");
				for (var i = 0; i < buttons.length; ++i) {
					var item = buttons[i];
					item.disabled = false;
				}

				document.getElementById("table_view_product_' . $product->id . '").style.display = "table-row";
				document.getElementById("table_edit_product_' . $product->id . '").style.display = "none";
			} 
		</script>';

		echo '</tr>';
		echo '</div>';
	}

	echo '</tbody>';

	echo '</table>';
	echo '</div>';

	echo '</div>';
}

// Product shortcode function
function product_shortcode($atts) {
	// Set shortcode defaults
	$attributes = shortcode_atts( array(
		'shortcode' => 'mikrogel',
		'language' => 'en',
	), $atts );

	// Grab WPDB info
	global $wpdb;

	// Grab products from database
	$table_name = $wpdb->prefix . "rbcproducts";
	$db_results = $wpdb->get_results( "SELECT * FROM $table_name WHERE product_shortcode = '" . $attributes['shortcode'] . "'" );

	// Select correct product
	$target_product = NULL;
	foreach ($db_results as $product) {
		if ($attributes['shortcode'] == $product->product_shortcode) {
			$target_product = $product;
		}
	}

	// Output correct product link
	if ($target_product != NULL) {
		if ($attributes['language'] == 'en') {
			if ($target_product->documentation_en != NULL) {
				return '<a href="' . wp_upload_dir()['baseurl'] . '/docs_en/' . $target_product->documentation_en . '" target="_blank">' . $target_product->product_name . '</a>';
			} else {
				return '<a href="https://rbcgroupbv.com/product-page-not-available/" target="_blank">' . $target_product->product_name . '</a>';
			}
		} else if ($attributes['language'] == 'nl') {
			if ($target_product->documentation_nl != NULL) {
				return '<a href="' . wp_upload_dir()['baseurl'] . '/docs_en/' . $target_product->documentation_nl . '" target="_blank">' . $target_product->product_name . '</a>';
			} else {
				return '<a href="https://rbcgroupbv.com/product-page-not-available/" target="_blank">' . $target_product->product_name . '</a>';
			}
		} else {
			return "<p>Unknown product language given.</p>";
		}
	} else {
		return "<p>Unknown product shortcode given.</p>";
	}

	return "Product = {$products['name']}";
}
add_shortcode( 'product', 'product_shortcode' );

class Alert {
	public $message;
	public $type;

	public function __construct($m, $t) {
		$this->message = $m;
		$this->type = $t;
	}
}

function createAlert(Alert $alert) {
	if ($alert->type == "success") {
		echo '<div style="width: 100%; background: lightgreen; border: 1px solid darkgreen; border-radius: 5px; margin-bottom: 15px; box-shadow: 3px 3px 5px 0px rgba(140,140,140,1);">';
		echo '<p style="padding: 10px;">' . $alert->message . '</p>';
		echo '</div>';
	} elseif ($alert->type == "warning") {
		echo '<div style="width: 100%; background: goldenrod; border: 1px solid darkgoldenrod; border-radius: 5px; margin-bottom: 15px; box-shadow: 3px 3px 5px 0px rgba(140,140,140,1);">';
		echo '<p style="padding: 10px;">' . $alert->message . '</p>';
		echo '</div>';
	} elseif ($alert->type == "error") {
		echo '<div style="width: 100%; background: red; border: 1px solid darkred; border-radius: 5px; margin-bottom: 15px; box-shadow: 3px 3px 5px 0px rgba(140,140,140,1);">';
		echo '<p style="padding: 10px; color: white;">' . $alert->message . '</p>';
		echo '</div>';
	}
}

?>