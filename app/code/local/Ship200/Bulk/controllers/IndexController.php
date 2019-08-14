<?php



class Ship200_Bulk_IndexController extends Mage_Core_Controller_Front_Action

{

	

	public function bulkprocessAction(){

		

		  $secret_key = Mage::getStoreConfig('bulk/info/appkey');
		   $order_status_import = Mage::getStoreConfig('bulk/info/order_status_import');
		   $order_status_tracking = Mage::getStoreConfig('bulk/info/order_status_tracking');
		   // $notify_customer_setting = Mage::getStoreConfig('bulk/info/notify_customer');
		    $notify_customer_setting = 1;
			if($notify_customer_setting == "1"){
					 $notifycustomer="true";
			}else{
				 $notifycustomer="false";
			}
			
		 

			if($secret_key == ""){ echo "The Secret Key was never setup. Please refer to read_me file"; exit;}
			
			if($order_status_import == ""){ echo "Please Select Order Status From Admin for Order Import"; exit;}
			if($order_status_tracking == ""){ echo "Please Select Order Status From Admin for Update With Tracking"; exit;}

	#Extra security
	// Check that request is coming from Ship200 Server
	$allowed_servers = file_get_contents('http://www.ship200.com/instructions/allowed_servers.txt');
	$servers_array = explode(",",$allowed_servers);

	$server = 0;
	foreach($servers_array as $ip){
		if($_SERVER['REMOTE_ADDR'] == $ip){$server = 1;}
	}
	if($server == 0){ echo "Incorrect Server"; exit;}
	// Check that request is coming from Ship200 Server		
		
		

		if ($_POST['id'] == $secret_key){
			

			 $fields = array(
		
		
		
				//			Ship200 field name				your db field name
		
				//			cannot be changed			can be changed (see examples below)
		
								"Orderid"				=>	"increment_id", // that will be used 'keyForUpdate' to update the tracking number back in the backend
		
								"Order_Date"			=>	"created_at",
		
								"Order_Status"			=>	"status",
		
		
		
								"Name"					=>	"firstname",
		
								"Company_Name"			=>	"company",
		
								"Address_Line1"			=>	"street",						
		
								"City"					=>	"city",
		
								"State"					=>	"region", // 2 Letter State, example: NY, LA etc..
		
								"Zip"					=>	"postcode",
		
								"Country"				=>	"country_id",
		
								"Phone"					=>	"telephone",
		
								"Email"					=>	"customer_email",
		
		
		
								"Weight"				=>	"weight", #any numeric(int) value
		
								//"Weight_Units"			=>	array(""=>"manual"), #valid values are "lb" or "oz"
		
								"Ship_Method"			=>	"shipping_description",
		
								//"Subtotal"			=>	"base_subtotal",
		
								//"Shipping"			=>	"base_shipping_amount",
		
								//"Total"			=>	"base_grand_total",
		
		
		
								"items"				=>	array("1"=>"items"),
		
		
		
								//"Name_On_Invoice"	=> 	array(str_replace("http://", "", HTTP_SERVER)=>"manual")
		
							); 
		




////////////////////////////////  Do Not Change Anything Below this Line  /////////////////////////////////////////////////	

  

				require_once("app/Mage.php");

				$app = Mage::app('');
				
				
				
				$salesModel=Mage::getModel("sales/order");
				
				$salesCollection = $salesModel->getCollection()->addFieldToFilter('status', $order_status_import);
				
				
				
				 
				
				
				
				foreach($salesCollection as $order)
				
				{
				
					$shipping_address = $order->getShippingAddress();
					//var_dump($shipping_address);
					//exit;
				
					$items=$order->getAllItems();
				
					
				
					$out .= "<order>\n";
				
					
				
						foreach($fields as $key => $value){
				
						
				
							if (is_array($value)){
				
								
				
								$man = "";
				
												foreach($value as $key2 => $value2){ /// if field is array
				
														
				
														if ($value2 == "items"){
															unset($totals);unset($item_title_array);unset($items_qty_array);unset($items_price_array);unset($items_sku_array);
															
															
				
															foreach($items as $item){
																
																$item_title_array[] = $this->clear($item->getName());
																$items_qty_array[] = $this->clear(round($item->getQtyOrdered()),2);
																$items_price_array[] = $this->clear(round($item->getPrice()),2);
																$items_sku_array[] = $this->clear($item->getSku());
															}	
															
															//titles
															$man .= "\n<title><![CDATA[".json_encode($item_title_array)."]]></title>\n";
															//itemid
															$man .= "\n<itemid>".json_encode($items_sku_array)."</itemid>\n";
															//qty
															$man .= "\n<qty>".json_encode($items_qty_array)."</qty>\n";
															//price
															$man .= "\n<price>".json_encode($items_price_array)."</price>\n";
																									
				
															$man .= "\n<subtotal>".$this->clear($order->getBaseSubtotal())."</subtotal>";
															
															$man .= "\n<shipping>".$this->clear($order->getBaseShippingAmount())."</shipping>";
															
															$man .= "\n<total>".$this->clear($order->getBaseGrandTotal())."</total>";
															
															$man .= "\n<discount>".$this->clear($order->getBaseDiscountAmount())."</discount>";
															
															
				
														}
				
												}
				
												$out .= "<$key>".$man."</$key>\n";
				
								
				
							}else if($value==""){
				
								$out .= "<$key></$key>\n";
				
							}else{
				
								
				
								if($this->clear($order->getData($value))==""){
				
										
				
									$out .= "<$key>".$this->clear($shipping_address->getData($value))."</$key>\n";
				
								}else{
									
									if($value=="status"){
										$out .= "<$key>".$this->clear($order_status_import)."</$key>\n";
									}else{
									
										$out .= "<$key>".$this->clear($order->getData($value))."</$key>\n";
									}
								}
				
							}
							
							
				
							
				
						}
				
						
				
					$out .= "</order>\n";  
				
					 
				
				}
				
					
				
							header("Content-type: text/xml; charset=utf-8"); 
				
						echo "<?xml version='1.0' encoding='ISO-8859-1'?>
				
								<orders>
				
								$out
				
								</orders>";
								exit;
				
								
			
					
				
	 //////////////// Update Tracking ///////////////////////  
				
	}elseif($_POST['update_tracking'] == "1" && $_POST['secret_key'] == $secret_key && $server == 1){
	
					$order=Mage::getModel("sales/order")->loadByIncrementId($_POST['keyForUpdate']);
						
					if($order->canShip()){

						$arrTracking = array(
						'carrier_code' => strtolower($_POST['carrier']),
						'title' => $_POST[service]." - (Ship200 Bulk)",
						'number' => $_POST['tracking'],
						);	
						
						$itemQty =  $order->getItemsCollection()->count();
						$shipment = $order->prepareShipment();
						if($shipment){								
							$track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
							$shipment->addTrack($track);
							$shipment->register();					
							$transactionSave = Mage::getModel('core/resource_transaction')
							->addObject($shipment)
							->addObject($shipment->getOrder())
							->save();
									}
							
					}	

					$order->setData('state', $order_status_tracking);
					$order->setStatus($order_status_tracking);
					$comment = addslashes($_POST['carrier'])." tracking#: ".addslashes($_POST['tracking']);
					$history = $order->addStatusHistoryComment($comment, false);
					$history->setIsCustomerNotified($notifycustomer);
					$order->save();
					

		}else{
		// Not valid request //////
		echo "Error: 1094";
		exit;
	}
	

		

		

	}

	

	public function clear ($value="") {

	

				$value = preg_replace("'<style[^>]*>.*</style>'siU",'',$value);

				$value = strip_tags($value);	

				$value = str_replace("<", "&lt;", $value);

				$value = str_replace("&", "&amp;", $value);	

				$value = str_replace(",", " ", $value);	

				$value = str_replace("\n", " ", $value);	

				$value = str_replace("\r", " ", $value);	

				$value = str_replace("	", " ", $value);	

				$value = str_replace("®", " ", $value);	

			

			    $value = preg_replace('/\<[^\>]+\>/', '', $value);

				#$value = urldecode(html_entity_decode($value));

				$value = preg_replace('/[\r\n]+/', '', $value);

				$value = preg_replace('/[\s]+$/', '', $value);

				$value = preg_replace('/[\s\t]+\xA0$/', '', $value);



				$value = substr($value, 0, 10000);

				

				$value = addslashes($value);



		return $value;

	}

	

	

}



?>