<?php
//echo '<pre>';
//echo print_r($this->account_info);
//echo '</pre>';

echo '<pre>'; echo $this->result;	echo '</pre>';	//отладочный результат
echo '<pre>'; echo $this->success;	echo '</pre>';
?>



<?php	
	//&& !isset($_GET['code'])
	if (isset($_POST['formId']['sendRequest']) ) { // если была нажата кнопка - выполняем какие-то действия
		unset($_POST['formId']['sendRequest']);
		TestController::authorizeYM();
	} else {									 // если нет - покажем небольшую форму с кнопкой
	?>
		<form action="<?php //echo $_SERVER['PHP_SELF'];?>" method="post" id="formId">
			<input type="submit" name="formId[sendRequest]" value="Оплатить"/>
		</form>
<?php 
	} ?>



