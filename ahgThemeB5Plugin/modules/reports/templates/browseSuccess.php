<?php decorate_with('layout_3col'); ?>

<?php slot('sidebar'); ?>

  <section id="advanced-search-filters">

    <h4><?php echo __('Reports'); ?></h4>

<div id="add-new-criteria">
 
	Strong Rooms :
	<select name="dropd" id="dropd" onchange="myFunction()">
	  <option value="Select">Select</option>}
	  <option value="Vault 2">Vault 2</option>
	  <option value="Bos">Bos</option>
	  <option value="All">All</option>
	</select> 
</div>
<div id="add-new-criteria">
 
	Location :
	<select name="dropl" id="dropl" onchange="myFunctionLoc()">
	  <option value="Select">Select</option>}
	  <option value="NARSSA floor 2">NARSSA floor 2</option>
	  <option value="NARSSA 2nd floor">NARSSA 2nd floor</option>
	  <option value="All">All</option>
	</select> 
</div>
  
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
function myFunction()
{
	var x=document.getElementById("dropd");
	var cookie_name = 'strongroom';
	var cookie_value = x.value;
	//alert("cookie_name="+cookie_name)
	//alert("cookie_value="+cookie_value)
	create_cookie(cookie_name, cookie_value, 1, "/");	
}

function myFunctionLoc()
{
	var x=document.getElementById("dropl");
	var cookie_name = 'strongroom2';
	var cookie_value = x.value;
	//alert("cookie_name="+cookie_name)
	//alert("cookie_value="+cookie_value)
	create_cookie(cookie_name, cookie_value, 1, "/");	
}


/**
 * Create cookie with javascript
 *
 * @param {string} name cookie name
 * @param {string} value cookie value
 * @param {int} days2expire
 * @param {string} path
 */
function create_cookie(name, value, days2expire, path) {
  var date = new Date();
  date.setTime(date.getTime() + (days2expire * 24 * 60 * 60 * 1000));
  var expires = date.toUTCString();

  document.cookie = name + '=' + value + ';' +
                   'expires=' + expires + ';' +
                   'path=' + path + ';';
   //alert("document.cookie="+document.cookie);
}
</script>
  
    	<a href="<?php echo url_for(['module' => 'physicalobject', 'action' => 'boxLabelCsv']); ?>" class="remove-filter">Search</a>     
    	
    </div>

    <div class="filter">
   		<a href="<?php echo url_for(['module' => 'physicalobject', 'action' => 'boxLabelCsvExport']); ?>" class="remove-filter">Strongrooms Export</a>
    </div>

    <div class="filter">
    	<a href="<?php echo url_for(['module' => 'bookoutobject', 'action' => 'browse']); ?>" class="remove-filter">Booked Out</a>
    </div>

    <div class="filter">
    	<a href="<?php echo url_for(['module' => 'reports', 'action' => 'browsePublish']); ?>" class="remove-filter">Publish</a>
    </div>

	<!--NARSSA / Plain Sailing remove
    <div class="filter">
   		<a href="<?php echo url_for(['module' => 'physicalobject', 'action' => 'pdf']); ?>" class="remove-filter">pdf Creator</a>
    </div-->

  </section>

<?php end_slot(); ?>
