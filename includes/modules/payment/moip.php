<?php
  class osC_Payment_moip extends osC_Payment {
    var $_title,
        $_code = 'moip',
        $_author_name = 'Wics Tecnologia',
        $_status = false,
		$enderecoPost,
		$chaveAcesso,
		$urlRetorno,
        $_sort_order;

    function osC_Payment_moip() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;

      $this->_title = $osC_Language->get('payment_moip_title');
      $this->_method_title = $osC_Language->get('payment_moip_method_title');
      $this->_description = $osC_Language->get('payment_moip_description');
      $this->_status = (defined('MODULE_PAYMENT_MOIP_STATUS') && (MODULE_PAYMENT_MOIP_STATUS == '1') ? true : false);
      $this->_sort_order = (defined('MODULE_PAYMENT_MOIP_SORT_ORDER') ? MODULE_PAYMENT_MOIP_SORT_ORDER : null);
      $this->urlRetorno = MODULE_PAYMENT_MOIP_URL_RETORNO;
	  $this->chaveAcesso  = MODULE_PAYMENT_MOIP_CHAVE;
	  $this->token  = MODULE_PAYMENT_MOIP_CHAVE;
	  
      $this->form_action_url = "https://www.moip.com.br/PagamentoMoIP.do";
      $this->apc_url = "https://www.moip.com.br/PagamentoMoIP.do";
	  
	      
      if ($this->_status === true) {
        $this->order_status = MODULE_PAYMENT_MOIP_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MOIP_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;
        
        if ((int)MODULE_PAYMENT_MOIP_ZONE > 0) {
          $check_flag = false;

          $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
          $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
          $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_MOIP_ZONE);
          $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
          $Qcheck->execute();

          while ($Qcheck->next()) {
            if ($Qcheck->valueInt('zone_id') < 1) {
              $check_flag = true;
              break;
            } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {
              $check_flag = true;
              break;
            }
          }

          if ($check_flag == false) {
            $this->_status = false;
          }
        }
      }
    }

    function selection() {
      global $order;
      $shipping_cost = $order->info['shipping_cost'];
      $fields = array();
      $fields[] = array('title' => 'Pagamento realizado pelo MoIP',
                        'text'  => "Finalize seu pagamento no site seguro do MoIP, e proteja-se de fraudes.");
      $fields[] = array('title' => 'O MoIP dispõe de:',
                        'text'  => '');
      $fields[] = array('title' => '<img src="http://www.moip.com.br/imgs/banner_5_1.jpg">',
                        'text'  => '');
      if (MODULE_PAYMENT_MOIP_SHIPPING=="") { // C&#225;lculo do frete pelo PagSeguro habilitado
          $tipoFretes = array (array('id' => 'EN',
                                     'text' => 'PAC'),
                               array('id' => 'SD',
                                     'text' => 'Sedex'));
          if (($shipping_cost>0)&&false) { // desativado enquanto n&#195;&#163;o aceita escolha caso a caso.
              $fields[] = array('title' => "&nbsp;&nbsp;C&#195;&#161;lculo de frete padr&#195;&#163;o",
                                'field' => osc_draw_radio_field("calcfrete", "LOJA", TRUE));
              $fields[] = array('title' => "&nbsp;&nbsp;C&#195;&#161;lculo de frete pelo MoIP",
                                'field' => osc_draw_radio_field("calcfrete", "MOIP"));
          } else {
              $fields[] = array('title' => "&nbsp;&nbsp;C&#195;&#161;lculo de frete pelo MoIP",
                                'field' => osc_draw_hidden_field("calcfrete", "MOIP", true));
          }
          $fields[] = array('title' => '&nbsp;&nbsp;&nbsp;&nbsp;Tipo de frete',
                            'field' => osc_draw_pull_down_menu('TipoFrete', $tipoFretes));
      }
      $selection = array('id' => $this->code,
                         'module' => $this->title,
                         'fields' => $fields);
      return $selection;
    }
    
    function pre_confirmation_check() {
		return false;
    }
    
    function confirmation() {
      global $cartID, $cart_moip_ID, $customer_id, $languages_id, $order, $order_total_modules, $insert_id;
      global $osc_Database;
      $confirmation = array('title' => $this->title . ': ',
                            'fields' => array(array('title' => MODULE_PAYMENT_MOIP_TEXT_OUTSIDE,
                                                    'field' => "")));
      if (tep_session_is_registered('cartID')) {
        $insert_order = false;
        if (tep_session_is_registered('cart_moip_ID')) {
          $order_id = substr($cart_moip_ID, strpos($cart_moip_ID, '-')+1);
          $curr_check = $osC_Database->query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
          if (($curr_check->value('currency') != $order->info['currency']) || ($cartID != substr($cart_moip_ID, 0, strlen($cartID)))) {
            $check_query = $osC_Database->query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');
            if ($check_query->numberOfRows() < 1) {
              $osC_Database->simpleQuery('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
              $osC_Database->simpleQuery('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
              $osC_Database->simpleQuery('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
              $osC_Database->simpleQuery('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
              $osC_Database->simpleQuery('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
              $osC_Database->simpleQuery('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
            }
            $insert_order = true;
          }
        } else {
          $insert_order = true;
        }
        if ($insert_order == true) {
          $order_totals = array();
          if (is_array($order_total_modules->modules)) {
            reset($order_total_modules->modules);
            while (list(, $value) = each($order_total_modules->modules)) {
              $class = substr($value, 0, strrpos($value, '.'));
              if ($GLOBALS[$class]->enabled) {
                for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
                  if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                    $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                            'title' => $GLOBALS[$class]->output[$i]['title'],
                                            'text' => $GLOBALS[$class]->output[$i]['text'],
                                            'value' => $GLOBALS[$class]->output[$i]['value'],
                                            'sort_order' => $GLOBALS[$class]->sort_order);
                  }
                }
              }
            }
          }
          $sql_data_array = array('customers_id' => $customer_id,
                                  'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                                  'customers_company' => $order->customer['company'],
                                  'customers_street_address' => $order->customer['street_address'],
                                  'customers_suburb' => $order->customer['suburb'],
                                  'customers_city' => $order->customer['city'],
                                  'customers_postcode' => $order->customer['postcode'],
                                  'customers_state' => $order->customer['state'],
                                  'customers_country' => $order->customer['country']['title'],
                                  'customers_telephone' => $order->customer['telephone'],
                                  'customers_email_address' => $order->customer['email_address'],
                                  'customers_address_format_id' => $order->customer['format_id'],
                                  'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                                  'delivery_company' => $order->delivery['company'],
                                  'delivery_street_address' => $order->delivery['street_address'],
                                  'delivery_suburb' => $order->delivery['suburb'],
                                  'delivery_city' => $order->delivery['city'],
                                  'delivery_postcode' => $order->delivery['postcode'],
                                  'delivery_state' => $order->delivery['state'],
                                  'delivery_country' => $order->delivery['country']['title'],
                                  'delivery_address_format_id' => $order->delivery['format_id'],
                                  'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                                  'billing_company' => $order->billing['company'],
                                  'billing_street_address' => $order->billing['street_address'],
                                  'billing_suburb' => $order->billing['suburb'],
                                  'billing_city' => $order->billing['city'],
                                  'billing_postcode' => $order->billing['postcode'],
                                  'billing_state' => $order->billing['state'],
                                  'billing_country' => $order->billing['country']['title'],
                                  'billing_address_format_id' => $order->billing['format_id'],
                                  'payment_method' => $order->info['payment_method'],
                                  'cc_type' => $order->info['cc_type'],
                                  'cc_owner' => $order->info['cc_owner'],
                                  'cc_number' => $order->info['cc_number'],
                                  'cc_expires' => $order->info['cc_expires'],
                                  'date_purchased' => 'now()',
                                  'orders_status' => $order->info['order_status'],
                                  'currency' => $order->info['currency'],
                                  'currency_value' => $order->info['currency_value']);
          tep_db_perform(TABLE_ORDERS, $sql_data_array);
          $insert_id = tep_db_insert_id();
          for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
            $sql_data_array = array('orders_id' => $insert_id,
                                    'title' => $order_totals[$i]['title'],
                                    'text' => $order_totals[$i]['text'],
                                    'value' => $order_totals[$i]['value'],
                                    'class' => $order_totals[$i]['code'],
                                    'sort_order' => $order_totals[$i]['sort_order']);
            tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
          }
          for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
            $sql_data_array = array('orders_id' => $insert_id,
                                    'products_id' => tep_get_prid($order->products[$i]['id']),
                                    'products_model' => $order->products[$i]['model'],
                                    'products_name' => $order->products[$i]['name'],
                                    'products_price' => $order->products[$i]['price'],
                                    'final_price' => $order->products[$i]['final_price'],
                                    'products_tax' => $order->products[$i]['tax'],
                                    'products_quantity' => $order->products[$i]['qty']);
            tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
            $order_products_id = tep_db_insert_id();
            $attributes_exist = '0';
            if (isset($order->products[$i]['attributes'])) {
              $attributes_exist = '1';
              for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                if (DOWNLOAD_ENABLED == 'true') {
                  $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";
                  $attributes = $osC_Database->query($attributes_query);
                } else {
                  $attributes = $osC_Database->query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                }
                $attributes_values = $attributes->toArray();
                $sql_data_array = array('orders_id' => $insert_id,
                                        'orders_products_id' => $order_products_id,
                                        'products_options' => $attributes_values['products_options_name'],
                                        'products_options_values' => $attributes_values['products_options_values_name'],
                                        'options_values_price' => $attributes_values['options_values_price'],
                                        'price_prefix' => $attributes_values['price_prefix']);
                tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
                if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                  $sql_data_array = array('orders_id' => $insert_id,
                                          'orders_products_id' => $order_products_id,
                                          'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                          'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                          'download_count' => $attributes_values['products_attributes_maxcount']);
                  tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                }
              }
            }
          $cart_moip_ID = $cartID . '-' . $insert_id;
          tep_session_register('cart_moip_ID');
        }
      }
      }
      return $confirmation;
    }

    function process_button() {
      global $osC_ShoppingCart, $osC_Currencies, $osC_Customer, $osC_Tax;
      
     $process_button_string = '';
      if (MODULE_PAYMENT_MOIP_GATEWAY_MODE == 'Producao') {
        $params = array('cod_loja' => MODULE_PAYMENT_MOIP_ID,
		'success_url' => osc_href_link(FILENAME_CHECKOUT, 'process', 'SSL'), 
        'cancel_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'), 
        'declined_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'));
        
      }else if (MODULE_PAYMENT_MOIP_GATEWAY_MODE == 'Teste') {
        $params = array('cod_loja' => '658926',
		'success_url' => osc_href_link(FILENAME_CHECKOUT, 'process', 'SSL'), 
        'cancel_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'), 
        'declined_url' => osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL'));
      }
      
	  $params['token'] = $this->chaveAcesso; 	 
	  $id_pedido = $this->_order_id;
   	  $params['redirect'] = 'true';
	  $params['url_retorno'] = $this->urlRetorno;
	  $params['url_post'] = osc_href_link(FILENAME_CHECKOUT, 'callback&module=moip&id_pedido=' . $id_pedido, 'SSL', false, false, true);
	  $params['resposta']= osc_href_link(FILENAME_CHECKOUT, 'success_url' . $id_pedido, 'SSL', false, false, true); 
      $params['valor'] = $osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $osC_Currencies->getCode());
      $params['id_pedido'] = $this->_order_id;
	  $params['nome'] = $osC_ShoppingCart->getBillingAddress('firstname') . ' ' . $osC_ShoppingCart->getBillingAddress('lastname');
      $params['endereco'] = $osC_ShoppingCart->getBillingAddress('street_address');
      $params['cidade'] = $osC_ShoppingCart->getBillingAddress('city');
	  $params['bairro'] = $osC_ShoppingCart->getBillingAddress('suburb');
	  $params['estado'] = $osC_ShoppingCart->getBillingAddress('state');
	  $replacements = array(" ", ".", ",", "-", ";");
	  $postcode = str_replace($replacements, "", $postcode);$params['cep'] = $osC_ShoppingCart->getBillingAddress('postcode');
      $params['telefone'] = $osC_ShoppingCart->getBillingAddress('telephone_number');
	  $params['cpf'] = $osC_ShoppingCart->getBillingAddress('fax');
      $params['email'] = $osC_Customer->getEmailAddress('email_address');
	  $params['complemento'] = $osC_ShoppingCart->getBillingAddress('shipping_comments');
	  $params['free'] = $osC_ShoppingCart->getBillingAddress('payment_comments'); 
      
      if ($osC_ShoppingCart->hasShippingAddress()) {
        $params['nome'] = $osC_ShoppingCart->getShippingAddress('firstname') . ' ' . $osC_ShoppingCart->getShippingAddress('lastname');
        $params['endereco'] = $osC_ShoppingCart->getShippingAddress('street_address');
        $params['cep'] = $osC_ShoppingCart->getShippingAddress('postcode');
		
      }else {
        $params['nome'] = $params['billing_fullname'];
        $params['endereco'] = $params['billing_address'];
        $params['cep'] = $params['billing_postcode'];
      }
      $products_description = array();
      if ($osC_ShoppingCart->hasContents()) {
        $products = $osC_ShoppingCart->getProducts();
        
        foreach($products as $product) {
          $product_name = $product['quantity'] . 'x : ' . $product['name']. '  R$ ' . $product['price'] . '<br/>';          
          if ($osC_ShoppingCart->hasVariants($product['id'])) {
            foreach ($osC_ShoppingCart->getVariants($product['id']) as $variant) {
              $product_name .= ' - ' . $variant['groups_name'] . ': ' . $variant['values_name']. $variant['values_price'] . '<br/>';
            }
          }
          
          $products_description[] = $product_name;
        }
        
        $params['produto'] = implode( '           <br/>    ' ,$products_description);
      }
      
      
      foreach($params as $key => $value) {
        $process_button_string .= osc_draw_hidden_field($key, $value);
      }
      
      return $process_button_string;
    }
    
   function callback() {
      global $osC_Database, $osC_Currencies;
      
      foreach ($_POST as $key => $value) {
        $post_string .= $key . '=' . urlencode($value) . '&';
      } 
      
      $post_string = substr($post_string, 0, -0);
      
      $this->_transaction_response = $this->sendTransactionToGateway($this->apc_url, $post_string);
		
	$token = $this->chaveAcesso;
	$enderecoPost = "https://www.pagamentodigital.com.br/checkout/verify/";
    $this->_transaction_response = 'VERIFICADO';
	$resposta = osc_href_link(FILENAME_CHECKOUT, 'success_url' . $id_pedido, 'SSL', false, false, true);

      switch ($_POST['cod_status']) {
        case '1':
          $transaction_type = 'Transação pagamento efetuado';
          break;
        case '2':
          $transaction_type = 'Transação cancelada';
          break;
        case '0':
        default:
          $transaction_type = 'Transação pendente, aguardando pagamento';
          break;
      }
	 
       $comments_retorno = 'Pagamento Digital | Pedido Valor total: R$' . sprintf("%01.2f", $_POST["valor_original"]) . "\n" . ' Recebido em  ' . $_POST['data_transacao'] . "\n" . ' Transacao ID:' . $_POST['id_transacao'] . "\n" . ' Situação '. $transaction_type . '.';
	   $comments_venda = ' Cliente :  ' . urlencode(nl2br($_POST['cliente_nome'])) .  "\n" .' CPF : ' . $_POST['cliente_cpf'] . "\n" .' Email : ' . $_POST['cliente_email'] . "\n" .'  .';
	   osC_Order::process($_POST['id_pedido'], $this->order_status, $comments_retorno);
	   osC_Order::insertOrderStatusHistory($_POST['id_pedido'], $this->order_status, $comments_venda);
	   	  	      		
      }  
    }

?>
