<!-- Edit for the table of contents -->
<!-- Note: In order to get the actual description of the book,
	 you have to go to another link. I'll speak with Sasha and see what 
	 I can do.

	 There might be a way to get the description via other means...
	 let's see -->

<?if (!defined('BASEPATH')) exit('No direct script access allowed')?>
<?$this->template->add_css(path_from_file(__FILE__).'book_list.css')?>
<?

/* Extract the version of the article, much like we did for the description
 */
function getVersionNumber($htmlVal) {
	$checkVal = preg_match('/\<base href=\"(.*)\"\>/', $htmlVal);
	print($checkVal);
	return "A";
}

/* Make the date and time prettier in the table of contents
 */
function changeLookDateTime($dateTimeString) {
	// As of right now, just get rid of the timestamp
	// If more needs to be done, I would like a Day-Month-Year
	// Date Structure (talk to Sasha about this)
	$newDateTimeString = preg_replace('/[0-9]{2}:[0-9]{2}:[0-9]{2}/', '', $dateTimeString);
	return $newDateTimeString;
}

/* Filter the html to get the description
 */
function getDescription($htmlVal) {
	$descArray = array();
	preg_match_all('/\<meta.name="(\w*)".content="(.*)"/', $htmlVal, $descArray);
	$arrayValA = $descArray[0];
	$arrayValB = $arrayValA[0];
	//print_r($arrayValB);
	$finalResult = preg_replace('/<meta name="description" content="/', '', $arrayValB);
	$finalResult = substr($finalResult, 0, strlen($finalResult)-1);
	//print_r($finalResult);
	return $finalResult;
}


/* Filter out the Table Of Contents Page.
 * This will allow my link for the Table of Contents to be shown
 * However, since we're not doing 
 * JP
 */
function filterFunction($a) {
	$trimmedTitle = trim($a->title);
	$trimmedTitle = strtolower($trimmedTitle);
	return ($trimmedTitle != "table of contents");
}
function filterTableOfContents($books) {
	return array_filter($books, 'filterFunction');
}

/* Simple, linear time algorithm to search for
 * an array object with the correct title
 * JP
 */
function checkIfThere($books, $titleToFind) {
	$returnVal = False;
	$titleToFind = trim($titleToFind);
	foreach($books as $bookVal) {
		$trimmedTitle = trim($bookVal->title);
		if (strcmp($titleToFind, $trimmedTitle) == 0) {
			$returnVal = True;
			break;
		}
	}
	return $returnVal;
}


function print_books($books, $is_large=false, $public=false) {
	echo '<ul class="book_icons">';
	foreach ($books as $row) {
		$created       = $row->created;
		$uri 		   = confirm_slash(base_url()).$row->slug;
		// Go to the article page to get the description
		$content = file_get_contents($uri);
		$description = getDescription($content);
		//print("\n");
		//print("Description: ".$description);
		//print(strlen($description));
		$title		   = trim($row->title);
		$book_id       = (int) $row->book_id;
		$thumbnail     = (!empty($row->thumbnail)) ? confirm_slash($row->slug).$row->thumbnail : null;
		$is_live       = ($row->display_in_index) ? true : false;
		if (empty($thumbnail) || !file_exists($thumbnail)) $thumbnail = path_from_file(__FILE__).'default_book_logo.png';
		$authors = array();
		foreach ($row->users as $user) {
			if ($user->relationship!=strtolower('author')) continue;
			if (!$user->list_in_index) continue;
			$authors[] = $user->fullname;
		}
		echo '<li><a href="'.$uri.'"><img class="book_icon'.(($is_large)?'':' small').'" src="'.confirm_base($thumbnail).'" /></a><h4><a href="'.$uri.'">'.$title.'</a></h4>';
		if (count($authors)) {
			$printString = '';
			foreach($authors as $authorVal) {
				if(count($authors) == 1) {
					$printString = $printString.$authorVal;
				} else {
					$printString = $printString.$authorVal.', ';
				}
			}
			$printString = '<strong>'.$printString.'</strong>';
			echo $printString;
			echo "<br />";
		}
		// Add the data appropriately
		if ($public) {
			echo "<strong>Description:</strong> ";
			echo $description;
		}
		//echo $description;
		echo '<br />';
		$dateVal = changeLookDateTime($created);
		$dateVal = '<strong>'.$dateVal.'</strong>';
		echo $dateVal;
		echo '</li>';
	}
	echo '</ul>';
}

?>

<?if (isset($_REQUEST['user_created']) && '1'==$_REQUEST['user_created']): ?>
<div class="saved">
  Thank you for registering your <?=$cover_title?> account
  <a href="<?=$uri?>" style="float:right;">clear</a>
</div>
<? endif ?>
<? if ($this->config->item('index_msg')): ?>
<div class="saved msg"><?=$this->config->item('index_msg')?></div>
<? endif ?>
<?
if(!$login_is_super) {
	foreach ($other_books as $key => $row) {
		$is_live =@ ($row->display_in_index) ? true : false;
	    if(!$is_live){
    	    unset($other_books[$key]);
	    }
	}
}
?>
<div id="other_books"<?=(($login->is_logged_in)?'':' class="wide"')?>>
<?
// Generate the table of contents here
if (count($featured_books) > 0) {
	echo '<h3>'.lang('welcome.featured_books').'</h3>';
	print_books($featured_books);
	echo '<br clear="both" />';
}
?>
<!-- Remove The View All Button, So It Can Be Done Automatically 
	 JP -->

<h3><?=lang('welcome.other_books')?></h3>
<form action="<?=base_url()?>" id="book_list_search">
<div>
<div><input type="text" name="sq" class="generic_text_input" value="<?=(isset($_REQUEST['sq'])?trim(htmlspecialchars($_REQUEST['sq'])):'')?>" /></div>
<div><input type="submit" class="generic_button" value="Search" /></div>
<!--<div><button type="submit" class="generic_button" value="1" name="view_all" >View All</button></div>-->
</div>
</form>


<!-- Generate hidden form so View All is a default. Then 
	 Use Javascript to send the form  style="display:none;"
	 JP -->



<?
if (isset($book_list_search_error)) {
	echo '<p class="error">'.$book_list_search_error.'</p>';
}
?>
<br clear="both" />
<? if (count($other_books) > 0) print_books($other_books, true, true) ?>
</div>

<?
if ($login->is_logged_in) {
	echo '<div id="user_books"><h3>Your Books</h3>';
	$newUserBooks = filterTableOfContents($user_books);
	if (count($newUserBooks) > 0) {
		echo '<ul class="book_icons">';
		print_books($newUserBooks, true);
		/* Generate the link to the Table of Contents
		 * Tested. If there is no Table of Contents 
		 * book created, it won't show (so no one will)
		 * accidently click a link to a nonexistent page
		 * JP
		 */
		/* Since we're not doing a book version of the
		 * Table of Contents book, this is largely unnecessary.
		 * Just in case we want to go back to it, it will only be commented
		 * out
		 * JP
		if (checkIfThere($user_books, "Table Of Contents")) {
			echo '<li><a href="http://dev.upenndigitalscholarship.org/scalar/table-of-contents"><img class="book_icon" src="http://dev.upenndigitalscholarship.org/scalar/system/application/views/modules/book_list/default_book_logo.png"></a>';
			echo '<h4><a href="http://dev.upenndigitalscholarship.org/scalar/table-of-contents"><span data-hypothesis="true" data-auto-approve="true" data-email-authors="true" data-joinable="true">Table Of Contents</span></a></h4></li>';
		} */
	} else {
		echo '<p>You haven\'t created any books yet.</p>';
	}
	echo '</div>';
}
?>
<br clear="both" />

<!-- Try to automatically have things load -->