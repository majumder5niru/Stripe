<?php

    include "Settings.php";
    include "navbar.php";
    include "Library/dbconnect.php";
    //require_once('vendor/autoload.php');
    require_once('vendor/stripe/stripe-php/init.php');
    $email = $_POST['email'];
    $cardNumber = $_POST['card_number'];
    $expiryMonth = $_POST['expiration_month'];
    $expiryYear = $_POST['expiration_year'];
    $cvc = $_POST['cvc'];
    $price = $_POST['price'];
    $product_id = $_POST['product_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];

    $a = CreateCustomer($email,$cardNumber,$expiryMonth,$expiryYear,$cvc,$price,$product_id,$first_name,$last_name);
    foreach($a as $value){
        echo $value."<br>";
    }

    function CreateCustomer($email,$cardNumber,$expiryMonth,$expiryYear,$cvc,$price,$product_id,$first_name,$last_name)
    {
        $settingsObj = new Settings();
        $stripeSecretKey = $settingsObj->GetValue('sk_test_ht2xSOdTIhQoNYsAzG8wnjYP');
        if($stripeSecretKey=="")
            return array('success'=>false,'reason'=>'secret_key_not_found','details'=>'Please set stripe api key on settings page');
        
        \Stripe\Stripe::setApiKey($stripeSecretKey);
        
        $planInfo = null;
        try 
        {
            $customerInfo = array(
                                'email' => $email,
                                'card'  => array(
                                "number" => $cardNumber,
                                "exp_month" => $expiryMonth,
                                "exp_year" => $expiryYear,
                                "cvc" => $cvc
                                )
                            );
            $customer = \Stripe\Customer::create($customerInfo);
            $stripeCustomerId = $customer->id;
            $b = CompleteOneTimePayment($stripeCustomerId,$price);
            foreach($b as $value){
                echo $value."<br>";
            }
            
            $Asql = mysql_query("INSERT INTO sold (first_name,last_name,email,card_number,product_id) VALUES ('$first_name','$last_name','$email','$cardNumber','$product_id')");

            return array('success'=>true,'customer_id'=>$stripeCustomerId,'customer_info'=>$customer); 
        } 
        catch(\Stripe\Error\Card $e) 
        {
          $body = $e->getJsonBody();
          $err  = $body['error'];
          return array('success'=>false,'reason'=>'card_declined','details'=>$err['message']);          
        } 
        catch (\Stripe\Error\InvalidRequest $e) 
        {
            return array('success'=>false,'reason'=>'invalid_parameter_supplied','details'=>'Invalid parameter supplied to stripe');
        } 
        catch (\Stripe\Error\Authentication $e) 
        {
            return array('success'=>false,'reason'=>'secret_key_not_valid','details'=>'Invalid parameter supplied to stripe');
        } 
        catch (\Stripe\Error\ApiConnection $e) 
        {
            return array('success'=>false,'reason'=>'connection_problem','details'=>'connection to stripe is not working');
        } 
        catch (Exception $e) 
        {
            return array('success'=>false,'reason'=>'other_error','details'=>'connection to stripe is not working');
        }
    } 

function CompleteOneTimePayment($stripeCustomerId,$price)
    {
        $settingsObj = new Settings();
        $stripeSecretKey = $settingsObj->GetValue('sk_test_ht2xSOdTIhQoNYsAzG8wnjYP');
        
        if($stripeSecretKey=="")
            return array('success'=>false,'reason'=>'secret_key_not_found','details'=>'Please set stripe api key on settings page');
        
        \Stripe\Stripe::setApiKey($stripeSecretKey);
        
        $planInfo = null;
        try 
        {
            $price = (int)$price*100;
            $paymentInfo = array(
                'customer' => $stripeCustomerId,
                'amount'   => $price,
                'currency' => 'usd'
            );
            $charge = \Stripe\Charge::create($paymentInfo);
            return array('success'=>true,'charge_info'=>$charge); 
        } 
        catch(\Stripe\Error\Card $e) 
        {
          $body = $e->getJsonBody();
          $err  = $body['error'];
          return array('success'=>false,'reason'=>'card_declined','details'=>$err['message']);          
        } 
        catch (\Stripe\Error\InvalidRequest $e) 
        {
            return array('success'=>false,'reason'=>'invalid_parameter_supplied','details'=>'Invalid parameter supplied to stripe');
        } 
        catch (\Stripe\Error\Authentication $e) 
        {
            return array('success'=>false,'reason'=>'secret_key_not_valid','details'=>'Invalid parameter supplied to stripe');
        } 
        catch (\Stripe\Error\ApiConnection $e) 
        {
            return array('success'=>false,'reason'=>'connection_problem','details'=>'connection to stripe is not working');
        } 
        catch (Exception $e) 
        {
            return array('success'=>false,'reason'=>'other_error','details'=>'connection to stripe is not working');
        }
    }
	
?>