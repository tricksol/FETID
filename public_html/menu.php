<?php
include('phplib/prepend.php');

if ($export_results) {
        page_open(array("sess"=>"fetid_Session","auth"=>"fetid_Auth","perm"=>"fetid_Perm","silent"=>"silent"));
} else {
	page_open(array("sess"=>"fetid_Session","auth"=>"fetid_Auth","perm"=>"fetid_Perm"));
	#if ($Field) include("pophead.ihtml"); else include("head.ihtml");
	echo "<h1>Menu</h1>";
	#if (empty($Field)) include("menu.html");
}
check_view_perms();


$f = new menuform;


if ($WithSelected) {
        check_edit_perms();
        switch ($WithSelected) {
                case "Delete":
			if (array_search('menu',$_ENV['no_edit'])) {
				echo "No Delete Allowed";
			} else {
                        	$sql = "DELETE FROM menu WHERE id IN (";
                        	$sql .= implode(",",$id);
                        	$sql .= ")";
                        	if ($dev) echo "<h1>$sql</h1>";
                        	$db->query($sql);
                        	echo $db->affected_rows()." deleted.";
		    	}
                        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"10; URL=".$sess->self_url()."\">";
                        break;
                case "Print";
                        foreach ($id as $row) {
				echo "<div class='float_left'>\n";
                                $f = new menuform;
                                $f->find_values($row);
                                $f->freeze();
                                $f->display();
				echo "\n</div>\n";
                        }
			echo "\n<br style='clear: both;'>\n";
                        break;
        }
        echo "&nbsp<a href=\"".$sess->self_url();
        echo "\">Back to menu.</a><br>\n";
        page_close();
        exit;
}

if ($submit) {
  switch ($submit) {
   case "Copy": $id="";
   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<font class='bigTextBold'>$cmd Menu</font>\n";
        $f->reload_values();
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $id = $f->save_values();
        if ($Field) {
                $text = $_POST["AddressLine1"].", ".$_POST["AddressLine2"].", ".$_POST["AddressLine3"].", ".$_POST["City"];
?><script>
if (window.opener) {
        window.opener.addOption("<?php echo $Field; ?>","<?php echo $text; ?>","<?php echo $id; ?>");
        window.close();
}
</script><?php
        }
        echo "<b>Done!</b><br />\n";
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to menu.</a><br />\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to menu.</a><br />\n";
        page_close();
        exit;
   case "Delete":
    if (isset($auth)) {
        check_edit_perms();
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br />\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to menu.</a><br />\n";
        page_close();
        exit;
   default:
	include("search.php");
  }
} else {
    if ($id) {
	$f->find_values($id);
    } else {
	include("search.php");
    }
}


if ($export_results) $f->setup();
else $f->javascript();


switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Copy":
	if ($cmd=="Copy") $id="";
    case "Edit":
	echo "<font class='bigTextBold'>$cmd Menu</font>\n";
	$f->display();
	if ($orig_cmd=="View") $f->showChildRecords();
	break;
    default:
	$cmd="Query";
	$t = new menuTable;
	$t->heading = 'on';
	$t->sortable = 'on';
	$t->trust_the_data = false;   /* if true, send raw data without htmlspecialchars */
	$t->limit = 100; 	 /* max length of field data before trucation and add ... */
	$t->add_extra = 'on';   /* or set to base url of php file to link to, defaults to PHP_SELF */
    #   $t->add_extra = "SomeFile.php";                           # use defaults, but point to a different target file.
    #   $t->add_extra = array("View","Edit","Copy","Delete");     # just specify the command names.
    #   $t->add_extra = array(                                    # or specify parameters as well.
    #                      "View" => array("target"=>"PayPal.php","key"=>"id","perm"=>"admin","display"=>"view","class"=>"ae_view"),
    #                      );
	$t->add_total = 'on';   /* add a grand total row to the bottom of the table on the numberic columns */
	$t->add_insert = $f->classname;  /* Add a blank row ontop of table allowing insert or search */
	$t->add_insert_buttons = 'Search';   /* Control which buttons appear on the add_insert row eg: Add,Search */
	/* See below - EditMode can also be turned on/off by user if section below uncommented */
	#$t->edit = $f->classname;   /* Allow rows to be editable with a save button that appears onchange */
	#$t->ipe_table = 'menu';   /* Make in place editing changes immediate without a save button */
	#$t->checkbox_menu = Array('Print');
	#$t->check = 'id';  /* Display a column of checkboxes with value of key field*/

	$db = new DB_fetid;

        if (!$export_results) echo "<a href=\"".$sess->self_url().$sess->add_query(array("cmd"=>"Add"))."\">Add</a> Menu\n";


        if (array_key_exists("menu_fields",$_REQUEST)) $menu_fields = $_REQUEST["menu_fields"];
        if (empty($menu_fields)) {
                $menu_fields = array_first_chunk($t->default,7,11);
                $sess->register("menu_fields");
        }
	if (in_array(@$LocField,$menu_fields)) displayLocSelect($f->classname,$LocField);
        
        $t->fields = $menu_fields;
		
	#$t->extra_html = array('fieldname'=>'extrahtml');
	#$t->align      = array('fieldname'=>'right', 'otherfield'=>'center'); 	

        if (!$export_results) {
          echo "Export to ";
          echo "&nbsp;<input name='ExportTo' type=radio onclick=\"javascript:export_results('Excel2007');\">Excel 2007";

          echo "<br>";

          echo "<a href=javascript:show('ColumnSelector')>Column Chooser</a> ";
          echo "<form id=ColumnSelector method='post' style=display:none>\n";
          echo "<a href=javascript:hide('ColumnSelector')>Hide</a>";
          echo " Columns: <br />";
          foreach ($t->all_fields as $field) {
                if (in_array($field,$menu_fields,TRUE)) $chk = "checked='checked'"; else $chk="";
                echo "\n<input type='checkbox' $chk name=menu_fields[] value='$field' />$field <br />";
          }
          echo "\n<input type=submit name=setcols value='Set' />";
          if ($sess->have_edit_perm()) {
            if ($EditMode=='on') {
                $on='checked="checked"'; $off='';
		$t->edit = 'menuform';   
		# $t->ipe_table = 'menu';   #uncomment this for immediate table update (no save button)
            } else {
                $off='checked="checked"'; $on='';
            }
            echo "\n<br />\nEdit Mode <input type='radio' name='EditMode' value='on' $on> On <input type='radio' name='EditMode' value='off' $off /> Off ";
          } else {
            $EditMode='';
          }
          echo "\n</form>\n";
	}

  // When we hit this page the first time,
  // there is no $q.
  if (!isset($q_menu)) {
    $q_menu = new menu_Sql_Query;     // We make one
    $q_menu->conditions = 1;     // ... with a single condition (at first)
    $q_menu->translate  = "on";  // ... column names are to be translated
    $q_menu->container  = "on";  // ... with a nice container table
    $q_menu->variable   = "on";  // ... # of conditions is variable
    $q_menu->lang       = "en";  // ... in English, please
    $q_menu->extra_cond = "";  
    $q_menu->default_query = "1";  
    $q_menu->default_sortorder = "id desc";  

    $sess->register("q_menu");   // and don't forget this!
  }

  if ($rowcount) {
        $q_menu->start_row = $startingwith;
        $q_menu->row_count = $rowcount;
  } else {
        $startingwith = $q_menu->start_row;
        $rowcount = $q_menu->row_count;
  }

  if ($submit=='Search') $query = $f->search();   // create sql query from form posted values.

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (array_key_exists("x",$_POST)) {
    get_request_values("x");
    $query = $q_menu->where("x", 1);
    $hideQuery = "";
  } else {
    $hideQuery = "style='display:none'";
  }

  if ($Format = $export_results) {
        $custom_query = array_key_exists("custom_query",$_POST) ? $_POST["custom_query"] : "";

        require_once "/usr/share/pear/PHPExcel/PHPExcel.php";

        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        $locale = 'en_us';
        $validLocale = PHPExcel_Settings::setLocale($locale);

        $workbook = new PHPExcel();
        $workbook->setActiveSheetIndex(0);
        $worksheet1 = $workbook->getActiveSheet();
        $worksheet1->setTitle('menu');

        $cols = count($t->fields);
        $range = "A1:" . chr(64+$cols) . "1";
        $worksheet1->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_DARKGREEN);
        $worksheet1->getStyle($range)->getAlignment()->setHorizontal('center');
        $worksheet1->getStyle($range)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);

        $r = 1;
        $col = "A";
        foreach ($t->fields as $field) {
                if (!isset($f->form_data->elements[$field]["ob"])) {var_dump($f->form_data->elements[$field]); exit; }
                $el = $f->form_data->elements[$field]["ob"];
                if (!$size=@$el->size) {
                        $size = 5;
                        if (!isset($el->options)) {
                                $size = strlen($el->value);
                        } else
                        foreach($el->options as $option) {
				if (is_array($option)) $len=strlen($option["label"]);
                                else $len = strlen($option);
                                if ($len>$size) $size = $len;
                        }
                }
                $worksheet1->getColumnDimension($col)->setWidth($size);
                $worksheet1->getCell("$col$r")->setValue($t->map_cols[$field]);
                $col++;
        }

        $sql = "SELECT * FROM menu $custom_query WHERE $query";
        $db->query($sql);
        while ($db->next_record()) {
                $r++;
                $col = "A";
                foreach ($t->fields as $field) {
                        $worksheet1->getCell("$col$r")->setValue($db->f($field));
                        $col++;
                }
        }

        $ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

        header("Content-Type: $ContentType");
        header("Content-Disposition: attachment;filename=\"menu.xlsx\"");
        header("Cache-Control: max-age=0");

        $objWriter = PHPExcel_IOFactory::createWriter($workbook, $Format);
        $objWriter->save('php://output');
        exit;
  }


  if (empty($sortorder)) $sortorder = empty($q_menu->last_sortorder) ? $q_menu->default_sortorder : $q_menu->last_sortorder ;
  if (empty($query))   $query     = empty($q_menu->last_query)     ? $q_menu->default_query     : $q_menu->last_query ;

  $q_menu->last_query = $query;
  $q_menu->last_sortorder = $sortorder;
/*
  $db->query("SELECT COUNT(*) as total from ".$db->qi("menu")." where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_menu->start_row - $q_menu->row_count))
      { $q_menu->start_row = $db->f("total") - $q_menu->row_count; }
*/ 
  if ($q_menu->start_row < 0) { $q_menu->start_row = 0; }

#  $f->sort_function_maps = array(  /* use a function to sort values for specified fields */
#      "ip_addr"=>"inet_aton",  
#      );

  if (strpos(strtolower($query),"order by")===false) {
	if ($so=$f->order_by($sortorder)) $query .= " order by ".$so;
  }

  $query .= " LIMIT ".$q_menu->start_row.",".$q_menu->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  echo "<a href=javascript:show('customQuery')>Custom Query</a>";
  echo "\n<div id=customQuery $hideQuery><a href=javascript:hide('customQuery')>Hide</a>\n";
  printf($q_menu->form("x", $t->map_cols, "query"));
  echo "\n</div>\n";

  if (array_key_exists("more_0",$x)) {$query="";}
  if (array_key_exists("less_0",$x)) {$query="";}

  // Do we have a valid query string?
  if ($query) {

    // Do that query
    $sql = $t->select($f).$query;
    $db->query($sql);
    #$db->query("select * from ".$db->qi("menu")." where ". $query);

    // Show that condition
    echo "<a href=javascript:show('QueryStats')>Query Stats</a><div id=QueryStats style=display:none>";
    echo "<a href=javascript:hide('QueryStats')>Hide</a><br>";
    printf("Query Condition = %s<br />\n", $query);
    printf("Query Results = %s<br /></div>\n", $db->num_rows());
    echo "<br />";

    // Dump the results (tagged as CSS class default)
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>
